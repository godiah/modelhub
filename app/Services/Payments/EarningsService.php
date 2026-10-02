<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;
use App\Services\Ledger\LedgerService;
use App\Support\Ledger\LedgerLine;
use Illuminate\Support\Facades\DB;

/** A seller's earnings: when each sale's share leaves the hold and becomes withdrawable, and the figures their earnings page shows. */
class EarningsService
{
    public function __construct(protected LedgerService $ledger) {}

    /** Move the seller's share of every sale whose hold has ended from pending to available. Safe to run as often as you like. */
    public function releaseDue(): int
    {
        $released = 0;

        Payment::where('status', PaymentStatus::Succeeded)->whereNull('released_at')->where('release_at', '<=', now())->with('purchase')->each(function (Payment $payment) use (&$released) {
            $released += $this->release($payment) ? 1 : 0;
        });

        return $released;
    }

    /** Release one sale's share. Skipped when the purchase was refunded in the meantime (the refund hands that money back instead). */
    public function release(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            $locked = Payment::with(['seller', 'purchase'])->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->released_at || $locked->status !== PaymentStatus::Succeeded || $locked->purchase?->status === 'refunded') {
                return false;
            }

            $this->ledger->post('release', "release:payment:{$locked->id}", [
                LedgerLine::debit($this->ledger->userAccount($locked->seller, 'pending'), $locked->seller_share_minor),
                LedgerLine::credit($this->ledger->userAccount($locked->seller, 'available'), $locked->seller_share_minor),
            ], "Hold ended on sale {$locked->reference}", $locked);

            $locked->update(['released_at' => now()]);

            return true;
        });
    }

    /**
     * The figures for a member's earnings page, in minor units.
     *
     * @return array{pending: int, available: int, earned: int, withdrawn: int, in_progress: int}
     */
    public function summary(User $user): array
    {
        return $this->ledger->balances($user) + [
            'earned' => (int) Payment::where('seller_id', $user->id)->where('status', PaymentStatus::Succeeded)->sum('seller_share_minor'),
            'withdrawn' => (int) Payout::where('user_id', $user->id)->where('status', PayoutStatus::Paid)->sum('amount_minor'),
            'in_progress' => (int) Payout::where('user_id', $user->id)->whereIn('status', [PayoutStatus::Requested, PayoutStatus::Processing])->sum('amount_minor'),
        ];
    }
}
