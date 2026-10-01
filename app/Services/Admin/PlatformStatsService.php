<?php

namespace App\Services\Admin;

use App\Enums\DisputeStatus;
use App\Enums\EngagementStatus;
use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Models\JobApplication;
use App\Models\JobEngagement;
use App\Models\JobPaymentDispute;
use App\Models\ModelJob;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/** Platform-wide numbers for the staff overview, cached for a few minutes so the page stays cheap. */
class PlatformStatsService
{
    public const WEEKS = 12;

    /** Bump the suffix when the shape of the numbers changes, so a deploy never reads the old shape from the cache. */
    public const CACHE_KEY = 'staff.platform-stats.v2';

    /** @return array<string, mixed> */
    public function get(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, fn () => $this->compute());
    }

    private function compute(): array
    {
        $week = now()->subDays(7);
        $before = now()->subDays(14);

        $trend = fn (int $now, int $then) => ['now' => $now, 'then' => $then];

        return [
            'members' => ['total' => User::count(), 'suspended' => User::whereNotNull('suspended_at')->count(), 'unverified' => User::whereNull('email_verified_at')->count(),
                'new' => $trend(User::where('created_at', '>=', $week)->count(), User::whereBetween('created_at', [$before, $week])->count()),
                'active' => User::where('last_login_at', '>=', $week)->count()],
            'sellers' => ['approved' => SellerProfile::where('status', SellerStatus::Approved)->count(), 'pending' => SellerProfile::where('status', SellerStatus::Pending)->count()],
            'projects' => ['open' => ModelJob::openForApplications()->count(), 'total' => ModelJob::count(), 'taken_down' => ModelJob::whereNotNull('taken_down_at')->count(),
                'new' => $trend(ModelJob::where('created_at', '>=', $week)->count(), ModelJob::whereBetween('created_at', [$before, $week])->count())],
            'applications' => $trend(JobApplication::where('created_at', '>=', $week)->count(), JobApplication::whereBetween('created_at', [$before, $week])->count()),
            'hires' => [
                'active' => JobEngagement::where('status', EngagementStatus::Active)->count(),
                'completed' => JobEngagement::where('status', EngagementStatus::Completed)->count(),
                'disputed' => JobEngagement::where('status', EngagementStatus::Disputed)->count(),
                'escrow' => (float) JobEngagement::whereNotNull('payment_escrowed_at')->whereNull('payment_released_at')->whereIn('status', [EngagementStatus::Active, EngagementStatus::Disputed])->sum('agreed_amount'),
            ],
            'models' => ['live' => Product::published()->count(), 'in_review' => Product::where('status', ProductStatus::InReview)->count(),
                'new' => $trend(Product::where('created_at', '>=', $week)->count(), Product::whereBetween('created_at', [$before, $week])->count())],
            'reviews' => $trend(ProductReview::where('created_at', '>=', $week)->count(), ProductReview::whereBetween('created_at', [$before, $week])->count()),
            'disputes' => ['open' => JobPaymentDispute::whereIn('status', [DisputeStatus::Pending, DisputeStatus::UnderReview])->count()],
            'today' => [
                'members' => User::where('created_at', '>=', today())->count(),
                'projects' => ModelJob::where('created_at', '>=', today())->count(),
                'models' => Product::where('created_at', '>=', today())->count(),
                'applications' => JobApplication::where('created_at', '>=', today())->count(),
            ],
            'series' => [
                'members' => $this->weekly(User::class),
                'projects' => $this->weekly(ModelJob::class),
                'models' => $this->weekly(Product::class),
                'applications' => $this->weekly(JobApplication::class),
            ],
        ];
    }

    /** New records per week, oldest first, for the last WEEKS weeks. @return list<array{label: string, count: int}> */
    private function weekly(string $model): array
    {
        $start = now()->startOfWeek()->subWeeks(self::WEEKS - 1);
        $counts = $model::where('created_at', '>=', $start)->pluck('created_at')->countBy(fn ($date) => $date->copy()->startOfWeek()->format('Y-m-d'));

        return collect(range(0, self::WEEKS - 1))->map(function ($i) use ($start, $counts) {
            $week = $start->copy()->addWeeks($i);

            return ['label' => $week->format('M j'), 'count' => (int) ($counts[$week->format('Y-m-d')] ?? 0)];
        })->all();
    }
}
