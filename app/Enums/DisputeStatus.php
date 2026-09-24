<?php

namespace App\Enums;

enum DisputeStatus: string
{
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::UnderReview => 'Under Review',
            self::Resolved => 'Resolved',
        };
    }
}
