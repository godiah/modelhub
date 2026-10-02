<?php

namespace App\Enums;

/** Where a withdrawal has got to. */
enum PayoutStatus: string
{
    /** Asked for, waiting for staff to approve it. */
    case Requested = 'requested';

    /** Approved and sent to the gateway, waiting for the money to land. */
    case Processing = 'processing';

    /** The money reached their phone. */
    case Paid = 'paid';

    /** The gateway could not send it. */
    case Failed = 'failed';

    /** Staff turned it down. */
    case Rejected = 'rejected';

    /** The member took the request back before it was approved. */
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Waiting for approval',
            self::Processing => 'Being sent',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
            self::Rejected => 'Turned down',
            self::Cancelled => 'Cancelled',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Requested => 'amber',
            self::Processing => 'blue',
            self::Paid => 'green',
            self::Failed, self::Rejected => 'red',
            self::Cancelled => 'neutral',
        };
    }

    /** Still going: the money is out of the balance and has not landed or come back. */
    public function isOpen(): bool
    {
        return $this === self::Requested || $this === self::Processing;
    }
}
