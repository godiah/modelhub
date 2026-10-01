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
                if (($item['hidden'] ?? false) || (isset($item['can']) && ! $user->can($item['can'])) || (($item['seller'] ?? false) && ! $user->isApprovedSeller())) {
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
     * Breadcrumb for the top bar: group, the matching menu item (linked when a tail follows) and an optional
     * page-supplied tail (a project title, "Archived", ...). Also resolves pages that are reachable but not
     * listed in the sidebar (Profile, Cancellation policy), which is why it walks the full definition.
     *
     * @return list<array{label: string, url: string|null}>
     */
    public static function breadcrumb(?string $tail = null): array
    {
        $crumbs = [];

        foreach (self::definition() as $group) {
            foreach ($group['items'] as $item) {
                if (! self::isActive($item)) {
                    continue;
                }

                $crumbs = [
                    ['label' => $group['label'], 'url' => null],
                    ['label' => $item['label'], 'url' => $tail !== null ? route($item['route']) : null],
                ];

                break 2;
            }
        }

        if ($tail !== null && $tail !== '') {
            $crumbs[] = ['label' => $tail, 'url' => null];
        }

        return $crumbs;
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
                    ['label' => 'My applications', 'route' => 'applications.my', 'icon' => 'document-text', 'match' => ['applications.my', 'applications.show', 'applications.archived', 'applications.drafts', 'applications.continue']],
                ],
            ],
            [
                'label' => 'Hire',
                'items' => [
                    ['label' => 'Post a project', 'route' => 'jobs.create', 'icon' => 'plus', 'match' => ['jobs.create']],
                    ['label' => 'Posted projects', 'route' => 'my-jobs.index', 'icon' => 'briefcase', 'match' => ['my-jobs.index', 'my-jobs.applications.*', 'my-jobs.archived.*', 'jobs.show', 'jobs.edit']],
                ],
            ],
            [
                'label' => 'Marketplace',
                'items' => [
                    ['label' => 'Browse models', 'route' => 'models.index', 'icon' => 'magnifying-glass', 'match' => ['models.*', 'sellers.*']],
                    ['label' => 'Wishlist', 'route' => 'wishlist.index', 'icon' => 'heart', 'match' => ['wishlist.*']],
                    ['label' => 'Sell models', 'route' => 'seller.index', 'icon' => 'cube', 'match' => ['seller.index', 'seller.apply']],
                    ['label' => 'My models', 'route' => 'seller.models.index', 'icon' => 'squares-2x2', 'match' => ['seller.models.*'], 'seller' => true],
                    ['label' => 'My store', 'route' => 'seller.store.edit', 'icon' => 'user', 'match' => ['seller.store.*'], 'seller' => true],
                ],
            ],
            [
                'label' => 'Delivery',
                'items' => [
                    ['label' => 'Engagements', 'route' => 'engagements.index', 'icon' => 'chat-bubble-left-right', 'match' => ['engagements.*'], 'except' => ['engagements.policy']],
                ],
            ],
            [
                'label' => 'Administration',
                'items' => [
                    ['label' => 'Disputed engagements', 'route' => 'admin.disputes.index', 'icon' => 'shield-check', 'match' => ['admin.disputes.*'], 'can' => 'view disputes'],
                    ['label' => 'Model reviews', 'route' => 'admin.models.index', 'icon' => 'cube', 'match' => ['admin.models.*'], 'can' => 'review models'],
                    ['label' => 'Seller applications', 'route' => 'admin.sellers.index', 'icon' => 'cube', 'match' => ['admin.sellers.*'], 'can' => 'review sellers'],
                    ['label' => 'Staff roles', 'route' => 'admin.staff.index', 'icon' => 'users', 'match' => ['admin.staff.*'], 'can' => 'manage users'],
                ],
            ],
            // Reachable pages that are not sidebar entries (profile lives in the user menu, the policy in the
            // footer); listed so the breadcrumb still resolves for them.
            [
                'label' => 'Account',
                'items' => [
                    ['label' => 'Profile', 'route' => 'profile', 'icon' => 'user', 'match' => ['profile'], 'hidden' => true],
                ],
            ],
            [
                'label' => 'Help',
                'items' => [
                    ['label' => 'Cancellation policy', 'route' => 'engagements.policy', 'icon' => 'scale', 'match' => ['engagements.policy'], 'hidden' => true],
                    ['label' => 'Terms of service', 'route' => 'legal.terms', 'icon' => 'document-text', 'match' => ['legal.terms'], 'hidden' => true],
                    ['label' => 'Privacy policy', 'route' => 'legal.privacy', 'icon' => 'shield-check', 'match' => ['legal.privacy'], 'hidden' => true],
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
