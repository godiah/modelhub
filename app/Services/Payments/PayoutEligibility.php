<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\User;
use App\Services\Ledger\LedgerService;
use App\Support\Money;
use App\Support\Settings\FeePolicy;

/**
 * The rules for asking to withdraw, in one place and with no side effects. PayoutService::request applies them before it writes anything;
 * the support assistant asks the same questions to say WHY a member cannot withdraw, so the two can never disagree about a rule.
 *
 * Only reads. Whether the ledger really holds enough is still decided, atomically, when the request is posted.
 */
class PayoutEligibility
{
    public function __construct(private readonly LedgerService $ledger) {}

    /** Why this amount cannot be requested (in the member's words), or null if the amount itself is fine. */
    public function amountProblem(int $amountMinor): ?string
    {
        if ($amountMinor <= 0 || $amountMinor % 100 !== 0) {
            return 'Withdraw a whole number of shillings.';
        }

        if ($amountMinor < FeePolicy::minPayoutMinor()) {
            return 'The smallest withdrawal is '.Money::formatMinor(FeePolicy::minPayoutMinor(), 0).'.';
        }

        $fee = FeePolicy::payoutFeeMinor();

        if ($amountMinor - $fee <= 0) {
            return 'That is not enough to cover the withdrawal fee of '.Money::formatMinor($fee, 0).'.';
        }

        if (($amountMinor - $fee) / 100 > config('payments.max_kes')) {
            return 'M-Pesa sends up to KES '.number_format(config('payments.max_kes')).' at a time. Withdraw a smaller amount.';
        }

        return null;
    }

    /** The withdrawal they already have in progress, if any: only one at a time. */
    public function openWithdrawal(User $user): ?Payout
    {
        return Payout::where('user_id', $user->id)->whereIn('status', [PayoutStatus::Requested, PayoutStatus::Processing])->latest('id')->first();
    }

    /**
     * Where a member stands: what they have, what is held and until when, the most they could withdraw now, and what is stopping them.
     *
     * @return array<string, mixed>
     */
    public function summary(User $user): array
    {
        $balances = $this->ledger->balances($user);
        $available = $balances['available'];
        $min = FeePolicy::minPayoutMinor();
        $fee = FeePolicy::payoutFeeMinor();
        $open = $this->openWithdrawal($user);

        // The biggest single request M-Pesa will carry: its cap on what is sent, plus the fee that comes out of the amount asked for
        $largestRequest = (int) round(config('payments.max_kes') * 100) + $fee;
        $withdrawable = max(0, min($available, $largestRequest));
        $withdrawable -= $withdrawable % 100;

        $holds = Payment::where('seller_id', $user->id)->where('purpose', Payment::PURPOSE_SALE)->where('status', PaymentStatus::Succeeded)
            ->whereNull('released_at')->whereNotNull('release_at')->get(['release_at', 'seller_share_minor']);

        $blockers = [];
        if ($open !== null) {
            $blockers[] = 'open_withdrawal';
        }
        if ($available < $min) {
            $blockers[] = $available <= 0 ? 'nothing_available' : 'below_minimum';
        }

        return [
            'available_minor' => $available,
            'pending_minor' => $balances['pending'],
            'min_withdrawal_minor' => $min,
            'fee_minor' => $fee,
            'max_per_withdrawal_minor' => (int) round(config('payments.max_kes') * 100),
            // what they could ask for right now: nothing while something blocks them (their available balance is shown separately)
            'withdrawable_minor' => $blockers === [] && $withdrawable >= $min ? $withdrawable : 0,
            'can_withdraw' => $blockers === [] && $available - $fee > 0,
            'blockers' => $blockers,
            'open_withdrawal' => $open === null ? null : ['reference' => $open->reference, 'status' => $open->status->value, 'status_label' => $open->status->label()],
            'held_payments' => $holds->count(),
            'held_minor' => (int) $holds->sum('seller_share_minor'),
            // The hold has ended but the hourly job that releases it has not run yet: it will show as available within the hour
            'overdue_payments' => $holds->filter(fn ($p) => $p->release_at->isPast())->count(),
            'next_release_at' => $holds->filter(fn ($p) => $p->release_at->isFuture())->min('release_at')?->toIso8601String(),
        ];
    }
}
