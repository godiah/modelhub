<?php

namespace App\Services\Notifications;

use App\Enums\NotificationCategory;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Builds the notifications page: the filtered, paginated feed grouped by day, plus the per-filter counts
 * shown in the side rail.
 */
class NotificationFeedService
{
    private const PER_PAGE = 15;

    /**
     * @return array{
     *     notifications: LengthAwarePaginator,
     *     groups: array<string, Collection<int, DatabaseNotification>>,
     *     counts: array{all: int, unread: int, categories: array<string, array{total: int, unread: int}>},
     *     filter: string,
     * }
     */
    public function feed(User $user, string $filter): array
    {
        $query = $user->notifications();

        match (true) {
            $filter === 'unread' => $query->whereNull('read_at'),
            $filter === NotificationCategory::Other->value => $query->whereNotIn('type', NotificationCategory::knownTypes()),
            NotificationCategory::tryFrom($filter) !== null => $query->whereIn('type', NotificationCategory::from($filter)->types()),
            default => null,
        };

        $notifications = $query->paginate(self::PER_PAGE)->withQueryString();

        return [
            'notifications' => $notifications,
            'groups' => $this->groupByDay($notifications->getCollection()),
            'counts' => $this->counts($user),
            'filter' => $filter,
        ];
    }

    /**
     * @param  Collection<int, DatabaseNotification>  $notifications
     * @return array<string, Collection<int, DatabaseNotification>>
     */
    private function groupByDay(Collection $notifications): array
    {
        return $notifications
            ->groupBy(function (DatabaseNotification $notification) {
                $created = $notification->created_at;

                return match (true) {
                    $created->isToday() => 'Today',
                    $created->isYesterday() => 'Yesterday',
                    $created->greaterThanOrEqualTo(now()->subDays(7)->startOfDay()) => 'This week',
                    default => 'Earlier',
                };
            })
            ->all();
    }

    /**
     * @return array{all: int, unread: int, categories: array<string, array{total: int, unread: int}>}
     */
    private function counts(User $user): array
    {
        $byType = $user->notifications()->reorder()
            ->selectRaw('type, COUNT(*) as total, SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread')
            ->groupBy('type')
            ->get();

        $categories = [];
        foreach (NotificationCategory::cases() as $category) {
            $categories[$category->value] = ['total' => 0, 'unread' => 0];
        }

        foreach ($byType as $row) {
            $key = NotificationCategory::forType($row->type)->value;
            $categories[$key]['total'] += (int) $row->total;
            $categories[$key]['unread'] += (int) $row->unread;
        }

        return [
            'all' => (int) $byType->sum('total'),
            'unread' => (int) $byType->sum('unread'),
            'categories' => $categories,
        ];
    }
}
