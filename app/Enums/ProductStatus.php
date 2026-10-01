<?php

namespace App\Enums;

/** Where a model listing is in its life. Only Published listings are visible to buyers. */
enum ProductStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Published = 'published';
    case Rejected = 'rejected';
    case Unpublished = 'unpublished';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InReview => 'In review',
            self::Published => 'Published',
            self::Rejected => 'Needs changes',
            self::Unpublished => 'Unpublished',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::InReview => 'amber',
            self::Published => 'green',
            self::Rejected => 'red',
            self::Unpublished => 'neutral',
        };
    }

    /** A seller may edit the listing's details and files in these states. */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Rejected, self::Unpublished], true);
    }
}
