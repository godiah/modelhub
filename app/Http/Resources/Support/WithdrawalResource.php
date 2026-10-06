<?php

namespace App\Http\Resources\Support;

use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What the support assistant may see of one withdrawal. An explicit list: nothing is added just because the column exists. The phone is
 * cut to its last three digits; the receipt is the member's own and is shown in full (they quote it to staff), but the assistant keeps it
 * out of its logs. No staff names, no gateway references, no ledger ids.
 *
 * @mixin Payout
 */
class WithdrawalResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $staleHours = (int) config('payments.payout_stale_hours');

        return [
            'reference' => $this->reference,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_open' => $this->status->isOpen(),
            'amount_minor' => $this->amount_minor,
            'fee_minor' => $this->fee_minor,
            'net_minor' => $this->net_minor,
            'currency' => $this->currency,
            'amount_display' => Money::formatMinor($this->amount_minor, 0),
            'net_display' => Money::formatMinor($this->net_minor, 0),
            'phone_last3' => substr((string) $this->msisdn, -3),
            'requested_at' => $this->created_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'receipt' => $this->status === PayoutStatus::Paid ? $this->receipt : null,
            // The reason staff or the gateway recorded, for a withdrawal that did not go through; the member is shown it already
            'reason' => in_array($this->status, [PayoutStatus::Failed, PayoutStatus::Rejected, PayoutStatus::Cancelled], true)
                ? mb_substr((string) $this->failure_reason, 0, 240) : null,
            // Being sent for longer than M-Pesa should take: staff check these. Says "not confirmed", never that anyone was alerted.
            'not_confirmed' => $this->status === PayoutStatus::Processing && $this->approved_at?->lt(now()->subHours($staleHours)) === true,
            'not_confirmed_after_hours' => $staleHours,
        ];
    }
}
