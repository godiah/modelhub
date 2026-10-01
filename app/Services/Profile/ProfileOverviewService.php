<?php

namespace App\Services\Profile;

use App\Enums\EngagementStatus;
use App\Enums\SellerStatus;
use App\Models\JobEngagement;
use App\Models\JobReview;
use App\Models\SellerProfile;
use App\Models\User;
use App\Models\UserSocialLink;
use App\Support\Profile\ProfileCompleteness;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Read-only summary of everything the user has shared on their profile. Everything is eager-loaded:
 * lazy loading is blocked outside production.
 */
class ProfileOverviewService
{
    /**
     * @return array<string, mixed>
     */
    public function overview(User $user): array
    {
        $user->load([
            'profile',
            'skills' => fn ($query) => $query->orderBy('name'),
            'software' => fn ($query) => $query->orderBy('name'),
        ]);

        return [
            'user' => $user,
            'profile' => $user->profile,
            'skills' => $user->skills,
            'software' => $user->software,
            'socialLinks' => $this->socialLinks($user),
            'completeness' => ProfileCompleteness::for($user),
            'stats' => $this->stats($user),
            'history' => $this->workHistory($user),
            'ratings' => $this->ratings($user),
            'store' => SellerProfile::where('user_id', $user->id)->where('status', SellerStatus::Approved)->first(),
            'reviews' => $this->recentReviews($user),
        ];
    }

    /**
     * Headline numbers: what the user has delivered, earned and has in flight.
     *
     * @return array{completed: int, earned: float, active: int}
     */
    private function stats(User $user): array
    {
        $completed = JobEngagement::where('status', EngagementStatus::Completed)->forApplicant($user->id);

        return [
            'completed' => (clone $completed)->count(),
            'earned' => (float) (clone $completed)->sum('net_amount'),
            'active' => JobEngagement::activeForUser($user->id)->where('status', EngagementStatus::Active)->count(),
        ];
    }

    /**
     * Most recent completed projects (as the freelancer) with the rating the client left, if any.
     *
     * @return list<array{title: string, client: string|null, completed_at: Carbon, amount: float, rating: int|null}>
     */
    private function workHistory(User $user): array
    {
        $engagements = JobEngagement::with(['application.job', 'application.poster'])
            ->where('status', EngagementStatus::Completed)
            ->forApplicant($user->id)
            ->latest('completed_at')
            ->limit(5)
            ->get();

        $ratings = JobReview::where('reviewee_id', $user->id)
            ->whereIn('engagement_id', $engagements->modelKeys())
            ->pluck('rating', 'engagement_id');

        return $engagements->map(fn (JobEngagement $engagement) => [
            'title' => $engagement->application->job->title,
            'client' => $engagement->application->poster?->name,
            'completed_at' => $engagement->completed_at ?? $engagement->updated_at,
            'amount' => (float) $engagement->net_amount,
            'rating' => $ratings[$engagement->id] ?? null,
        ])->all();
    }

    /**
     * Public links on active networks, in the order the user arranged them.
     *
     * @return Collection<int, UserSocialLink>
     */
    private function socialLinks(User $user): Collection
    {
        return $user->publicSocialLinks()
            ->with(['socialNetwork' => fn ($query) => $query->active()->ordered()])
            ->get()
            ->filter(fn ($link) => $link->socialNetwork !== null)
            ->values();
    }

    /**
     * @return array{count: int, average: float|null, distribution: array<int, int>}
     */
    private function ratings(User $user): array
    {
        $counts = JobReview::where('reviewee_id', $user->id)
            ->where('is_public', true)
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $count = (int) $counts->sum();
        $weighted = $counts->reduce(fn (int $carry, int $total, int $rating) => $carry + $rating * $total, 0);

        return [
            'count' => $count,
            'average' => $count > 0 ? round($weighted / $count, 1) : null,
            'distribution' => collect([5, 4, 3, 2, 1])->mapWithKeys(fn (int $stars) => [$stars => (int) ($counts[$stars] ?? 0)])->all(),
        ];
    }

    /**
     * @return Collection<int, JobReview>
     */
    private function recentReviews(User $user): Collection
    {
        return JobReview::with([
            'reviewer' => fn ($query) => $query->select('id', 'name', 'avatar'),
            'engagement' => fn ($query) => $query->select('id', 'application_id', 'created_at'),
            'engagement.job' => fn ($query) => $query->select('model_jobs.id', 'model_jobs.title'),
        ])
            ->where('reviewee_id', $user->id)
            ->where('is_public', true)
            ->latest()
            ->limit(5)
            ->get();
    }
}
