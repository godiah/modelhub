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
     * @return array<int, array{label: string, items: array<int, array{label: string, route: string, icon: string, section: ?string, active: bool, badge: int}>}>
     */
    public static function for(User $user): array
    {
        $groups = [];

        foreach (self::definition() as $group) {
            $items = [];

            foreach ($group['items'] as $item) {
                if (($item['hidden'] ?? false) || (($item['seller'] ?? false) && ! $user->isApprovedSeller()) || (($item['notSeller'] ?? false) && $user->isApprovedSeller()) || (($item['earner'] ?? false) && ! $user->hasJobEarnings())) {
                    continue;
                }

                $items[] = [
                    'label' => $item['label'],
                    'route' => $item['route'],
                    'icon' => $item['icon'],
                    'section' => $item['section'] ?? null,
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
     * The top bar's primary action for the page being viewed, following the sidebar group the page belongs to:
     * Projects pages offer "Post a project", Models pages offer "Add a model" (or "Sell your models" to someone who
     * does not sell yet), and everywhere else (dashboard, notifications, profile, admin) a "Create" menu with both.
     * The action is left out on the page it would lead to.
     *
     * @return array{type: 'link', label: string, url: string, icon: string}|array{type: 'menu', label: string, items: list<array{label: string, url: string, icon: string}>}|null
     */
    public static function primaryAction(User $user): ?array
    {
        $project = ['label' => __('Post a project'), 'url' => route('jobs.create'), 'icon' => 'plus', 'route' => 'jobs.create'];
        $model = $user->isApprovedSeller()
            ? ['label' => __('Add a model'), 'url' => route('seller.models.create'), 'icon' => 'plus', 'route' => 'seller.models.create']
            : ['label' => __('Sell your models'), 'url' => route('seller.index'), 'icon' => 'banknotes', 'route' => 'seller.index'];

        $choices = match (self::activeGroup()) {
            'Projects' => [$project],
            'Models' => [$model],
            default => [$project, $model],
        };

        $choices = array_values(array_filter($choices, fn (array $choice) => ! request()->routeIs($choice['route'])));
        $choices = array_map(fn (array $choice) => array_diff_key($choice, ['route' => true]), $choices);

        return match (count($choices)) {
            0 => null,
            1 => ['type' => 'link'] + $choices[0],
            default => ['type' => 'menu', 'label' => __('Create'), 'items' => $choices],
        };
    }

    private static function activeGroup(): ?string
    {
        foreach (self::definition() as $group) {
            foreach ($group['items'] as $item) {
                if (self::appliesToCurrentUser($item) && self::isActive($item)) {
                    return $group['label'];
                }
            }
        }

        return null;
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
                if (! self::appliesToCurrentUser($item) || ! self::isActive($item)) {
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

    /** A page listed in two places (Earnings, under Models for sellers and under Projects for the rest) belongs to the one that is the current member's. */
    private static function appliesToCurrentUser(array $item): bool
    {
        $for = $item['crumbFor'] ?? null;

        if ($for === null || ! ($user = auth()->user())) {
            return true;
        }

        return ($for === 'sellers') === $user->isApprovedSeller();
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
                    ...(config('support.enabled') ? [['label' => 'My requests', 'route' => 'support.requests.index', 'icon' => 'inbox', 'match' => ['support.requests.*']]] : []),
                    // Visual preview of "My requests" (SUPPORT_UI_PREVIEW); mock data, kept for design reference
                    ...(config('support.ui_preview') && app()->environment('local') ? [['label' => 'My requests (design preview)', 'route' => 'dev.support.requests', 'icon' => 'inbox', 'match' => ['dev.support.request', 'dev.support.requests']]] : []),
                ],
            ],
            // One group per product, each with its buying and selling sides together. A `section` adds a small
            // sub-label before the first item that carries it.
            [
                'label' => 'Models',
                'items' => [
                    ['label' => 'Browse models', 'route' => 'models.index', 'icon' => 'cube', 'match' => ['models.*', 'sellers.*']],
                    ['label' => 'Wishlist', 'route' => 'wishlist.index', 'icon' => 'heart', 'match' => ['wishlist.*']],
                    ['label' => 'My licences', 'route' => 'licences.index', 'icon' => 'clipboard-document', 'match' => ['licences.*']],
                    ['label' => 'Sell models', 'route' => 'seller.index', 'icon' => 'banknotes', 'match' => ['seller.index', 'seller.apply'], 'notSeller' => true],
                    ['label' => 'My store', 'route' => 'seller.store.edit', 'icon' => 'tag', 'match' => ['seller.store.*'], 'section' => 'Selling', 'seller' => true],
                    ['label' => 'My models', 'route' => 'seller.models.index', 'icon' => 'archive-box', 'match' => ['seller.models.*'], 'section' => 'Selling', 'seller' => true],
                    ['label' => 'Earnings', 'route' => 'earnings.index', 'icon' => 'cash', 'match' => ['earnings.*'], 'section' => 'Selling', 'seller' => true, 'crumbFor' => 'sellers'],
                ],
            ],
            [
                'label' => 'Projects',
                'items' => [
                    ['label' => 'Browse projects', 'route' => 'jobs.browse', 'icon' => 'magnifying-glass', 'match' => ['jobs.index', 'jobs.browse', 'jobs.apply']],
                    ['label' => 'My applications', 'route' => 'applications.my', 'icon' => 'document-text', 'match' => ['applications.my', 'applications.show', 'applications.archived', 'applications.drafts', 'applications.continue']],
                    ['label' => 'Post a project', 'route' => 'jobs.create', 'icon' => 'plus', 'match' => ['jobs.create']],
                    ['label' => 'Posted projects', 'route' => 'my-jobs.index', 'icon' => 'briefcase', 'match' => ['my-jobs.index', 'my-jobs.applications.*', 'my-jobs.archived.*', 'jobs.show', 'jobs.edit']],
                    ['label' => 'Engagements', 'route' => 'engagements.index', 'icon' => 'chat-bubble-left-right', 'match' => ['engagements.*'], 'except' => ['engagements.policy']],
                    // Freelancers who have been paid for a job, and are not sellers, find their earnings here
                    ['label' => 'Earnings', 'route' => 'earnings.index', 'icon' => 'cash', 'match' => ['earnings.*'], 'notSeller' => true, 'earner' => true, 'crumbFor' => 'others'],
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
                    ['label' => 'Payments & earnings', 'route' => 'policies.payments', 'icon' => 'cash', 'match' => ['policies.payments'], 'hidden' => true],
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
