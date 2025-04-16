<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ModelJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'skills',
        'software',
        'images',
        'deadline',
        'no_deadline',
        'budget',
        'is_active',
        'applicants_count',
    ];

    protected $casts = [
        'skills' => 'array',
        'software' => 'array',
        'no_deadline' => 'boolean',
        'is_active' => 'boolean',
        'deadline' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Ensures binding by slug
    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class, 'job_id')
            ->where('status', '!=', 'drafted');
    }

    public function jobImages()
    {
        return $this->hasMany(JobImage::class, 'model_job_id');
    }

    /**
     * Check if the job has any accepted engagement
     */
    public function hasAcceptedEngagement()
    {
        return $this->applications()
            ->whereHas('engagement', function ($query) {
                $query->whereIn('status', ['applicant_accepted', 'active', 'completed']);
            })
            ->exists();
    }

    /**
     * Filters jobs that are considered "active"
     * Includes jobs with no_deadline = true
     * or jobs with a deadline in the future
     * Used for querying active jobs from the database
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->where('no_deadline', true)
                    ->orWhere('deadline', '>', now());
            });
    }

    /**
     * Checks if a single job instance is active
     * Returns true if no_deadline is set
     * Returns true if deadline hasn't passed
     * Used for checking individual job status
     */
    public function isActive()
    {
        return $this->is_active &&
            ($this->no_deadline || now()->lessThanOrEqualTo($this->deadline));
    }

    /**
     *  Cache invalidation when jobs are updated or new jobs are created
     */
    protected static function booted()
    {
        parent::booted();

        // When a job is updated, we should invalidate related caches
        static::updated(function ($job) {
            // Clear cache for this specific job
            Cache::forget('similar_jobs_' . $job->id . '_' . $job->updated_at->timestamp);

            // Get jobs that might have this job as "similar"
            // This is approximate - we're getting jobs that share tags
            $relatedJobs = ModelJob::where('id', '!=', $job->id)
                ->active()
                ->where(function ($query) use ($job) {
                    $tags = array_merge($job->skills ?? [], $job->software ?? []);
                    foreach ($tags as $tag) {
                        $query->orWhereRaw("JSON_CONTAINS(skills, ?)", ['"' . $tag . '"'])
                            ->orWhereRaw("JSON_CONTAINS(software, ?)", ['"' . $tag . '"']);
                    }
                })
                ->limit(50) // Limit to avoid excessive processing                
                ->get();

            // Clear cache for related jobs
            foreach ($relatedJobs as $relatedJob) {
                Cache::forget('similar_jobs_' . $relatedJob->id . '_' . $relatedJob->updated_at->timestamp);
            }
        });

        // When a job is created, invalidate caches of jobs that might show this as similar
        static::created(function ($job) {
            // Get current job's tags
            $tags = array_merge($job->skills ?? [], $job->software ?? []);

            // If job has no tags, no need to invalidate any caches
            if (empty($tags)) {
                return;
            }

            // Find jobs that share tags with this new job
            // Those jobs might need to include this new job in their "similar jobs" lists
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
                $cacheKey = 'similar_jobs_' . $relatedJob->id . '_' . $relatedJob->updated_at->timestamp;
                Cache::forget($cacheKey);

                // Alternatively, for logging purposes:
                // \Log::info("Invalidated similar jobs cache for job #{$relatedJob->id} due to new job #{$job->id}");
            }
        });
    }
}
