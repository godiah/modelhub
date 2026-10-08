<?php

namespace App\Enums;

/** How urgently staff should look. Decided by ModelHub from live records and rules, never by the assistant or the member. */
enum SupportTicketSeverity: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Low => 'neutral',
            self::Normal => 'blue',
            self::High => 'amber',
            self::Urgent => 'red',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::Low => 0,
            self::Normal => 1,
            self::High => 2,
            self::Urgent => 3,
        };
    }

    public function raised(): self
    {
        return match ($this) {
            self::Low => self::Normal,
            self::Normal => self::High,
            self::High, self::Urgent => self::Urgent,
        };
    }

    public function atLeast(self $other): self
    {
        return $this->rank() >= $other->rank() ? $this : $other;
    }
}
