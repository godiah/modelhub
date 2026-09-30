<?php

namespace App\Enums;

use App\Notifications\ApplicationWithdrawnNotification;
use App\Notifications\DisputeCreatedNotification;
use App\Notifications\EngagementCancelledNotification;
use App\Notifications\EngagementResponseNotification;
use App\Notifications\HiredNotification;
use App\Notifications\JobPostedNotification;
use App\Notifications\NewApplicationMessage;
use App\Notifications\PartialPaymentProcessedNotification;
use App\Notifications\PaymentAcceptedNotification;
use App\Notifications\PaymentDisputedNotification;
use App\Notifications\ReviewSubmittedNotification;

/**
 * The filter groups on the notifications page. Each notification class belongs to exactly one;
 * anything not listed (a type added later, a stale row) lands in Other rather than disappearing.
 */
enum NotificationCategory: string
{
    case Messages = 'messages';
    case Engagements = 'engagements';
    case Payments = 'payments';
    case Disputes = 'disputes';
    case Reviews = 'reviews';
    case Projects = 'projects';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Messages => 'Messages',
            self::Engagements => 'Hiring & engagements',
            self::Payments => 'Payments',
            self::Disputes => 'Disputes',
            self::Reviews => 'Reviews',
            self::Projects => 'Projects',
            self::Other => 'Other',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Messages => 'envelope',
            self::Engagements => 'briefcase',
            self::Payments => 'banknotes',
            self::Disputes => 'shield-check',
            self::Reviews => 'star',
            self::Projects => 'clipboard-list',
            self::Other => 'bell',
        };
    }

    /**
     * Notification classes stored in `notifications.type` for this category (empty for Other).
     *
     * @return list<class-string>
     */
    public function types(): array
    {
        return match ($this) {
            self::Messages => [NewApplicationMessage::class],
            self::Engagements => [HiredNotification::class, EngagementResponseNotification::class, EngagementCancelledNotification::class, ApplicationWithdrawnNotification::class],
            self::Payments => [PartialPaymentProcessedNotification::class, PaymentAcceptedNotification::class, PaymentDisputedNotification::class],
            self::Disputes => [DisputeCreatedNotification::class],
            self::Reviews => [ReviewSubmittedNotification::class],
            self::Projects => [JobPostedNotification::class],
            self::Other => [],
        };
    }

    /** Every class claimed by a real category, i.e. what "Other" excludes. */
    public static function knownTypes(): array
    {
        return array_merge(...array_map(fn (self $category) => $category->types(), self::cases()));
    }

    public static function forType(string $type): self
    {
        foreach (self::cases() as $category) {
            if (in_array($type, $category->types(), true)) {
                return $category;
            }
        }

        return self::Other;
    }
}
