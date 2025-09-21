<?php

namespace App\Models;

use App\Helpers\Jobs\JobCacheHelper;
use App\Jobs\JobFilterTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class ModelJob extends Model
{
    use HasFactory, JobFilterTrait;

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
        'is_archived',
        'applicants_count',
    ];

    protected $casts = [
        'skills' => 'array',
        'software' => 'array',
        'no_deadline' => 'boolean',
        'is_active' => 'boolean',
        'is_archived' => 'boolean',
        'deadline' => 'date',
    ];

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

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
            ->where('is_archived', false)
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
     * Scope for archived jobs
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope for unarchived jobs
     */
    public function scopeUnarchived($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Check if the job is archived
     */
    public function isArchived()
    {
        return $this->is_archived;
    }

    /**
     * Archive the job
     */
    public function archive()
    {
        $this->update([
            'is_archived' => true,
            'is_active' => false
        ]);
    }

    /**
     * Unarchive the job
     */
    public function unarchive()
    {
        $this->update([
            'is_archived' => false,
            'is_active' => true
        ]);
    }

    /**
     *  Cache invalidation when jobs are updated or new jobs are created
     */
    protected static function booted()
    {
        parent::booted();

        // When a job is updated, we should invalidate related caches
        static::updated(function ($job) {
            $cacheHelper = app(JobCacheHelper::class);
            
            // Clear cache for this specific job
            $cacheHelper->clearJobCache($job);

            // Clear cache for related jobs
            $cacheHelper->clearRelatedJobsCache($job);
        });

        // When a job is created, invalidate caches of jobs that might show this as similar
        static::created(function ($job) {
            $cacheHelper = app(JobCacheHelper::class);
            
            // Clear cache for related jobs that might need to include this new job
            $cacheHelper->clearRelatedJobsCache($job);
        });
    }
}
