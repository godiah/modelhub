<?php

// JobBrowsingService manages job listing, filtering, and sorting functionality.
// This service supports browsing jobs with search, skill, and software filters, as well as sorting options.
// It also provides functionality to retrieve similar jobs based on a given job, leveraging caching for performance.

namespace App\Services\Jobs;

use App\Helpers\Jobs\JobCacheHelper;
use App\Models\ModelJob;
use App\Models\Skill;
use App\Models\Software;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class JobBrowsingService
{
    protected JobCacheHelper $cacheHelper;

    public function __construct(JobCacheHelper $cacheHelper)
    {
        $this->cacheHelper = $cacheHelper;
    }

    // Browse jobs with filters and pagination
    public function browseJobs(array $filters): LengthAwarePaginator
    {
        $query = ModelJob::active()
            ->withSearch($filters['search'] ?? null)
            ->withSorting($filters['sort'] ?? 'newest');

        $this->applySkillsFilter($query, $filters['skills'] ?? null);
        $this->applySoftwareFilter($query, $filters['software'] ?? null);

        return $query->paginate(5);
    }

    // The browse UI's filter is a single skill/software id, but ModelJob.skills/software
    // store names (see JobManagementService::store()) — resolve the id here, then delegate
    // the actual query condition to JobFilterTrait::scopeWithSkills()/scopeWithSoftware()
    // rather than duplicating the whereJsonContains() call.
    protected function applySkillsFilter(Builder $query, ?int $skillId): void
    {
        if ($skillId && $skill = Skill::find($skillId)) {
            $query->withSkills([$skill->name]);
        }
    }

    protected function applySoftwareFilter(Builder $query, ?int $softwareId): void
    {
        if ($softwareId && $software = Software::find($softwareId)) {
            $query->withSoftware([$software->name]);
        }
    }

    // Filter dropdown options for the browse view — kept here so the view never queries directly
    public function getFilterOptions(): array
    {
        return [
            'skills' => Skill::where('is_active', true)->get(),
            'software' => Software::where('is_active', true)->get(),
        ];
    }

    // Get similar jobs for a given job
    public function getSimilarJobs(ModelJob $job)
    {
        // Ensure the job is active
        if (! $job->is_active || ! $job->isActive()) {
            return collect();
        }

        $job->loadMissing('user');

        // Get similar jobs using cache helper
        return $this->cacheHelper->getSimilarJobs($job);
    }
}
