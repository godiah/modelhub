<?php

namespace App\Enums;

/** Where a member stands as a seller of 3D models. Only Approved sellers can list models. */
enum SellerStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending review',
            self::Approved => 'Approved',
            self::Rejected => 'Not approved',
            self::Suspended => 'Suspended',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Suspended => 'neutral',
        };
    }
}
