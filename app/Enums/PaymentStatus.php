<?php

namespace App\Enums;

/** Where a payment a buyer started has got to. */
enum PaymentStatus: string
{
    /** The M-Pesa prompt is on the buyer's phone and not answered yet. */
    case Pending = 'pending';

    /** Paid, and the buyer has their licence. */
    case Succeeded = 'succeeded';

    /** The payment did not go through (declined, wrong PIN, no funds). */
    case Failed = 'failed';

    /** The buyer cancelled the prompt. */
    case Cancelled = 'cancelled';

    /** The prompt was never answered in time. */
    case Expired = 'expired';

    /** Money arrived but could not be turned into a licence (a wrong amount, a duplicate purchase): staff must look at it. */
    case Review = 'review';

    /** Paid, then given back by staff: the licence has ended and the seller's share was taken back. */
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Waiting for payment',
            self::Succeeded => 'Paid',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
            self::Review => 'Needs review',
            self::Refunded => 'Refunded',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'blue',
            self::Succeeded => 'green',
            self::Failed, self::Cancelled, self::Expired => 'neutral',
            self::Review => 'amber',
            self::Refunded => 'red',
        };
    }

    /** Nothing more will happen to it on its own. */
    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }
}
