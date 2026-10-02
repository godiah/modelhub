<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\GatewayState;
use App\Enums\LicenceTier;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Notifications\ModelPurchasedNotification;
use App\Notifications\ModelSoldNotification;
use App\Services\Ledger\LedgerService;
use App\Services\Marketplace\LicenceService;
use App\Support\Ledger\LedgerLine;
use App\Support\Money;
use App\Support\Payments\GatewayResponse;
use App\Support\Payments\PaymentOutcome;
use App\Support\Payments\PaymentRequest;
use App\Support\Phone;
use App\Support\Settings\FeePolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Taking a payment for a model, from the M-Pesa prompt to the licence. start() sends the prompt and fixes how the sale will be split;
 * applyOutcome() is the single place a result is acted on, whether it arrives by the gateway's callback, by a status check or late. It is
 * safe to call any number of times for the same payment: the money is recorded, and the licence issued, once.
 */
class PaymentService
{
    public function __construct(protected PaymentGateway $gateway, protected LicenceService $licences, protected LedgerService $ledger) {}

    /** Start paying for a licence on a model. Returns the payment (pending, or failed if the prompt could not be sent), or the reason it cannot start. */
    public function start(User $buyer, Product $product, LicenceTier $tier, string $phone): Payment|string
    {
        if (! $msisdn = Phone::msisdn($phone)) {
            return 'Enter a valid Safaricom number, for example 0712 345 678.';
        }

        if ($error = $this->licences->cannotBuy($buyer, $product, $tier)) {
            return $error;
        }

        $amount = $product->priceFor($tier);

        if ($amount <= 0) {
            return 'This licence is free, so no payment is needed.';
        }

        if ($amount % 100 !== 0) {
            return 'This model\'s price is not in whole shillings, so it cannot be paid for by M-Pesa. Please tell the seller.';
        }

        if ($amount / 100 < config('payments.min_kes') || $amount / 100 > config('payments.max_kes')) {
            return 'M-Pesa takes between KES '.number_format(config('payments.min_kes')).' and KES '.number_format(config('payments.max_kes')).' in one payment.';
        }

        // Pressing "pay" twice must not send two prompts: carry on with the one that is still waiting
        $waiting = Payment::where('user_id', $buyer->id)->where('product_id', $product->id)->where('tier', $tier)->where('status', PaymentStatus::Pending)->where('expires_at', '>', now())->latest('id')->first();

        if ($waiting) {
            return $waiting;
        }

        // How the sale is split, and how long the seller's share is held, is fixed now and never changes for this payment
        $rate = FeePolicy::modelsRate($product->sellerProfile);
        $commission = (int) round($amount * $rate);

        $payment = Payment::create([
            'reference' => Payment::newReference(), 'user_id' => $buyer->id, 'seller_id' => $product->user_id, 'product_id' => $product->id, 'tier' => $tier, 'amount_minor' => $amount,
            'currency' => $product->currency, 'msisdn' => $msisdn, 'status' => PaymentStatus::Pending, 'gateway' => $this->gateway->name(),
            'commission_rate' => $rate, 'commission_minor' => $commission, 'seller_share_minor' => $amount - $commission, 'hold_days' => FeePolicy::saleHoldDays(),
            'expires_at' => now()->addMinutes(config('payments.pending_minutes')),
        ]);

        try {
            $response = $this->gateway->requestPayment(new PaymentRequest($payment->reference, $msisdn, $amount, Str::limit(config('app.name'), 13, '')));
        } catch (Throwable $e) {
            report($e);
            $response = new GatewayResponse(false, null, 'Payments are not available right now. Please try again in a moment.');
        }

        if (! $response->accepted) {
            $payment->update(['status' => PaymentStatus::Failed, 'failure_reason' => $response->message ?? 'The prompt could not be sent.']);

            return $payment;
        }

        $payment->update(['gateway_reference' => $response->gatewayReference]);

        return $payment;
    }

    /** Ask the gateway where a pending payment has got to and act on the answer (for a callback that is late). A prompt nobody answered ends as expired. */
    public function refresh(Payment $payment): Payment
    {
        if (! $payment->isPending() || ! $payment->gateway_reference) {
            return $payment;
        }

        try {
            $outcome = $this->gateway->queryPayment($payment->gateway_reference);
        } catch (Throwable $e) {
            report($e);
            $outcome = new PaymentOutcome($payment->gateway_reference, GatewayState::Pending);
        }

        if ($outcome->state === GatewayState::Pending && $payment->hasExpired()) {
            $outcome = new PaymentOutcome($payment->gateway_reference, GatewayState::TimedOut, reason: 'The prompt was not answered in time.');
        }

        return $this->applyOutcome($payment, $outcome);
    }

    /** Give up on every prompt that has not been answered in time (after one last check with the gateway, in case the money did arrive). */
    public function expireStale(): int
    {
        $count = 0;

        Payment::where('status', PaymentStatus::Pending)->where('expires_at', '<', now())->each(function (Payment $payment) use (&$count) {
            $this->refresh($payment);
            $count++;
        });

        return $count;
    }

    /** Act on what the gateway says. Idempotent: a payment already settled (or sent to review) is left alone, and a late "paid" still counts. */
    public function applyOutcome(Payment $payment, PaymentOutcome $outcome): Payment
    {
        $licence = null;

        $payment = DB::transaction(function () use ($payment, $outcome, &$licence) {
            $payment = Payment::with(['product', 'buyer'])->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if (in_array($payment->status, [PaymentStatus::Succeeded, PaymentStatus::Review], true)) {
                return $payment;
            }

            if ($outcome->state === GatewayState::Succeeded) {
                $licence = $this->settle($payment, $outcome);

                return $payment->fresh();
            }

            // A failure only ends a payment that was still waiting; it never undoes anything
            if ($payment->isPending()) {
                [$status, $reason] = match ($outcome->state) {
                    GatewayState::Failed => [PaymentStatus::Failed, $outcome->reason ?? 'The payment did not go through.'],
                    GatewayState::Cancelled => [PaymentStatus::Cancelled, $outcome->reason ?? 'The payment was cancelled.'],
                    GatewayState::TimedOut => [PaymentStatus::Expired, $outcome->reason ?? 'The prompt was not answered in time.'],
                    default => [null, null],
                };

                if ($status) {
                    $payment->update(['status' => $status, 'failure_reason' => $reason]);
                }
            }

            return $payment->fresh();
        });

        if ($licence && $payment->status === PaymentStatus::Succeeded) {
            $payment->buyer->notify(new ModelPurchasedNotification($payment, $licence));
            $payment->seller->notify(new ModelSoldNotification($payment, $payment->product));
        }

        return $payment;
    }

    /** Money has arrived: check it is what was asked for, issue the licence and record the sale, all together. */
    private function settle(Payment $payment, PaymentOutcome $outcome)
    {
        $paid = $outcome->amountMinor ?? $payment->amount_minor;

        if ($paid !== $payment->amount_minor) {
            $this->review($payment, $outcome, $paid, 'Paid '.Money::formatMinor($paid).' but '.Money::formatMinor($payment->amount_minor).' was expected.');

            return null;
        }

        $licence = $this->licences->grantPaid($payment);

        if (is_string($licence)) {
            $this->review($payment, $outcome, $paid, $licence);

            return null;
        }

        $seller = $payment->seller;
        $lines = [
            LedgerLine::debit($this->ledger->platformAccount('gateway'), $payment->amount_minor),
            LedgerLine::credit($this->ledger->userAccount($seller, 'pending'), $payment->seller_share_minor),
        ];

        if ($payment->commission_minor > 0) {
            $lines[] = LedgerLine::credit($this->ledger->platformAccount('revenue'), $payment->commission_minor);
        }

        $this->ledger->post('sale', "sale:payment:{$payment->id}", $lines, "Sale of \"{$licence->product_title}\" ({$licence->tier->label()} licence)", $payment, ['receipt' => $outcome->receipt, 'licence' => $licence->key]);

        $payment->update([
            'status' => PaymentStatus::Succeeded, 'received_minor' => $paid, 'receipt' => $outcome->receipt, 'completed_at' => now(), 'purchase_id' => $licence->purchase_id, 'failure_reason' => null,
            'release_at' => now()->addDays($payment->hold_days),
        ]);

        return $licence;
    }

    /** Money is in but cannot become a licence: park it as unallocated and flag the payment for staff, rather than lose track of it. */
    private function review(Payment $payment, PaymentOutcome $outcome, int $paid, string $reason): void
    {
        $this->ledger->post('unallocated', "unallocated:payment:{$payment->id}", [
            LedgerLine::debit($this->ledger->platformAccount('gateway'), $paid),
            LedgerLine::credit($this->ledger->platformAccount('suspense'), $paid),
        ], "Payment {$payment->reference} received but not allocated", $payment, ['receipt' => $outcome->receipt, 'reason' => $reason]);

        $payment->update(['status' => PaymentStatus::Review, 'received_minor' => $paid, 'receipt' => $outcome->receipt, 'failure_reason' => $reason, 'completed_at' => now()]);

        Log::warning("Payment {$payment->reference} needs review: {$reason}", ['payment_id' => $payment->id, 'receipt' => $outcome->receipt]);
    }
}
