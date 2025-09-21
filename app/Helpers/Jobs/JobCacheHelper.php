<?php

// JobCacheHelper manages caching operations for job-related data, focusing on similar job recommendations.
// This helper class generates cache keys, calculates similar jobs based on shared skills and software,
// and handles cache invalidation to ensure up-to-date job recommendations while optimizing performance.

namespace App\Helpers\Jobs;

use App\Models\ModelJob;
use Illuminate\Support\Facades\Cache;

class JobCacheHelper
{
    // Get similar jobs with caching
    public function getSimilarJobs(ModelJob $job)
    {
        // Cache key based on job ID and last updated timestamp
        $cacheKey = $this->generateSimilarJobsCacheKey($job);

        // Get similar jobs from cache or calculate if not cached
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($job) {
            return $this->calculateSimilarJobs($job);
        });
    }

    // Generate cache key for similar jobs
    public function generateSimilarJobsCacheKey(ModelJob $job): string
    {
        return 'similar_jobs_' . $job->id . '_' . $job->updated_at->timestamp;
    }

    // Calculate similar jobs based on tags
    protected function calculateSimilarJobs(ModelJob $job)
    {
        // Get current job's tags
        $currentJobTags = array_merge(
            $job->skills ?? [],
            $job->software ?? []
        );

        // If no tags, just get the most recent jobs
        if (empty($currentJobTags)) {
            return ModelJob::where('id', '!=', $job->id)
                ->active()
                ->latest()
                ->take(4)
                ->get();
        }

        // Use database queries for efficiency
        $similarJobsQuery = ModelJob::where('id', '!=', $job->id)->active();

        // Use raw SQL for JSON array comparison
        $similarJobsQuery->where(function ($query) use ($currentJobTags) {
            foreach ($currentJobTags as $tag) {
                $query->orWhereRaw("JSON_CONTAINS(skills, ?)", ['"' . $tag . '"'])
                    ->orWhereRaw("JSON_CONTAINS(software, ?)", ['"' . $tag . '"']);
            }
        });

        // Calculate similarity score at database level and order by it
        $selectRaw = [];
        foreach ($currentJobTags as $tag) {
            $selectRaw[] = "JSON_CONTAINS(skills, '\"" . $tag . "\")";
            $selectRaw[] = "JSON_CONTAINS(software, '\"" . $tag . "\")";
        }

        $similarJobs = $similarJobsQuery
            ->select('*')
            ->selectRaw('(' . implode(' + ', $selectRaw) . ') as similarity_score')
            ->orderByDesc('similarity_score')
            ->orderByDesc('created_at')
            ->take(4)
            ->get();

        // If we found fewer than 4 similar jobs, supplement with recent jobs
        if ($similarJobs->count() < 4) {
            $existingIds = $similarJobs->pluck('id')->toArray();
            $additionalJobs = ModelJob::where('id', '!=', $job->id)
                ->whereNotIn('id', $existingIds)
                ->active()
                ->latest()
                ->take(4 - $similarJobs->count())
                ->get();

            $similarJobs = $similarJobs->merge($additionalJobs);
        }

        return $similarJobs;
    }

    // Clear cache for a specific job
    public function clearJobCache(ModelJob $job): void
    {
        $cacheKey = $this->generateSimilarJobsCacheKey($job);
        Cache::forget($cacheKey);
    }

    // Clear cache for related jobs that might show this job as similar
    public function clearRelatedJobsCache(ModelJob $job): void
    {
        $tags = array_merge($job->skills ?? [], $job->software ?? []);

        // If job has no tags, no need to invalidate any caches
        if (empty($tags)) {
            return;
        }

        // Find jobs that share tags with this job
        $relatedJobs = ModelJob::where('id', '!=', $job->id)
            ->active()
            ->where(function ($query) use ($tags) {
                foreach ($tags as $tag) {
                    $query->orWhereRaw("JSON_CONTAINS(skills, ?)", ['"' . $tag . '"'])
                        ->orWhereRaw("JSON_CONTAINS(software, ?)", ['"' . $tag . '"']);
                }
            })
            ->limit(50) // Limit to avoid excessive processing
            ->get();

        // Clear cache for each related job
        foreach ($relatedJobs as $relatedJob) {
            $this->clearJobCache($relatedJob);
        }
    }
}
