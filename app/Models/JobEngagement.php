<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class JobEngagement extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'status',
        'employer_accepted_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'notes',
        'agreed_amount',
        'service_fee',
        'net_amount',
        'payment_escrowed_at',
        'payment_released_at',
        'is_archived_by_applicant',
        'is_archived_by_poster',
    ];

    protected $casts = [
        'employer_accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'payment_escrowed_at' => 'datetime',
        'payment_released_at' => 'datetime',
        'agreed_amount' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'is_archived_by_applicant' => 'boolean',
        'is_archived_by_poster' => 'boolean',
    ];

    /**
     * Get the job application associated with this engagement
     */
    public function application()
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }

    /**
     * Get the job associated with this engagement through the application
     */
    public function job()
    {
        return $this->hasOneThrough(ModelJob::class, JobApplication::class, 'id', 'id', 'application_id', 'job_id');
    }

    /**
     * Get the poster (employer) associated with this engagement through the application
     */
    public function poster()
    {
        return $this->hasOneThrough(User::class, JobApplication::class, 'id', 'id', 'application_id', 'poster_id');
    }

    /**
     * Get the applicant (freelancer) associated with this engagement through the application
     */
    public function applicant()
    {
        return $this->hasOneThrough(User::class, JobApplication::class, 'id', 'id', 'application_id', 'applicant_id');
    }

    /**
     * Get the deliverables for this engagement
     */
    public function deliverables()
    {
        return $this->hasMany(JobDeliverable::class, 'engagement_id');
    }

    /**
     * Get the reviews for this engagement
     */
    public function reviews()
    {
        return $this->hasMany(JobReview::class, 'engagement_id');
    }

    /**
     * Check if the engagement has been accepted by the applicant
     */
    public function isAcceptedByApplicant()
    {
        return in_array($this->status, ['applicant_accepted', 'active', 'completed']);
    }

    /**
     * Check if the engagement is currently active
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Check if the engagement is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the engagement is cancelled
     */
    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if the payment is in escrow
     */
    public function isPaymentEscrowed()
    {
        return !is_null($this->payment_escrowed_at);
    }

    /**
     * Check if all deliverables are completed
     */
    public function allDeliverablesCompleted()
    {
        if ($this->deliverables->isEmpty()) {
            return false;
        }

        return $this->deliverables->every(function ($deliverable) {
            return $deliverable->status === 'approved';
        });
    }

    /**
     * Calculate completion percentage based on approved deliverables
     */
    public function completionPercentage()
    {
        $total = $this->deliverables->count();
        if ($total === 0) {
            return 0;
        }

        $completed = $this->deliverables->where('status', 'approved')->count();
        return ($completed / $total) * 100;
    }

    // Check for existing review
    public function hasBeenReviewedByCurrentUser()
    {
        return $this->reviews()
            ->where('reviewer_id', Auth::id())
            ->exists();
    }

    /**
     * Get the cancellation record for this engagement
     */
    public function cancellation()
    {
        return $this->hasOne(JobCancellation::class, 'engagement_id');
    }

    /**
     * Get partial payments for this engagement
     */
    public function partialPayments()
    {
        return $this->hasMany(JobPartialPayment::class, 'engagement_id');
    }

    /**
     * Determine if user can cancel this engagement
     */
    public function canBeCancelled()
    {
        return !in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * Check if the current user can process payment
     */
    public function canProcessPayment()
    {
        $user = Auth::user();
        if (!$user) return false;

        return $user->id === $this->application->poster_id && $this->isCancelled();
    }

    // // Scope for active (non-archived) engagements
    // public function scopeActive($query)
    // {
    //     return $query->where('is_archived', false);
    // }

    // // Scope for archived
    // public function scopeArchived($query)
    // {
    //     return $query->where('is_archived', true);
    // }

    public function scopeActiveForUser($query, $userId)
    {
        return $query->whereHas('application', function ($q) use ($userId) {
            $q->where(function ($subQuery) use ($userId) {
                $subQuery->where('applicant_id', $userId)
                    ->where('job_engagements.is_archived_by_applicant', false);
            })->orWhere(function ($subQuery) use ($userId) {
                $subQuery->where('poster_id', $userId)
                    ->where('job_engagements.is_archived_by_poster', false);
            });
        });
    }

    public function scopeArchivedForUser($query, $userId)
    {
        return $query->whereHas('application', function ($q) use ($userId) {
            $q->where(function ($subQuery) use ($userId) {
                $subQuery->where('applicant_id', $userId)
                    ->where('job_engagements.is_archived_by_applicant', true);
            })->orWhere(function ($subQuery) use ($userId) {
                $subQuery->where('poster_id', $userId)
                    ->where('job_engagements.is_archived_by_poster', true);
            });
        });
    }
}
