<?php

// JobManagementService handles CRUD operations for job postings, including creation, updates, and retrieval.
// This service integrates image handling, slug generation, and user authorization to manage job data,
// ensuring database consistency, proper notifications, and access control for job-related actions.
// Service also handle retrieving a user posted jobs, archiving & unarchiving user posted jobs too.

namespace App\Services\Jobs;

use App\Enums\ApplicationStatus;
use App\Enums\EngagementStatus;
use App\Helpers\Jobs\JobCacheHelper;
use App\Models\JobApplication;
use App\Models\JobEngagement;
use App\Models\ModelJob;
use App\Models\Skill;
use App\Models\Software;
use App\Notifications\JobPostedNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JobManagementService
{
    protected JobImageService $imageService;

    protected JobSlugService $slugService;

    protected JobCacheHelper $cacheHelper;

    public function __construct(JobImageService $imageService, JobSlugService $slugService, JobCacheHelper $cacheHelper)
    {
        $this->imageService = $imageService;
        $this->slugService = $slugService;
        $this->cacheHelper = $cacheHelper;
    }

    // Get data for creating a new job (skills & software)
    public function getNewJobData(): array
    {
        return [
            'skills' => Skill::where('is_active', true)->get(),
            'software' => Software::where('is_active', true)->get(),
        ];
    }

    // Store a new job
    public function store(Request $request, array $validatedData): ModelJob
    {
        // Handle main image upload
        $imagePath = $this->imageService->handleMainImage($request);
        if ($imagePath) {
            $validatedData['image'] = $imagePath;
        }

        // Generate unique slug
        $slug = $this->slugService->generateUniqueSlug($validatedData['title']);

        $job = DB::transaction(function () use ($request, $validatedData, $slug) {
            // Create the job associated with the authenticated user
            $job = Auth::user()->jobs()->create([
                'title' => $validatedData['title'],
                'slug' => $slug,
                'description' => $validatedData['description'] ?? null,
                'skills' => $validatedData['skills'],
                'software' => $validatedData['software'],
                'images' => $validatedData['image'] ?? null,
                'deadline' => $validatedData['deadline'],
                'no_deadline' => $validatedData['no_deadline'],
                'budget' => $validatedData['budget'],
            ]);

            // Handle additional images
            $this->imageService->handleAdditionalImages($request, $job);

            // Send notification
            Auth::user()->notify(new JobPostedNotification($job));

            return $job;
        });

        return $job;
    }

    // Get data for editing a job
    public function getEditJobData(ModelJob $job): array
    {
        return [
            'job' => $job,
            'skills' => Skill::where('is_active', true)->get(),
            'software' => Software::where('is_active', true)->get(),
        ];
    }

    // Update a job: brief, budget, deadline, open/closed switch and images, together or not at all
    public function update(ModelJob $job, array $updateData, ?Request $request = null, array $removedImageIds = []): ModelJob
    {
        DB::transaction(function () use ($job, $updateData, $request, $removedImageIds) {
            $job->update($updateData);

            if ($request) {
                $this->imageService->replaceMainImage($request, $job);
                $this->imageService->removeAdditionalImages($job, $removedImageIds);
                $this->imageService->handleAdditionalImages($request, $job);
            }
        });

        // The slug stays as it was, so links already shared keep working; suggestions built from the old
        // brief are stale now.
        $this->cacheHelper->clearJobCache($job);

        return $job;
    }

    // Check if a job title exists
    public function titleExists(string $title): bool
    {
        return ModelJob::where('title', $title)->exists();
    }

    // Get user's posted jobs with filtering and sorting
    public function getUserPostedJobs(array $filters): LengthAwarePaginator
    {
        $query = ModelJob::where('user_id', Auth::id())
            ->unarchived()
            ->with('engagements')
            ->withCount(['applications as new_applications_count' => fn ($q) => $q->where('status', ApplicationStatus::Submitted)]);

        if (! empty($filters['search'])) {
            $query->where('title', 'like', '%'.$filters['search'].'%');
        }

        // "active" = open for applications right now, "closed" = everything else that is not archived
        match ($filters['status']) {
            'active' => $query->openForApplications(),
            'closed', 'inactive' => $query->notOpenForApplications(),
            default => null,
        };

        // Apply sorting
        $this->applySortingToPostedJobs($query, $filters['sort']);

        return $query->paginate(10)->withQueryString();
    }

    // Who applied to each listed project (latest first, drafts excluded), for the list's detail panel
    public function getPostedJobInsights(array $jobs): array
    {
        $ids = collect($jobs)->pluck('id')->all();

        if ($ids === []) {
            return [];
        }

        return JobApplication::whereIn('job_id', $ids)
            ->where('poster_id', Auth::id())
            ->where('status', '!=', ApplicationStatus::Draft)
            ->with('applicant.profile')
            ->latest()
            ->get()
            ->groupBy('job_id')
            ->all();
    }

    // Headline numbers across all the poster's projects
    public function getPostedJobStats(): array
    {
        $engagements = fn (array $statuses) => JobEngagement::whereHas('application', fn ($q) => $q->where('poster_id', Auth::id()))
            ->whereIn('status', $statuses)
            ->count();

        return [
            'open' => ModelJob::where('user_id', Auth::id())->unarchived()->openForApplications()->count(),
            'new_applications' => JobApplication::where('poster_id', Auth::id())
                ->where('status', ApplicationStatus::Submitted)
                ->whereHas('job', fn ($q) => $q->unarchived())
                ->count(),
            'in_progress' => $engagements([EngagementStatus::Active, EngagementStatus::Disputed]),
            'completed' => $engagements([EngagementStatus::Completed]),
        ];
    }

    // Tab counts for the poster's project list (archived projects live on their own page)
    public function getPostedJobCounts(): array
    {
        $mine = fn () => ModelJob::where('user_id', Auth::id())->unarchived();

        return [
            'all' => $mine()->count(),
            'active' => $mine()->openForApplications()->count(),
            'closed' => $mine()->notOpenForApplications()->count(),
        ];
    }

    // Everything the poster's project page shows besides the project itself: who applied, and the hire if any
    public function getPosterOverview(ModelJob $job): array
    {
        $applications = $job->applications()
            ->where('status', '!=', ApplicationStatus::Draft)
            ->with(['applicant.profile', 'engagement'])
            ->latest()
            ->get();

        return [
            'applications' => $applications,
            'recent' => $applications->take(4),
            'counts' => [
                'total' => $applications->count(),
                'new' => $applications->where('status', ApplicationStatus::Submitted)->count(),
                'hired' => $applications->where('status', ApplicationStatus::Hired)->count(),
            ],
            'engagement' => $job->engagements()->latest('job_engagements.id')->first(),
        ];
    }

    // Apply sorting to posted jobs query
    protected function applySortingToPostedJobs(Builder $query, string $sort): void
    {
        switch ($sort) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'deadline':
                $query->orderBy('deadline', 'asc');
                break;
            case 'budget_high':
                $query->orderBy('budget', 'desc');
                break;
            case 'budget_low':
                $query->orderBy('budget', 'asc');
                break;
            default:
                $query->latest();
        }
    }

    // Archive a job. Returns null once it is archived, otherwise why it was refused.
    public function archiveJob(ModelJob $job): ?string
    {
        if ($job->user_id !== Auth::id()) {
            return 'Unauthorized Action';
        }

        $job->loadMissing('engagements');

        if (! $job->canBeArchived()) {
            return $job->isOpenForApplications()
                ? 'Close this project before archiving it. You can do that from Edit.'
                : 'A freelancer is working on this project, so it cannot be archived yet.';
        }

        $job->archive();

        return null;
    }

    // Restore an archived job. Returns null once restored, otherwise why it was refused.
    public function restoreJob(ModelJob $job): ?string
    {
        if ($job->user_id !== Auth::id()) {
            return 'Unauthorized Action';
        }

        if (! $job->is_archived) {
            return 'This project is not archived.';
        }

        $job->unarchive();

        return null;
    }

    // The poster's archived projects, newest first, with a real (non-draft) application count
    public function getArchivedJobs(?string $search = null): LengthAwarePaginator
    {
        return ModelJob::where('user_id', Auth::id())
            ->archived()
            ->when($search, fn ($query) => $query->where('title', 'like', '%'.$search.'%'))
            ->withCount(['applications as applications_count' => fn ($q) => $q->where('status', '!=', ApplicationStatus::Draft)])
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    // Everything the archived project's page shows: its applications, latest first, drafts excluded
    public function getArchivedJobOverview(ModelJob $job): array
    {
        $job->load('jobImages');

        return [
            'applications' => $job->applications()
                ->where('status', '!=', ApplicationStatus::Draft)
                ->with('applicant.profile')
                ->latest()
                ->get(),
        ];
    }
}
