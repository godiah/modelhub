<?php

namespace App\Support\Navigation;

use App\Enums\DisputeStatus;
use App\Enums\PayoutStatus;
use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Models\JobPaymentDispute;
use App\Models\Payout;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\SellerProfile;
use App\Models\Staff;
use App\Support\Staff\StaffAccess;

/**
 * The staff portal's navigation: grouped by function, limited to what the signed-in staff member may do, with a live
 * count of what is waiting in each queue. The member app's SidebarMenu is separate: staff never see member pages.
 */
final class StaffMenu
{
    /**
     * @return list<array{label: string, items: list<array{label: string, route: string, icon: string, active: bool, badge: int}>}>
     */
    public static function for(Staff $staff): array
    {
        $groups = [];

        foreach (self::definition() as $group) {
            $items = [];

            foreach ($group['items'] as $item) {
                if (isset($item['can']) && ! $staff->can($item['can'])) {
                    continue;
                }

                if (! empty($item['super']) && ! $staff->hasRole(StaffAccess::SUPER_ADMIN)) {
                    continue;
                }

                $items[] = [
                    'label' => $item['label'],
                    'route' => $item['route'],
                    'icon' => $item['icon'],
                    'active' => request()->routeIs(...$item['match']),
                    'badge' => isset($item['badge']) ? self::count($item['badge'], $staff) : 0,
                ];
            }

            if ($items !== []) {
                $groups[] = ['label' => $group['label'], 'items' => $items];
            }
        }

        return $groups;
    }

    /**
     * The trail shown in the top bar: the menu group, the menu page we are under, then the page itself when it is a detail page.
     *
     * @param  list<array{label: string, items: list<array<string, mixed>>}>  $menu  the result of for()
     * @return list<array{label: string, url: ?string}>
     */
    public static function breadcrumb(array $menu, ?string $title = null): array
    {
        foreach ($menu as $group) {
            foreach ($group['items'] as $item) {
                if (! $item['active']) {
                    continue;
                }

                $onListPage = request()->routeIs($item['route']);
                $crumbs = [['label' => $group['label'], 'url' => null], ['label' => $item['label'], 'url' => $onListPage ? null : route($item['route'])]];

                if (! $onListPage && $title && $title !== $item['label']) {
                    $crumbs[] = ['label' => $title, 'url' => null];
                }

                return $crumbs;
            }
        }

        return [['label' => $title ?: 'Staff portal', 'url' => null]];
    }

    /** What is waiting, per queue. Used by the menu badges and the dashboard. */
    public static function count(string $queue, ?Staff $staff = null): int
    {
        return match ($queue) {
            'models' => Product::where('status', ProductStatus::InReview)->count(),
            'sellers' => SellerProfile::where('status', SellerStatus::Pending)->count(),
            'reports' => ProductReview::visible()->whereHas('reports', fn ($reports) => $reports->where('status', 'open'))->count(),
            'disputes' => JobPaymentDispute::whereIn('status', [DisputeStatus::Pending, DisputeStatus::UnderReview])->count(),
            'payouts' => Payout::where('status', PayoutStatus::Requested)->count(),
            'notifications' => $staff?->unreadNotifications()->count() ?? 0,
            default => 0,
        };
    }

    /** @return list<array{label: string, items: list<array<string, mixed>>}> */
    private static function definition(): array
    {
        return [
            [
                'label' => 'Overview',
                'items' => [
                    ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'squares-2x2', 'match' => ['admin.dashboard']],
                    ['label' => 'Platform overview', 'route' => 'admin.overview', 'icon' => 'chart-bar', 'match' => ['admin.overview'], 'can' => 'view platform overview'],
                    ['label' => 'Notifications', 'route' => 'admin.notifications.index', 'icon' => 'bell', 'match' => ['admin.notifications.*'], 'badge' => 'notifications'],
                ],
            ],
            [
                'label' => 'Moderation',
                'items' => [
                    ['label' => 'Model reviews', 'route' => 'admin.models.index', 'icon' => 'clipboard-check', 'match' => ['admin.models.*'], 'can' => 'review models', 'badge' => 'models'],
                    ['label' => 'Seller applications', 'route' => 'admin.sellers.index', 'icon' => 'clipboard-list', 'match' => ['admin.sellers.*'], 'can' => 'review sellers', 'badge' => 'sellers'],
                    ['label' => 'Review reports', 'route' => 'admin.reviews.index', 'icon' => 'flag', 'match' => ['admin.reviews.*'], 'can' => 'moderate reviews', 'badge' => 'reports'],
                ],
            ],
            [
                'label' => 'Platform',
                'items' => [
                    ['label' => 'Members', 'route' => 'admin.members.index', 'icon' => 'user-group', 'match' => ['admin.members.*'], 'can' => 'view members'],
                    ['label' => 'Projects', 'route' => 'admin.projects.index', 'icon' => 'briefcase', 'match' => ['admin.projects.*'], 'can' => 'view projects'],
                    ['label' => 'Hires', 'route' => 'admin.engagements.index', 'icon' => 'chat-bubble-left-right', 'match' => ['admin.engagements.*'], 'can' => 'view engagements'],
                    ['label' => 'All models', 'route' => 'admin.catalogue.index', 'icon' => 'cube', 'match' => ['admin.catalogue.*'], 'can' => 'view models'],
                    ['label' => 'All stores', 'route' => 'admin.stores.index', 'icon' => 'tag', 'match' => ['admin.stores.*'], 'can' => 'view sellers'],
                ],
            ],
            [
                'label' => 'Payments',
                'items' => [
                    ['label' => 'Payment disputes', 'route' => 'admin.disputes.index', 'icon' => 'scale', 'match' => ['admin.disputes.*'], 'can' => 'view disputes', 'badge' => 'disputes'],
                    ['label' => 'Payouts', 'route' => 'admin.payouts.index', 'icon' => 'cash', 'match' => ['admin.payouts.*'], 'can' => 'view payouts', 'badge' => 'payouts'],
                ],
            ],
            [
                'label' => 'Access',
                'items' => [
                    ['label' => 'Staff', 'route' => 'admin.staff.index', 'icon' => 'users', 'match' => ['admin.staff.*'], 'can' => 'manage staff'],
                    ['label' => 'Roles', 'route' => 'admin.roles.index', 'icon' => 'shield-check', 'match' => ['admin.roles.*'], 'can' => 'manage roles'],
                ],
            ],
            [
                'label' => 'System',
                'items' => [
                    ['label' => 'Activity log', 'route' => 'admin.activity.index', 'icon' => 'document-text', 'match' => ['admin.activity.*'], 'can' => 'view audit log'],
                    ['label' => 'Settings', 'route' => 'admin.settings.security', 'icon' => 'cog-6-tooth', 'match' => ['admin.settings.*'], 'super' => true],
                ],
            ],
        ];
    }
}
