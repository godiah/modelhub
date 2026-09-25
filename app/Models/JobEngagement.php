<?php

namespace App\Models;

use App\Enums\EngagementStatus;
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
        'status' => EngagementStatus::class,
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
        return $this->hasOneThrough(
            ModelJob::class,           // Related model
            JobApplication::class,     // Intermediate model
            'id',                     // Foreign key on intermediate table (job_applications.id)
            'id', // Foreign key on related table (model_jobs.id)
            'application_id',         // Local key on current table (job_engagements.application_id)
            'job_id'                  // Local key on intermediate table (job_applications.job_id)
        )->select('model_jobs.*');    // Explicitly select from model_jobs to avoid ambiguity
    }

    /**
     * Get the poster (employer) associated with this engagement through the application
     */
    public function poster()
    {
        return $this->hasOneThrough(User::class, JobApplication::class, 'id', 'id', 'application_id', 'poster_id');
    }

    public function applicant()
    {
        return $this->hasOneThrough(User::class, JobApplication::class, 'id', 'id', 'application_id', 'applicant_id');
    }

    /**
     * Get the messages for the engagement.
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'engagement_id');
    }

    /**
     * Get the deliverables for this engagement
     */
    public function deliverables()
    {
        return $this->hasMany(JobDeliverable::class, 'engagement_id');
    }

    /**
     * Get the total number of deliverables
     */
    public function getTotalDeliverablesCount()
    {
        return $this->deliverables->count();
    }

    /**
     * Get the number of approved deliverables
     */
    public function getCompletedDeliverablesCount()
    {
        return $this->deliverables->where('status', 'approved')->count();
    }

    /**
     * Get the number of pending deliverables (submitted but not approved/rejected)
     */
    public function getPendingDeliverablesCount()
    {
        return $this->deliverables->where('status', 'submitted')->count();
    }

    /**
     * Check if there are any pending deliverables that need client action
     */
    public function hasPendingDeliverables()
    {
        return $this->getPendingDeliverablesCount() > 0;
    }

    public function hasSubmittedOrApprovedDeliverables()
    {
        return $this->getPendingDeliverablesCount() > 0 || $this->getCompletedDeliverablesCount() > 0;
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
        return in_array($this->status, [
            EngagementStatus::ApplicantAccepted,
            EngagementStatus::Active,
            EngagementStatus::Completed,
        ]);
    }

    /**
     * Check if the engagement is currently active
     */
    public function isActive()
    {
        return $this->status === EngagementStatus::Active;
    }

    /**
     * Check if the engagement is completed
     */
    public function isCompleted()
    {
        return $this->status === EngagementStatus::Completed;
    }

    /**
     * Check if the engagement is cancelled
     */
    public function isCancelled()
    {
        return $this->status === EngagementStatus::Cancelled;
    }

    /**
     * Check if the engagement is settled
     */
    public function isSettled()
    {
        return $this->status === EngagementStatus::Settled;
    }

    /**
     * Check if the payment is in escrow
     */
    public function isPaymentEscrowed()
    {
        return ! is_null($this->payment_escrowed_at);
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
    public function hasBeenReviewedByUser($userId = null)
    {
        $userId = $userId ?: Auth::id();

        return $this->reviews()
            ->where('reviewer_id', $userId)
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
        return ! in_array($this->status, [EngagementStatus::Completed, EngagementStatus::Cancelled]);
    }

    /**
     * Check if the current user can process payment
     */
    public function canProcessPayment()
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $user->id === $this->application->poster_id && $this->isCancelled();
    }

    /**
     * Calculate the partial payment amount based on approved deliverables
     */
    public function calculatePartialPaymentAmount()
    {
        $totalDeliverables = $this->getTotalDeliverablesCount();
        $approvedDeliverables = $this->getCompletedDeliverablesCount();

        if ($totalDeliverables === 0 || $approvedDeliverables === 0) {
            return 0;
        }

        $paymentPercentage = $approvedDeliverables / $totalDeliverables;

        return round($this->net_amount * $paymentPercentage, 2);
    }

    /**
     * Mark engagement as settled after payment acceptance
     */
    public function markAsSettled()
    {
        $this->update([
            'status' => EngagementStatus::Settled,
        ]);

        if ($this->cancellation) {
            $this->cancellation->update([
                'partial_payment_processed' => true,
                'partial_payment_processed_at' => now(),
            ]);
        }

        return $this;
    }

    /**
     * Mark engagement as disputed
     */
    public function markAsDisputed()
    {
        $this->update([
            'status' => EngagementStatus::Disputed,
        ]);

        if ($this->cancellation) {
            $this->cancellation->update([
                'is_dispute' => true,
            ]);
        }

        return $this;
    }

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

    /**
     * Get the status badge CSS classes based on engagement status
     */
    public function getStatusClasses()
    {
        return $this->status->badgeClasses();
    }

    /**
     * Get human-readable status name
     */
    public function getStatusLabelAttribute()
    {
        return $this->status->label();
    }

    /**
     * Get SVG path for status icon
     */
    public function getStatusIconPathAttribute()
    {
        return $this->status->iconPath();
    }

    /**
     * Total Earnings
     */
    public function scopeForApplicant($query, $userId)
    {
        return $query->whereHas('application', function ($q) use ($userId) {
            $q->where('applicant_id', $userId);
        });
    }
}
