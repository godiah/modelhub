<?php

namespace App\Support\Navigation;

use App\Models\User;

/**
 * Single source of truth for the signed-in app shell's navigation (sidebar, mobile drawer, top-bar breadcrumb).
 *
 * A plain class rather than config/navigation.php: the entries need permission checks and a live
 * badge count, and closures in config files break `php artisan config:cache`.
 */
final class SidebarMenu
{
    /**
     * Groups visible to the given user, each item flagged with whether it matches the current route.
     *
     * @return array<int, array{label: string, items: array<int, array{label: string, route: string, icon: string, active: bool, badge: int}>}>
     */
    public static function for(User $user): array
    {
        $groups = [];

        foreach (self::definition() as $group) {
            $items = [];

            foreach ($group['items'] as $item) {
                if (isset($item['can']) && ! $user->can($item['can'])) {
                    continue;
                }

                $items[] = [
                    'label' => $item['label'],
                    'route' => $item['route'],
                    'icon' => $item['icon'],
                    'active' => self::isActive($item),
                    'badge' => ($item['badge'] ?? null) === 'unread-notifications'
                        ? $user->unreadNotifications()->count()
                        : 0,
                ];
            }

            if ($items !== []) {
                $groups[] = ['label' => $group['label'], 'items' => $items];
            }
        }

        return $groups;
    }

    /**
     * The group and item matching the current route, for the top-bar breadcrumb.
     *
     * @param  array<int, array{label: string, items: array<int, array{label: string, active: bool}>}>  $groups
     * @return array{group: string, item: string}|null
     */
    public static function current(array $groups): ?array
    {
        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                if ($item['active']) {
                    return ['group' => $group['label'], 'item' => $item['label']];
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, array{label: string, items: array<int, array<string, mixed>>}>
     */
    private static function definition(): array
    {
        return [
            [
                'label' => 'Overview',
                'items' => [
                    ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'squares-2x2', 'match' => ['dashboard']],
                    ['label' => 'Notifications', 'route' => 'notifications.index', 'icon' => 'bell', 'match' => ['notifications.*'], 'badge' => 'unread-notifications'],
                ],
            ],
            [
                'label' => 'Find work',
                'items' => [
                    ['label' => 'Browse projects', 'route' => 'jobs.browse', 'icon' => 'magnifying-glass', 'match' => ['jobs.index', 'jobs.browse', 'jobs.apply']],
                    ['label' => 'My applications', 'route' => 'applications.my', 'icon' => 'document-text', 'match' => ['applications.my', 'applications.show', 'applications.archived', 'applications.continue']],
                    ['label' => 'Drafts', 'route' => 'applications.drafts', 'icon' => 'pencil-square', 'match' => ['applications.drafts']],
                ],
            ],
            [
                'label' => 'Hire',
                'items' => [
                    ['label' => 'Post a project', 'route' => 'jobs.create', 'icon' => 'plus', 'match' => ['jobs.create']],
                    ['label' => 'Posted projects', 'route' => 'my-jobs.index', 'icon' => 'briefcase', 'match' => ['my-jobs.index', 'my-jobs.applications.*', 'my-jobs.archived.*', 'jobs.show', 'jobs.edit']],
                    ['label' => 'Message templates', 'route' => 'my-jobs.message-templates', 'icon' => 'chat-bubble-text', 'match' => ['my-jobs.message-templates*']],
                ],
            ],
            [
                'label' => 'Delivery',
                'items' => [
                    ['label' => 'Projects', 'route' => 'project.index', 'icon' => 'clipboard-list', 'match' => ['project.*']],
                    ['label' => 'Engagements', 'route' => 'engagements.index', 'icon' => 'chat-bubble-left-right', 'match' => ['engagements.*'], 'except' => ['engagements.policy']],
                ],
            ],
            [
                'label' => 'Administration',
                'items' => [
                    ['label' => 'Disputed engagements', 'route' => 'admin.disputes.index', 'icon' => 'shield-check', 'match' => ['admin.disputes.*'], 'can' => 'view disputes'],
                    ['label' => 'Staff roles', 'route' => 'admin.staff.index', 'icon' => 'users', 'match' => ['admin.staff.*'], 'can' => 'manage users'],
                ],
            ],
            [
                'label' => 'Help',
                'items' => [
                    ['label' => 'Cancellation policy', 'route' => 'engagements.policy', 'icon' => 'scale', 'match' => ['engagements.policy']],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private static function isActive(array $item): bool
    {
        $request = request();

        if (isset($item['except']) && $request->routeIs(...$item['except'])) {
            return false;
        }

        return $request->routeIs(...$item['match']);
    }
}
