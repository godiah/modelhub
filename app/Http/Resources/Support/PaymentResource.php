<?php

namespace App\Http\Resources\Support;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What the support assistant may see of one payment the member made (a purchase, or funding a job). An explicit list. Left out on purpose:
 * the product or job title and the seller's name (written by other people, and they would travel into stored chat text), the gateway
 * reference, commission, the seller's share, staff names. The receipt is the member's own, shown in full; the phone is cut to three digits.
 *
 * `licence` is the question people ask: did paying produce one? 'active', 'ended' (revoked, e.g. refunded), 'none' (paid, but none exists),
 * or 'not_applicable' (a job funding payment never produces a licence).
 *
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    public static $wrap = null;

    /** Whether the sale produced a licence still in force: 'active' or 'ended', or 'none'. Worked out by the caller in one query for a list. */
    private string $licence = 'none';

    public function withLicence(string $state): static
    {
        $this->licence = $state;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $failed = in_array($this->status, [PaymentStatus::Failed, PaymentStatus::Cancelled, PaymentStatus::Expired, PaymentStatus::Review], true);

        return [
            'reference' => $this->reference,
            'purpose' => $this->purpose,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'amount_minor' => $this->amount_minor,
            'amount_display' => Money::formatMinor($this->amount_minor, 0),
            'received_display' => $this->received_minor !== null && $this->received_minor !== $this->amount_minor ? Money::formatMinor($this->received_minor, 0) : null,
            'currency' => $this->currency,
            'tier' => $this->isEscrow() ? null : $this->tier?->value,
            'tier_label' => $this->isEscrow() ? null : $this->tier?->label(),
            'phone_last3' => substr((string) $this->msisdn, -3),
            'requested_at' => $this->created_at?->toIso8601String(),
            'expires_at' => $this->isPending() ? $this->expires_at?->toIso8601String() : null,
            'completed_at' => $this->completed_at?->toIso8601String(),
            // Still waiting on M-Pesa after the prompt's time ran out: the expiry job has not looked at it yet, so it is neither paid nor failed
            'pending_past_expiry' => $this->hasExpired(),
            'receipt' => $this->receipt,
            'reason' => $failed ? mb_substr((string) $this->failure_reason, 0, 240) : null,
            'licence' => $this->licenceState(),
        ];
    }

    private function licenceState(): string
    {
        if ($this->isEscrow()) {
            return 'not_applicable';
        }

        return $this->licence;
    }
}
