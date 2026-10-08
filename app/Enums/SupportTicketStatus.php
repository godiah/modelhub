<?php

namespace App\Enums;

/** Where a support ticket has got to. */
enum SupportTicketStatus: string
{
    /** Just filed; nobody has answered yet. */
    case Open = 'open';

    /** Staff have answered and are waiting for the member. */
    case PendingMember = 'pending_member';

    /** The member has answered (or something changed) and staff need to look. */
    case PendingStaff = 'pending_staff';

    /** Staff think it is sorted. The member can still reopen it for a while. */
    case Resolved = 'resolved';

    /** Finished: no more replies. */
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::PendingMember => 'Waiting for you',
            self::PendingStaff => 'With staff',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Open, self::PendingStaff => 'amber',
            self::PendingMember => 'blue',
            self::Resolved => 'green',
            self::Closed => 'neutral',
        };
    }

    /** Still needs someone: not resolved or closed. */
    public function isActive(): bool
    {
        return in_array($this, [self::Open, self::PendingMember, self::PendingStaff], true);
    }

    /** Waiting on staff (the queue). */
    public function needsStaff(): bool
    {
        return $this === self::Open || $this === self::PendingStaff;
    }
}
