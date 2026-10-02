<?php

namespace App\Services\Payments;

use App\Contracts\PayoutGateway;
use App\Enums\GatewayState;
use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Models\Staff;
use App\Models\User;
use App\Notifications\PayoutNotConfirmedNotification;
use App\Notifications\PayoutNotSentNotification;
use App\Notifications\PayoutPaidNotification;
use App\Notifications\PayoutRequestedNotification;
use App\Services\Ledger\LedgerService;
use App\Support\Ledger\LedgerException;
use App\Support\Ledger\LedgerLine;
use App\Support\Money;
use App\Support\Payments\GatewayResponse;
use App\Support\Payments\PaymentOutcome;
use App\Support\Payments\PayoutRequest;
use App\Support\Phone;
use App\Support\Settings\FeePolicy;
use App\Support\Staff\StaffAudit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Withdrawals: a member asks to take earnings out to M-Pesa, staff approve, the money is sent. The amount leaves their available balance
 * the moment they ask (it sits in "payouts on their way out") and goes back if the withdrawal does not end paid, so the same money can never be
 * asked for twice. Every step is posted to the ledger, and every result is acted on in one place, idempotently.
 */
class PayoutService
{
    public function __construct(protected PayoutGateway $gateway, protected LedgerService $ledger) {}

    /** Ask to withdraw. Returns the request, or why it cannot be made. The fee is taken from the amount asked for. */
    public function request(User $user, int $amountMinor, string $phone): Payout|string
    {
        if (! $msisdn = Phone::msisdn($phone)) {
            return 'Enter a valid Safaricom number, for example 0712 345 678.';
        }

        if ($amountMinor <= 0 || $amountMinor % 100 !== 0) {
            return 'Withdraw a whole number of shillings.';
        }

        if ($amountMinor < FeePolicy::minPayoutMinor()) {
            return 'The smallest withdrawal is '.Money::formatMinor(FeePolicy::minPayoutMinor(), 0).'.';
        }

        $fee = FeePolicy::payoutFeeMinor();
        $net = $amountMinor - $fee;

        if ($net <= 0) {
            return 'That is not enough to cover the withdrawal fee of '.Money::formatMinor($fee, 0).'.';
        }

        if ($net / 100 > config('payments.max_kes')) {
            return 'M-Pesa sends up to KES '.number_format(config('payments.max_kes')).' at a time. Withdraw a smaller amount.';
        }

        if (Payout::where('user_id', $user->id)->whereIn('status', [PayoutStatus::Requested, PayoutStatus::Processing])->exists()) {
            return 'You already have a withdrawal in progress. Wait for it to finish, or cancel it.';
        }

        try {
            $payout = DB::transaction(function () use ($user, $amountMinor, $fee, $net, $msisdn) {
                $payout = Payout::create([
                    'reference' => Payout::newReference(), 'user_id' => $user->id, 'amount_minor' => $amountMinor, 'fee_minor' => $fee, 'net_minor' => $net,
                    'currency' => config('marketplace.currency'), 'msisdn' => $msisdn, 'status' => PayoutStatus::Requested,
                ]);

                // Out of their balance now, so it cannot be asked for twice; the ledger refuses if they do not have it
                $this->ledger->post('payout_requested', "payout:{$payout->id}:requested", [
                    LedgerLine::debit($this->ledger->userAccount($user, 'available'), $amountMinor),
                    LedgerLine::credit($this->ledger->platformAccount('payout_clearing'), $amountMinor),
                ], "Withdrawal {$payout->reference} requested", $payout);

                return $payout;
            });
        } catch (LedgerException) {
            return 'You do not have that much available to withdraw.';
        }

        Staff::permission('approve payouts')->where('is_active', true)->get()->each->notify(new PayoutRequestedNotification($payout->load('user')));

        return $payout;
    }

    /** The member takes back a request nobody has approved yet. */
    public function cancel(Payout $payout, User $user): Payout|string
    {
        if ($payout->user_id !== $user->id) {
            return 'This is not your withdrawal.';
        }

        return $this->close($payout, PayoutStatus::Cancelled, 'Cancelled by you.') ?? 'It has already been approved, so it can no longer be cancelled.';
    }

    /** Staff turn a request down, with a reason the member is shown. The money goes back to their balance. */
    public function reject(Payout $payout, Staff $by, string $reason): Payout|string
    {
        $closed = $this->close($payout, PayoutStatus::Rejected, trim($reason), $by);

        if (! $closed) {
            return 'This withdrawal is no longer waiting for approval.';
        }

        StaffAudit::log('payout.rejected', "Turned down the withdrawal {$closed->reference} of ".Money::formatMinor($closed->amount_minor, 0), $closed, ['reason' => trim($reason)], $by->id);
        $closed->user->notify(new PayoutNotSentNotification($closed));

        return $closed;
    }

    /**
     * Staff approve a request: it is marked as being sent (and committed) before the gateway is asked, so a crash can never send it twice; if the
     * gateway refuses it, the money goes back and the withdrawal ends failed.
     */
    public function approve(Payout $payout, Staff $by): Payout|string
    {
        $payout = DB::transaction(function () use ($payout, $by) {
            $locked = Payout::with('user')->whereKey($payout->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PayoutStatus::Requested) {
                return null;
            }

            $locked->update(['status' => PayoutStatus::Processing, 'gateway' => $this->gateway->name(), 'approved_by' => $by->id, 'approved_at' => now()]);

            return $locked;
        });

        if (! $payout) {
            return 'This withdrawal is no longer waiting for approval.';
        }

        StaffAudit::log('payout.approved', "Approved the withdrawal {$payout->reference} of ".Money::formatMinor($payout->amount_minor, 0).' to '.$payout->user->name, $payout, ['net' => $payout->net_minor], $by->id);

        try {
            $response = $this->gateway->sendPayout(new PayoutRequest($payout->reference, $payout->msisdn, $payout->net_minor, 'Withdrawal'));
        } catch (Throwable $e) {
            // We cannot tell whether the gateway took it. Giving the money back now could pay the member twice, so the withdrawal stays "being
            // sent" under our own reference, for the gateway's result or for staff to settle once they have checked.
            report($e);
            $response = new GatewayResponse(true, $payout->reference, 'Sent; waiting for confirmation.');
        }

        if (! $response->accepted) {
            return $this->returnFunds($payout, PayoutStatus::Failed, $response->message ?? 'The transfer could not be started.', true);
        }

        $payout->update(['gateway_reference' => $response->gatewayReference]);

        return $payout;
    }

    /** Ask the gateway where a payout being sent has got to, and act on the answer (for a callback that is late). */
    public function refresh(Payout $payout): Payout
    {
        if ($payout->status !== PayoutStatus::Processing || ! $payout->gateway_reference) {
            return $payout;
        }

        try {
            $outcome = $this->gateway->queryPayout($payout->gateway_reference);
        } catch (Throwable $e) {
            report($e);

            return $payout;
        }

        return $this->applyOutcome($payout, $outcome);
    }

    /** Check every payout still being sent, and tell staff about any that M-Pesa has not confirmed after too long. */
    public function checkProcessing(): int
    {
        $count = 0;

        Payout::where('status', PayoutStatus::Processing)->whereNotNull('gateway_reference')->each(function (Payout $payout) use (&$count) {
            $payout = $this->refresh($payout);
            $count++;

            if ($payout->status === PayoutStatus::Processing && $payout->approved_at?->lt(now()->subHours((int) config('payments.payout_stale_hours')))) {
                $this->flagNotConfirmed($payout);
            }
        });

        return $count;
    }

    /** Tell the approvers once a day while a withdrawal stays unconfirmed. */
    private function flagNotConfirmed(Payout $payout): void
    {
        if (! Cache::add("payout-not-confirmed.{$payout->id}", true, now()->addDay())) {
            return;
        }

        Staff::permission('approve payouts')->where('is_active', true)->get()->each->notify(new PayoutNotConfirmedNotification($payout));
    }

    /**
     * Staff settle a withdrawal M-Pesa never confirmed, after checking the M-Pesa portal: it was sent (the receipt is recorded and the books
     * close as if the result had come), or it was not (the money goes back to the member). Only a withdrawal still "being sent" can be settled.
     */
    public function settleByHand(Payout $payout, Staff $by, bool $sent, string $note, ?string $receipt = null): Payout|string
    {
        $note = trim($note);
        $receipt = $receipt !== null ? strtoupper(trim($receipt)) : null;

        if ($sent && blank($receipt)) {
            return 'Enter the M-Pesa receipt from the portal to record it as sent.';
        }

        $result = DB::transaction(function () use ($payout, $sent, $note, $receipt) {
            $locked = Payout::with('user')->whereKey($payout->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PayoutStatus::Processing) {
                return null;
            }

            return $sent
                ? $this->markPaid($locked, new PaymentOutcome($locked->gateway_reference ?? $locked->reference, GatewayState::Succeeded, $receipt, $locked->net_minor))
                : $this->returnFunds($locked, PayoutStatus::Failed, $note !== '' ? $note : 'Staff confirmed the transfer was not sent.', false);
        });

        if (! $result) {
            return 'This withdrawal is no longer being sent.';
        }

        StaffAudit::log($sent ? 'payout.settled_sent' : 'payout.settled_not_sent', ($sent ? 'Marked as sent' : 'Marked as not sent').": withdrawal {$result->reference} of ".Money::formatMinor($result->net_minor, 0).' to '.$result->user->name, $result, ['receipt' => $receipt, 'note' => $note], $by->id);

        $result->user->notify($sent ? new PayoutPaidNotification($result) : new PayoutNotSentNotification($result));

        return $result;
    }

    /** Act on what the gateway says about a payout being sent. Idempotent: anything no longer being sent is left alone. */
    public function applyOutcome(Payout $payout, PaymentOutcome $outcome): Payout
    {
        $result = DB::transaction(function () use ($payout, $outcome) {
            $locked = Payout::with('user')->whereKey($payout->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PayoutStatus::Processing) {
                return [$locked, null];
            }

            return match ($outcome->state) {
                GatewayState::Pending => [$locked, null],
                GatewayState::Succeeded => [$this->markPaid($locked, $outcome), 'paid'],
                default => [$this->returnFunds($locked, PayoutStatus::Failed, $outcome->reason ?? 'The transfer failed.', false), 'failed'],
            };
        });

        [$payout, $happened] = $result;

        match ($happened) {
            'paid' => $payout->user->notify(new PayoutPaidNotification($payout)),
            'failed' => $payout->user->notify(new PayoutNotSentNotification($payout)),
            default => null,
        };

        return $payout;
    }

    private function markPaid(Payout $payout, PaymentOutcome $outcome): Payout
    {
        $lines = [
            LedgerLine::debit($this->ledger->platformAccount('payout_clearing'), $payout->amount_minor),
            LedgerLine::credit($this->ledger->platformAccount('gateway'), $payout->net_minor),
        ];

        if ($payout->fee_minor > 0) {
            $lines[] = LedgerLine::credit($this->ledger->platformAccount('payout_fees'), $payout->fee_minor);
        }

        $this->ledger->post('payout_paid', "payout:{$payout->id}:paid", $lines, "Withdrawal {$payout->reference} sent", $payout, ['receipt' => $outcome->receipt]);
        $payout->update(['status' => PayoutStatus::Paid, 'receipt' => $outcome->receipt, 'completed_at' => now(), 'failure_reason' => null]);

        return $payout;
    }

    /** End a withdrawal that did not get sent and give the member their money back. Returns null when it had already moved on. */
    private function close(Payout $payout, PayoutStatus $status, string $reason, ?Staff $by = null): ?Payout
    {
        return DB::transaction(function () use ($payout, $status, $reason, $by) {
            $locked = Payout::with('user')->whereKey($payout->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PayoutStatus::Requested) {
                return null;
            }

            if ($by) {
                $locked->approved_by = $by->id;
            }

            return $this->returnFunds($locked, $status, $reason, false);
        });
    }

    /** Put the money back into the member's available balance and end the withdrawal with the given status. */
    private function returnFunds(Payout $payout, PayoutStatus $status, string $reason, bool $notify): Payout
    {
        DB::transaction(function () use ($payout, $status, $reason) {
            $this->ledger->post('payout_returned', "payout:{$payout->id}:returned", [
                LedgerLine::debit($this->ledger->platformAccount('payout_clearing'), $payout->amount_minor),
                LedgerLine::credit($this->ledger->userAccount($payout->user, 'available'), $payout->amount_minor),
            ], "Withdrawal {$payout->reference} returned to the balance", $payout, ['reason' => $reason]);

            $payout->update(['status' => $status, 'failure_reason' => $reason, 'completed_at' => now()]);
        });

        if ($notify) {
            $payout->user->notify(new PayoutNotSentNotification($payout));
        }

        return $payout;
    }
}
