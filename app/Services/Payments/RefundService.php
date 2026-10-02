<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Staff;
use App\Notifications\PaymentRefundedNotification;
use App\Notifications\SaleRefundedNotification;
use App\Services\Ledger\LedgerService;
use App\Services\Marketplace\LicenceService;
use App\Support\Ledger\LedgerException;
use App\Support\Ledger\LedgerLine;
use App\Support\Money;
use App\Support\Staff\StaffAudit;
use Illuminate\Support\Facades\DB;

/**
 * Giving a buyer their money back. Two cases, both ending the payment as refunded:
 *
 *  - a sale that went through: the licence ends, and the seller's share and the platform's commission are taken back out of the ledger
 *    (from the seller's pending earnings if the hold has not ended, otherwise from what they still have available);
 *  - a payment that arrived but could not be turned into a licence (it is sitting in the unallocated account): the parked money is returned.
 *
 * The ledger records that the money left the gateway; sending it back to the buyer's M-Pesa is done by staff in the M-Pesa portal (there is no
 * reversal in the payment gateway yet), so a refund is recorded once that has been done.
 */
class RefundService
{
    public function __construct(protected LedgerService $ledger, protected LicenceService $licences) {}

    /** Why this payment cannot be refunded, or null when it can. */
    public function cannotRefund(Payment $payment): ?string
    {
        if ($payment->isEscrow() && $payment->status === PaymentStatus::Succeeded) {
            return 'This money is held in the job\'s escrow. It is paid out or returned from the job, not refunded here.';
        }

        return match ($payment->status) {
            PaymentStatus::Succeeded, PaymentStatus::Review => null,
            PaymentStatus::Refunded => 'This payment has already been refunded.',
            default => 'Nothing was paid, so there is nothing to refund.',
        };
    }

    public function refund(Payment $payment, Staff $by, string $reason): Payment|string
    {
        $reason = trim($reason);
        $notify = null;

        $result = DB::transaction(function () use ($payment, $by, $reason, &$notify) {
            $locked = Payment::with(['seller', 'buyer', 'product', 'purchase.licence'])->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($error = $this->cannotRefund($locked)) {
                return $error;
            }

            $wasSale = $locked->status === PaymentStatus::Succeeded;

            try {
                $wasSale ? $this->reverseSale($locked) : $this->returnParkedMoney($locked);
            } catch (LedgerException) {
                return 'The seller has already withdrawn this money, so it cannot be taken back from their earnings here.';
            }

            if ($wasSale && $locked->purchase?->licence) {
                $this->licences->revoke($locked->purchase->licence, "Refunded: {$reason}");
            }

            $locked->update(['status' => PaymentStatus::Refunded, 'refunded_at' => now(), 'refunded_by' => $by->id, 'refund_reason' => $reason]);
            StaffAudit::log('payment.refunded', "Refunded {$locked->reference} (".Money::formatMinor($locked->received_minor ?? $locked->amount_minor).') to '.$locked->buyer->name, $locked, ['reason' => $reason, 'was_sale' => $wasSale], $by->id);

            $notify = $wasSale;

            return $locked->fresh(['seller', 'buyer', 'product']);
        });

        if (is_string($result)) {
            return $result;
        }

        $result->buyer->notify(new PaymentRefundedNotification($result));

        if ($notify) {
            $result->seller->notify(new SaleRefundedNotification($result));
        }

        return $result;
    }

    /** Take the sale back out of the ledger: the gateway gives the money back, and the seller's share and the commission are reversed. */
    private function reverseSale(Payment $payment): void
    {
        $from = $this->ledger->userAccount($payment->seller, $payment->released_at ? 'available' : 'pending');
        $lines = [LedgerLine::debit($from, $payment->seller_share_minor)];

        if ($payment->commission_minor > 0) {
            $lines[] = LedgerLine::debit($this->ledger->platformAccount('revenue'), $payment->commission_minor);
        }

        $lines[] = LedgerLine::credit($this->ledger->platformAccount('gateway'), $payment->amount_minor);

        $this->ledger->post('refund', "refund:payment:{$payment->id}", $lines, "Refund of sale {$payment->reference}", $payment);
    }

    /** Hand back money that was parked as unallocated. */
    private function returnParkedMoney(Payment $payment): void
    {
        $amount = $payment->received_minor ?? $payment->amount_minor;

        $this->ledger->post('refund_unallocated', "refund:payment:{$payment->id}", [
            LedgerLine::debit($this->ledger->platformAccount('suspense'), $amount),
            LedgerLine::credit($this->ledger->platformAccount('gateway'), $amount),
        ], "Refund of unallocated payment {$payment->reference}", $payment);
    }
}
