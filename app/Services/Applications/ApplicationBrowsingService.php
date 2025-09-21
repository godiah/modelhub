<?php

// Handle filtering and listing of applications

namespace App\Services\Applications;

use App\Models\JobApplication;
use App\Models\JobReview;
use App\Models\ModelJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ApplicationBrowsingService
{
    // Get applications for a specific job
    public function getJobApplications(string $slug, array $filters): array
    {
        // Check if the job belongs to the authenticated user
        $job = ModelJob::where('slug', $slug)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Build the base query
        $query = $job->applications()->with('applicant');

        // Apply search filter
        if (!empty($filters['search'])) {
            $this->applySearchFilter($query, $filters['search']);
        }

        // Apply status filter
        if ($filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Get paginated results
        $applications = $query->where('status', '!=', 'draft')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Check if filters are active
        $hasFilters = !empty($filters['search']) || $filters['status'] !== 'all';

        return [
            'job' => $job,
            'applications' => $applications,
            'hasFilters' => $hasFilters
        ];
    }

    // Get application details with reviews and stats
    public function getApplicationDetails(JobApplication $application): array
    {
        // Ensure the current user is the owner of this job posting
        if (Auth::user()->id !== $application->job->user_id) {
            abort(403, 'Unauthorized action.');
        }

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
            'ratingFilter' => 'all'
        ];
    }

    // Check authorization for application viewing
    public function authorizeApplicationView(JobApplication $application): bool
    {
        return Auth::user()->id === $application->job->user_id;
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
    protected function applySearchFilter(Builder $query, string $search): void
    {
        $query->whereHas('applicant', function ($q) use ($search) {
            $q->where('name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%');
        });
    }
}
