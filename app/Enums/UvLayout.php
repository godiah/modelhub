<?php

namespace App\Enums;

enum UvLayout: string
{
    case NonOverlapping = 'non_overlapping';
    case Overlapping = 'overlapping';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::NonOverlapping => 'Non-overlapping',
            self::Overlapping => 'Overlapping',
            self::Mixed => 'Mixed',
        };
    }
}
