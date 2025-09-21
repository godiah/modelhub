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
        $query = ModelJob::active();

        $this->applySearchFilter($query, $filters['search'] ?? null);
        $this->applySkillsFilter($query, $filters['skills'] ?? null);
        $this->applySoftwareFilter($query, $filters['software'] ?? null);
        $this->applySorting($query, $filters['sort'] ?? 'newest');

        return $query->paginate(5);
    }

    // Apply search filter to query
    protected function applySearchFilter(Builder $query, ?string $search): void
    {
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
    }

    // Apply skills filter to query
    protected function applySkillsFilter(Builder $query, ?int $skillId): void
    {
        if ($skillId) {
            $skill = Skill::find($skillId);
            if ($skill) {
                $query->whereJsonContains('skills', $skill->name);
            }
        }
    }

    // Apply software filter to query
    protected function applySoftwareFilter(Builder $query, ?int $softwareId): void
    {
        if ($softwareId) {
            $software = Software::find($softwareId);
            if ($software) {
                $query->whereJsonContains('software', $software->name);
            }
        }
    }

    // Apply sorting to query
    protected function applySorting(Builder $query, string $sort): void
    {
        switch ($sort) {
            case 'budget_high':
                $query->orderBy('budget', 'desc');
                break;
            case 'budget_low':
                $query->orderBy('budget', 'asc');
                break;
            case 'deadline':
                $query->whereNotNull('deadline')->orderBy('deadline', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }
    }

    // Get similar jobs for a given job
    public function getSimilarJobs(ModelJob $job)
    {
        // Ensure the job is active
        if (!$job->is_active || !$job->isActive()) {
            return collect();
        }

        $job->loadMissing('user');

        // Get similar jobs using cache helper
        return $this->cacheHelper->getSimilarJobs($job);
    }
}
