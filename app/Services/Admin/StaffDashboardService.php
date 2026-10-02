<?php

namespace App\Services\Admin;

use App\Enums\DisputeStatus;
use App\Enums\EngagementStatus;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Models\JobEngagement;
use App\Models\JobPaymentDispute;
use App\Models\ModelJob;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ReviewReport;
use App\Models\SellerProfile;
use App\Models\Staff;
use App\Models\StaffActivity;
use App\Models\User;
use App\Models\WishlistItem;
use App\Support\Money;
use App\Support\Navigation\StaffMenu;
use App\Support\Settings\PlatformSettings;
use App\Support\Staff\StaffAccess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Builds the staff dashboard, block by block, from what the signed-in staff member is allowed to see. A block the person has
 * no permission for is not built at all (so nothing is queried for it and nothing leaks into the page).
 */
class StaffDashboardService
{
    /** An item that has waited this many days turns amber, and red at the second number. */
    public const AMBER_DAYS = 3;

    public const RED_DAYS = 7;

    /** How many items of each queue the attention list shows. */
    private const PER_QUEUE = 5;

    /** Activity that is not a decision: sign-ins and the like are left out of "your work" and team numbers. */
    private const NOT_DECISIONS = 'staff.%';

    public function __construct(protected PlatformStatsService $stats) {}

    /** @return array<string, mixed> */
    public function for(Staff $staff): array
    {
        $attention = $this->attention($staff);

        return [
            'attention' => $attention,
            'work' => $this->myWork($staff),
            'notifications' => $staff->notifications()->limit(5)->get(),
            'pulse' => $staff->can('view platform overview') ? $this->pulse() : null,
            'feeds' => $this->feeds($staff),
            'activity' => $staff->can('view audit log') ? StaffActivity::with('staff:id,name,avatar')->where('action', 'not like', self::NOT_DECISIONS)->latest('id')->limit(4)->get() : null,
            'team' => $this->team($staff),
        ];
    }

    /** Colour for how long something has waited. */
    public static function tone(?Carbon $since): string
    {
        $days = $since ? $since->diffInDays(now()) : 0;

        return $days >= self::RED_DAYS ? 'red' : ($days >= self::AMBER_DAYS ? 'amber' : 'neutral');
    }

    /* ---------------------------------------------------------------- needs attention */

    /**
     * The actual items waiting on this person, oldest first, with how many are overdue.
     *
     * @return array{items: list<array<string, mixed>>, queues: list<array<string, mixed>>, total: int, overdue: int}
     */
    private function attention(Staff $staff): array
    {
        $limit = now()->subDays(self::AMBER_DAYS);
        $items = collect();
        $queues = [];
        $overdue = 0;

        if ($staff->can('review models')) {
            $count = StaffMenu::count('models');
            $late = Product::where('status', ProductStatus::InReview)->where('submitted_at', '<=', $limit)->count();
            $queues[] = ['key' => 'models', 'label' => 'Models', 'title' => 'Models to review', 'icon' => 'clipboard-check', 'count' => $count, 'late' => $late, 'oldest' => $this->oldest(Product::where('status', ProductStatus::InReview)->min('submitted_at')), 'url' => route('admin.models.index')];
            $overdue += $late;

            Product::with('sellerProfile:id,user_id,display_name')->where('status', ProductStatus::InReview)->orderBy('submitted_at')->limit(self::PER_QUEUE)->get()
                ->each(fn (Product $product) => $items->push($this->item('clipboard-check', 'Model to review', $product->title, ($product->sellerProfile?->display_name ?? __('A seller')).' · '.($product->isFree() ? __('Free') : Money::formatMinor($product->price_minor, 0)), $product->submitted_at, route('admin.models.index'))));
        }

        if ($staff->can('review sellers')) {
            $count = StaffMenu::count('sellers');
            $late = SellerProfile::where('status', SellerStatus::Pending)->where('submitted_at', '<=', $limit)->count();
            $queues[] = ['key' => 'sellers', 'label' => 'Applications', 'title' => 'Seller applications', 'icon' => 'clipboard-list', 'count' => $count, 'late' => $late, 'oldest' => $this->oldest(SellerProfile::where('status', SellerStatus::Pending)->min('submitted_at')), 'url' => route('admin.sellers.index')];
            $overdue += $late;

            SellerProfile::with('user:id,name')->where('status', SellerStatus::Pending)->orderBy('submitted_at')->limit(self::PER_QUEUE)->get()
                ->each(fn (SellerProfile $store) => $items->push($this->item('clipboard-list', 'Seller application', $store->display_name, __('Applied by :name', ['name' => $store->user?->name ?? __('a deleted account')]), $store->submitted_at, route('admin.sellers.index', ['status' => 'pending']))));
        }

        if ($staff->can('moderate reviews')) {
            $open = fn ($reports) => $reports->where('status', 'open');
            $count = StaffMenu::count('reports');
            $late = ProductReview::visible()->whereHas('reports', fn ($r) => $open($r)->where('created_at', '<=', $limit))->count();
            $queues[] = ['key' => 'reports', 'label' => 'Reports', 'title' => 'Reported reviews', 'icon' => 'flag', 'count' => $count, 'late' => $late, 'oldest' => $this->oldest(ReviewReport::where('status', 'open')->min('created_at')), 'url' => route('admin.reviews.index')];
            $overdue += $late;

            ProductReview::visible()->with('product:id,title')
                ->withCount(['reports as open_reports_count' => fn ($r) => $r->where('status', 'open')])
                ->withMin(['reports as first_report_at' => fn ($r) => $r->where('status', 'open')], 'created_at')
                ->whereHas('reports', fn ($r) => $open($r))
                ->orderBy('first_report_at')->limit(self::PER_QUEUE)->get()
                ->each(fn (ProductReview $review) => $items->push($this->item('flag', 'Reported review', __('Review of :model', ['model' => $review->product->title]), trans_choice(':count report|:count reports', $review->open_reports_count, ['count' => $review->open_reports_count]).' · '.Str::limit($review->comment, 70), Carbon::parse($review->first_report_at), route('admin.reviews.index'))));
        }

        if ($staff->can('view disputes')) {
            $count = StaffMenu::count('disputes');
            $late = JobPaymentDispute::whereIn('status', [DisputeStatus::Pending, DisputeStatus::UnderReview])->where('created_at', '<=', $limit)->count();
            $queues[] = ['key' => 'disputes', 'label' => 'Disputes', 'title' => 'Payment disputes', 'icon' => 'scale', 'count' => $count, 'late' => $late, 'oldest' => $this->oldest(JobPaymentDispute::whereIn('status', [DisputeStatus::Pending, DisputeStatus::UnderReview])->min('created_at')), 'url' => route('admin.disputes.index')];
            $overdue += $late;

            JobPaymentDispute::with(['cancellation.engagement.application.job:id,title', 'assignedAdmin:id,name'])
                ->whereIn('status', [DisputeStatus::Pending, DisputeStatus::UnderReview])->orderBy('created_at')->limit(self::PER_QUEUE)->get()
                ->each(function (JobPaymentDispute $dispute) use ($items, $staff) {
                    $handler = $dispute->assignedAdmin ? ($dispute->assignedAdmin->is($staff) ? __('You are handling it') : __(':name is handling it', ['name' => $dispute->assignedAdmin->name])) : __('Nobody has taken it on');
                    $amount = $dispute->cancellation->partial_payment_amount;

                    $items->push($this->item('scale', 'Payment dispute', $dispute->cancellation->engagement->application->job->title, $handler.($amount !== null ? ' · '.Money::format($amount, 0) : ''), $dispute->created_at, route('admin.disputes.show', $dispute->cancellation_id), $dispute->assignedAdmin ? null : 'Unassigned'));
                });
        }

        if ($staff->can('refund payments')) {
            $count = StaffMenu::count('payments');
            $late = Payment::where('status', PaymentStatus::Review)->where('completed_at', '<=', $limit)->count();
            $queues[] = ['key' => 'payments', 'label' => 'Payments', 'title' => 'Payments to review', 'icon' => 'exclamation-triangle', 'count' => $count, 'late' => $late, 'oldest' => $this->oldest(Payment::where('status', PaymentStatus::Review)->min('completed_at')), 'url' => route('admin.payments.index', ['status' => 'review'])];
            $overdue += $late;

            Payment::with(['buyer:id,name', 'product:id,title'])->where('status', PaymentStatus::Review)->orderBy('completed_at')->limit(self::PER_QUEUE)->get()
                ->each(fn (Payment $payment) => $items->push($this->item('exclamation-triangle', 'Payment to review', $payment->product?->title ?? $payment->reference, ($payment->buyer?->name ?? __('A buyer')).' · '.Str::limit((string) $payment->failure_reason, 70), $payment->completed_at, route('admin.payments.show', $payment))));
        }

        if ($staff->can('approve payouts')) {
            $count = StaffMenu::count('payouts');
            $late = Payout::where('status', PayoutStatus::Requested)->where('created_at', '<=', $limit)->count();
            $queues[] = ['key' => 'payouts', 'label' => 'Payouts', 'title' => 'Withdrawals to approve', 'icon' => 'cash', 'count' => $count, 'late' => $late, 'oldest' => $this->oldest(Payout::where('status', PayoutStatus::Requested)->min('created_at')), 'url' => route('admin.payouts.index')];
            $overdue += $late;

            Payout::with('user:id,name')->where('status', PayoutStatus::Requested)->orderBy('created_at')->limit(self::PER_QUEUE)->get()
                ->each(fn (Payout $payout) => $items->push($this->item('cash', 'Withdrawal', $payout->user?->name ?? __('A member'), Money::formatMinor($payout->amount_minor, 0).' to M-Pesa', $payout->created_at, route('admin.payouts.index'))));
        }

        return [
            'items' => $items->sortBy(fn ($item) => $item['since']?->timestamp ?? PHP_INT_MAX)->take(12)->values()->all(),
            'queues' => $queues,
            'total' => (int) collect($queues)->sum('count'),
            'overdue' => $overdue,
        ];
    }

    private function oldest(mixed $date): ?Carbon
    {
        return $date ? Carbon::parse($date) : null;
    }

    private function item(string $icon, string $kind, string $title, string $detail, ?Carbon $since, string $url, ?string $tag = null): array
    {
        return ['icon' => $icon, 'kind' => $kind, 'title' => $title, 'detail' => $detail, 'since' => $since, 'tone' => self::tone($since), 'url' => $url, 'tag' => $tag];
    }

    /* ---------------------------------------------------------------- your work */

    /** @return array{disputes: Collection, week: array<string, int>, recent: Collection} */
    private function myWork(Staff $staff): array
    {
        // Two weeks of this person's decisions: this week for the numbers, last week for the trend
        $rows = StaffActivity::where('staff_id', $staff->id)->where('action', 'not like', self::NOT_DECISIONS)->where('created_at', '>=', today()->subDays(13))->get(['action', 'created_at']);
        $thisWeek = $rows->filter(fn ($row) => $row->created_at->gte(today()->subDays(6)));
        $perDay = $thisWeek->countBy(fn ($row) => $row->created_at->format('Y-m-d'));

        return [
            'disputes' => $staff->can('view disputes')
                ? JobPaymentDispute::where('admin_assigned', $staff->id)->where('status', DisputeStatus::UnderReview)->with('cancellation.engagement.application.job:id,title')->latest()->limit(5)->get()
                : collect(),
            'week' => $thisWeek->pluck('action')->countBy(fn ($action) => explode('.', $action)[0])->sortDesc()->all(),
            'total' => $thisWeek->count(),
            'previous' => $rows->count() - $thisWeek->count(),
            'daily' => collect(range(6, 0))->map(fn ($ago) => ['label' => today()->subDays($ago)->format('D'), 'count' => (int) ($perDay[today()->subDays($ago)->format('Y-m-d')] ?? 0), 'today' => $ago === 0])->all(),
            'recent' => StaffActivity::where('staff_id', $staff->id)->where('action', 'not like', self::NOT_DECISIONS)->latest('id')->limit(5)->get(),
        ];
    }

    /* ---------------------------------------------------------------- the platform */

    /** @return array<string, mixed> */
    private function pulse(): array
    {
        $stats = $this->stats->get();
        $change = function (array $trend): ?array {
            if ($trend['then'] === 0) {
                return $trend['now'] > 0 ? ['+'.$trend['now'], true] : null;
            }
            $pct = (int) round(($trend['now'] - $trend['then']) / $trend['then'] * 100);

            return [($pct >= 0 ? '+' : '').$pct.'%', $pct >= 0];
        };

        $weekly = fn (string $label, string $key) => ['label' => $label, 'points' => $stats['series'][$key]];

        return [
            'chart' => ['members' => $weekly('Members', 'members'), 'projects' => $weekly('Projects', 'projects'), 'models' => $weekly('Models', 'models'), 'applications' => $weekly('Applications', 'applications')],
            'tiles' => [
                ['label' => 'Members', 'value' => number_format($stats['members']['total']), 'icon' => 'user-group', 'hint' => __(':n today', ['n' => $stats['today']['members']]), 'trend' => $change($stats['members']['new']), 'series' => $stats['series']['members']],
                ['label' => 'Open projects', 'value' => number_format($stats['projects']['open']), 'icon' => 'briefcase', 'hint' => __(':n posted today', ['n' => $stats['today']['projects']]), 'trend' => $change($stats['projects']['new']), 'series' => $stats['series']['projects']],
                ['label' => 'Hires in progress', 'value' => number_format($stats['hires']['active']), 'icon' => 'chat-bubble-left-right', 'hint' => __(':n in dispute', ['n' => $stats['hires']['disputed']]), 'trend' => null, 'series' => null],
                ['label' => 'Live models', 'value' => number_format($stats['models']['live']), 'icon' => 'cube', 'hint' => __(':n listed today', ['n' => $stats['today']['models']]), 'trend' => $change($stats['models']['new']), 'series' => $stats['series']['models']],
                ['label' => 'In escrow', 'value' => Money::format($stats['hires']['escrow'], 0), 'icon' => 'banknotes', 'hint' => __('Funded, not yet released'), 'trend' => null, 'series' => null],
            ],
        ];
    }

    /* ---------------------------------------------------------------- role feeds */

    /** @return array<string, array<string, mixed>> */
    private function feeds(Staff $staff): array
    {
        $feeds = [];

        if ($staff->can('view members')) {
            $feeds['members'] = [
                'newest' => User::latest()->limit(5)->get(),
                'suspended' => User::whereNotNull('suspended_at')->with('suspendedBy:id,name')->latest('suspended_at')->limit(3)->get(),
                'unverified' => User::whereNull('email_verified_at')->count(),
            ];
        }

        if ($staff->can('view projects')) {
            $quiet = now()->subDays(7);
            $feeds['projects'] = [
                'newest' => ModelJob::with('user:id,name,avatar')->withCount('applications')->latest()->limit(5)->get(),
                'quiet' => ModelJob::openForApplications()->where('created_at', '<=', $quiet)->doesntHave('applications')->count(),
                'taken_down' => ModelJob::whereNotNull('taken_down_at')->count(),
            ];
        }

        if ($staff->can('view engagements')) {
            $feeds['hires'] = ['stuck' => $this->stuckHires(), 'late' => $this->lateHires()->count(), 'active' => JobEngagement::where('status', EngagementStatus::Active)->count()];
        }

        if ($staff->can('view models')) {
            $feeds['models'] = [
                'published' => Product::published()->with('sellerProfile:id,user_id,display_name', 'images')->latest('published_at')->limit(5)->get(),
                'saved' => $this->mostSaved(),
            ];
        }

        return $feeds;
    }

    /** Active hires with a deliverable still pending past its due date. */
    private function lateHires()
    {
        return JobEngagement::where('status', EngagementStatus::Active)
            ->whereHas('deliverables', fn ($d) => $d->where('status', 'pending')->whereNotNull('due_date')->where('due_date', '<', today()));
    }

    /** The worst of them first, with how many deliverables are overdue and how much of the work has been approved. @return Collection<int, JobEngagement> */
    private function stuckHires(): Collection
    {
        $overdue = fn ($d) => $d->where('status', 'pending')->whereNotNull('due_date')->where('due_date', '<', today());

        return $this->lateHires()
            ->with(['application.job:id,title', 'application.poster:id,name,avatar', 'application.applicant:id,name,avatar'])
            ->withCount(['deliverables as overdue_count' => $overdue, 'deliverables as total_deliverables', 'deliverables as approved_deliverables' => fn ($d) => $d->where('status', 'approved')])
            ->withMin(['deliverables as oldest_due' => $overdue], 'due_date')
            ->orderBy('oldest_due')->limit(5)->get();
    }

    /** Published models saved most in the last 7 days. @return Collection<int, Product> */
    private function mostSaved(): Collection
    {
        $saves = WishlistItem::where('created_at', '>=', now()->subDays(7))->selectRaw('product_id, count(*) as total')->groupBy('product_id')->orderByDesc('total')->limit(5)->pluck('total', 'product_id');

        return Product::published()->with('sellerProfile:id,user_id,display_name')->whereIn('products.id', $saves->keys())->get()
            ->each(fn (Product $product) => $product->setAttribute('week_saves', (int) $saves[$product->id]))
            ->sortByDesc('week_saves')->values();
    }

    /* ---------------------------------------------------------------- team and security */

    /** @return array<string, mixed>|null */
    private function team(Staff $staff): ?array
    {
        $canLog = $staff->can('view audit log');
        $canStaff = $staff->can('manage staff');

        if (! $canLog && ! $canStaff) {
            return null;
        }

        $team = ['canLog' => $canLog, 'canStaff' => $canStaff];

        if ($canLog) {
            $week = now()->subDays(7);
            $byPerson = StaffActivity::whereNotNull('staff_id')->where('action', 'not like', self::NOT_DECISIONS)->where('created_at', '>=', $week)
                ->selectRaw('staff_id, count(*) as total')->groupBy('staff_id')->orderByDesc('total')->limit(5)->pluck('total', 'staff_id');
            $people = Staff::whereIn('id', $byPerson->keys())->get(['id', 'name', 'avatar'])->keyBy('id');

            $team['decisions'] = $byPerson->map(fn ($total, $id) => ['staff' => $people[$id] ?? null, 'total' => (int) $total])->filter(fn ($row) => $row['staff'])->values();
            $team['failed_signins'] = StaffActivity::where('action', 'staff.sign-in-failed')->where('created_at', '>=', now()->subDay())->count();
            $team['active_today'] = Staff::where('last_login_at', '>=', today())->orderByDesc('last_login_at')->limit(8)->get(['id', 'name', 'avatar', 'last_login_at']);
        }

        if ($canStaff) {
            $team['no_role'] = Staff::where('is_active', true)->doesntHave('roles')->count();
            $team['never_signed_in'] = Staff::where('is_active', true)->whereNull('last_login_at')->count();
            $team['deactivated'] = Staff::where('is_active', false)->count();
            $team['active_staff'] = Staff::where('is_active', true)->count();
            $team['with_app'] = Staff::where('is_active', true)->whereNotNull('two_factor_confirmed_at')->count();
        }

        // What the platform currently enforces, for the people who can change it
        $team['posture'] = $staff->hasRole(StaffAccess::SUPER_ADMIN) ? [
            ['label' => 'Member sign-in codes', 'on' => PlatformSettings::bool('security.otp_members_required')],
            ['label' => 'Staff sign-in codes', 'on' => PlatformSettings::bool('security.otp_staff_required')],
            ['label' => 'One session per account', 'on' => PlatformSettings::bool('security.member_single_session') && PlatformSettings::bool('security.staff_single_session')],
        ] : null;

        return $team;
    }
}
