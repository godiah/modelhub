<?php

namespace App\Models;

use App\Enums\EngagementStatus;
use App\Helpers\Jobs\JobCacheHelper;
use App\Traits\JobFilterTrait;
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

    /** Engagements that came out of this project's applications. */
    public function engagements()
    {
        return $this->hasManyThrough(JobEngagement::class, JobApplication::class, 'job_id', 'application_id');
    }

    /**
     * True while a hired freelancer is working (or has finished/disputed the work), so the project must not
     * be reopened to new applications from the edit form. Cancelled engagements are reopened on purpose.
     */
    public function hasEngagementInProgress(): bool
    {
        return $this->engagements()
            ->whereIn('job_engagements.status', [EngagementStatus::Active, EngagementStatus::Disputed, EngagementStatus::Completed])
            ->exists();
    }

    /**
     * Archiving tidies away a finished or abandoned project, so it must already be closed to applications and
     * have nobody working on it (a completed engagement is fine; an active or disputed one is not).
     */
    public function canBeArchived(): bool
    {
        if ($this->is_archived || $this->isOpenForApplications()) {
            return false;
        }

        $engagements = $this->relationLoaded('engagements') ? $this->engagements : $this->engagements()->get();

        return ! $engagements->contains(fn ($e) => in_array($e->status, [EngagementStatus::Active, EngagementStatus::Disputed], true));
    }

    /**
     * What the poster sees as the project's state: [label, badge tone]. Needs `engagements` loaded.
     *
     * @return array{0: string, 1: string}
     */
    public function listingStatus(): array
    {
        $engagement = $this->engagements->sortByDesc('id')->first(fn ($e) => in_array($e->status, [
            EngagementStatus::Active, EngagementStatus::Disputed, EngagementStatus::Completed, EngagementStatus::EmployerAccepted,
        ], true));

        if ($engagement) {
            return match ($engagement->status) {
                EngagementStatus::EmployerAccepted => [__('Offer sent'), 'amber'],
                EngagementStatus::Completed => [__('Completed'), 'green'],
                EngagementStatus::Disputed => [__('In dispute'), 'red'],
                default => [__('In progress'), 'blue'],
            };
        }

        return match (true) {
            $this->is_archived => [__('Archived'), 'neutral'],
            $this->isOpenForApplications() => [__('Open'), 'green'],
            $this->is_active => [__('Expired'), 'neutral'],
            default => [__('Closed'), 'neutral'],
        };
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
     * True when the project has a deadline within the next few days (today included), for "closing soon" cues.
     */
    public function deadlineIsSoon(int $days = 3): bool
    {
        if ($this->no_deadline || $this->deadline === null) {
            return false;
        }

        $left = today()->diffInDays($this->deadline, false);

        return $left >= 0 && $left <= $days;
    }

    /**
     * The one definition of "open for applications": switched on, not archived, and either without a
     * deadline or with a deadline that has not passed. A deadline is a date, so the project stays open
     * through the end of that day ("Due Oct 2" still accepts applications on Oct 2).
     * Browse, the apply page, application submission and similar projects all use this.
     */
    public function scopeOpenForApplications($query)
    {
        return $query->where('is_active', true)
            ->where('is_archived', false)
            ->where(function ($q) {
                $q->where('no_deadline', true)
                    ->orWhereNull('deadline')
                    ->orWhere('deadline', '>=', today());
            });
    }

    /**
     * The complement of scopeOpenForApplications(): switched off, archived or past its deadline.
     */
    public function scopeNotOpenForApplications($query)
    {
        return $query->where(function ($q) {
            $q->where('is_active', false)
                ->orWhere('is_archived', true)
                ->orWhere(function ($expired) {
                    $expired->where('no_deadline', false)->whereNotNull('deadline')->where('deadline', '<', today());
                });
        });
    }

    /**
     * Instance version of scopeOpenForApplications().
     */
    public function isOpenForApplications(): bool
    {
        return $this->is_active
            && ! $this->is_archived
            && ($this->no_deadline || $this->deadline === null || ! $this->deadline->isBefore(today()));
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
            'is_active' => false,
        ]);
    }

    /**
     * Unarchive the job
     */
    public function unarchive()
    {
        // Restoring takes the project out of the archive; it only accepts applications again when that is
        // safe: nobody is working on it and its deadline has not passed. Otherwise it comes back closed
        // and the poster reopens it (with a new deadline) from Edit.
        $reopen = ! $this->hasEngagementInProgress()
            && ($this->no_deadline || $this->deadline === null || ! $this->deadline->isBefore(today()));

        $this->update([
            'is_archived' => false,
            'is_active' => $reopen,
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
