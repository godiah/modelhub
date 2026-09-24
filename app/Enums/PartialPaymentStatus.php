<?php

namespace App\Enums;

enum PartialPaymentStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Disputed = 'disputed';
    case Finalized = 'finalized';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Disputed => 'Disputed',
            self::Finalized => 'Finalized',
        };
    }
}
