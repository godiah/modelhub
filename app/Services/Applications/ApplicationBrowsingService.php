<?php

// Handle filtering and listing of applications

namespace App\Services\Applications;

use App\Enums\ApplicationStatus;
use App\Models\ApplicantMessage;
use App\Models\JobApplication;
use App\Models\JobReview;
use App\Models\ModelJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;

class ApplicationBrowsingService
{
    // Get applications for a specific job, with what the poster needs to compare bids
    public function getJobApplications(string $slug, array $filters): array
    {
        // Check if the job belongs to the authenticated user
        $job = ModelJob::where('slug', $slug)
            ->where('user_id', Auth::id())
            ->with('engagements')
            ->firstOrFail();

        $submitted = fn () => $job->applications()->where('status', '!=', ApplicationStatus::Draft);

        // Build the filtered, sorted list
        $query = $submitted()->with(['applicant.profile', 'engagement']);

        if (! empty($filters['search'])) {
            $this->applySearchFilter($query, $filters['search']);
        }

        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        match ($filters['sort'] ?? 'date_desc') {
            'date_asc' => $query->oldest(),
            'offer_high' => $query->orderByDesc('offer_amount')->latest(),
            'offer_low' => $query->orderBy('offer_amount')->latest(),
            default => $query->latest(),
        };

        $applications = $query->paginate(10)->withQueryString();

        // Status pill counts and bid statistics cover every application, not just the filtered page
        $counts = $submitted()->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $bids = $submitted()->toBase()
            ->where('status', '!=', ApplicationStatus::Withdrawn)
            ->whereNotNull('offer_amount')
            ->selectRaw('min(offer_amount) as low, avg(offer_amount) as mean, max(offer_amount) as high')
            ->first();

        // Average public rating and review count for the applicants on this page, in one query
        $ratings = JobReview::whereIn('reviewee_id', $applications->pluck('applicant_id'))
            ->public()
            ->selectRaw('reviewee_id, avg(rating) as average, count(*) as total')
            ->groupBy('reviewee_id')
            ->get()
            ->keyBy('reviewee_id');

        return [
            'job' => $job,
            'applications' => $applications,
            'hasFilters' => ! empty($filters['search']) || $filters['status'] !== 'all',
            'filters' => $filters,
            'counts' => ['all' => (int) $counts->sum()] + $counts->map(fn ($n) => (int) $n)->all(),
            'bids' => $bids && $bids->low !== null ? ['low' => (float) $bids->low, 'mean' => (float) $bids->mean, 'high' => (float) $bids->high] : null,
            'ratings' => $ratings,
            'filled' => $job->hasActiveHire(),
        ];
    }

    // Get application details with reviews and stats
    public function getApplicationDetails(JobApplication $application): array
    {
        $application->loadMissing(['applicant.profile', 'job.engagements', 'engagement']);

        // Get reviews for this applicant
        $reviews = JobReview::where('reviewee_id', $application->applicant_id)
            ->with(['reviewer', 'engagement.application.job'])
            ->public()
            ->latest()
            ->paginate(5);

        // Calculate stats
        $totalReviews = JobReview::where('reviewee_id', $application->applicant_id)
            ->public()
            ->count();

        $averageRating = JobReview::where('reviewee_id', $application->applicant_id)
            ->public()
            ->avg('rating') ?? 0;

        // Find top skill/tag
        $topSkill = $this->getTopSkill($application->applicant_id, $totalReviews);

        return [
            'application' => $application,
            'job' => $application->job,
            'reviews' => $reviews,
            'totalReviews' => $totalReviews,
            'averageRating' => $averageRating,
            'topSkill' => $topSkill,
            'ratingFilter' => 'all',
            'sentMessages' => ApplicantMessage::where('job_application_id', $application->id)->latest()->take(5)->get(),
        ];
    }

    // Get the most frequently mentioned skill/tag for an applicant
    protected function getTopSkill(int $applicantId, int $totalReviews): ?string
    {
        if ($totalReviews === 0) {
            return null;
        }

        $allTags = JobReview::where('reviewee_id', $applicantId)
            ->public()
            ->get()
            ->pluck('tags')
            ->flatten()
            ->filter();

        $tagCounts = collect();
        foreach ($allTags as $tag) {
            $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
        }

        return $tagCounts->count() > 0 ? $tagCounts->sortDesc()->keys()->first() : null;
    }

    // Apply search filter to applications query
    protected function applySearchFilter(Builder|Relation $query, string $search): void
    {
        $query->whereHas('applicant', function ($q) use ($search) {
            $q->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%');
        });
    }
}
