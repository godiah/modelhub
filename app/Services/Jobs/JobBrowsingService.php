<?php

// JobBrowsingService manages job listing, filtering, and sorting functionality.
// This service supports browsing jobs with search, skill, and software filters, as well as sorting options.
// It also provides functionality to retrieve similar jobs based on a given job, leveraging caching for performance.

namespace App\Services\Jobs;

use App\Helpers\Jobs\JobCacheHelper;
use App\Models\JobApplication;
use App\Models\ModelJob;
use App\Models\Skill;
use App\Models\Software;
use App\Models\User;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

class JobBrowsingService
{
    protected JobCacheHelper $cacheHelper;

    public function __construct(JobCacheHelper $cacheHelper)
    {
        $this->cacheHelper = $cacheHelper;
    }

    public const PER_PAGE = 10;

    // Browse jobs with filters and pagination
    public function browseJobs(array $filters, ?User $viewer = null): LengthAwarePaginator
    {
        // Browse lists only projects a freelancer can apply to right now — and never the viewer's own.
        $query = ModelJob::openForApplications()
            ->when($viewer, fn ($query) => $query->where('user_id', '!=', $viewer->id))
            ->with('user.profile')
            ->withSearch($filters['search'] ?? null)
            ->withBudgetBetween($filters['budget_min'] ?? null, $filters['budget_max'] ?? null)
            ->postedWithin($filters['posted'] ?? null)
            ->withSorting($filters['sort'] ?? 'newest');

        // ModelJob.skills/software store names (see JobManagementService::store()), while the filter
        // rail sends ids — resolve them here, then let JobFilterTrait build the JSON conditions.
        if (! empty($filters['skills'])) {
            $query->withAnySkill(Skill::whereIn('id', $filters['skills'])->pluck('name')->all());
        }

        if (! empty($filters['software'])) {
            $query->withAnySoftware(Software::whereIn('id', $filters['software'])->pluck('name')->all());
        }

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    // The signed-in user's application status for each listed job (job id => ApplicationStatus),
    // so cards can say "Applied" or "Continue draft" instead of a bare Apply button.
    public function applicationStatuses(?User $user, iterable $jobs): array
    {
        // collect($paginator) would yield the pagination metadata, not the jobs on the page.
        $ids = collect($jobs instanceof Paginator ? $jobs->items() : $jobs)->pluck('id')->all();

        if (! $user || $ids === []) {
            return [];
        }

        return JobApplication::where('applicant_id', $user->id)
            ->whereIn('job_id', $ids)
            ->pluck('status', 'job_id')
            ->all();
    }

    // Filter dropdown options for the browse view — kept here so the view never queries directly
    public function getFilterOptions(): array
    {
        return [
            'skills' => Skill::where('is_active', true)->orderBy('name')->get(),
            'software' => Software::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    // Get similar jobs for a given job
    public function getSimilarJobs(ModelJob $job, ?User $viewer = null)
    {
        // Nothing to suggest for a project that is itself closed
        if (! $job->isOpenForApplications()) {
            return collect();
        }

        $job->loadMissing('user');

        // The similar-jobs list is cached for a day, so re-check against the database that each suggestion
        // is still open (a project can be filled or expire in the meantime) and isn't the viewer's own.
        $suggestions = $this->cacheHelper->getSimilarJobs($job);
        $stillOpen = ModelJob::openForApplications()
            ->whereIn('id', $suggestions->pluck('id'))
            ->when($viewer, fn ($query) => $query->where('user_id', '!=', $viewer->id))
            ->pluck('id');

        return $suggestions->whereIn('id', $stillOpen)->values();
    }
}
