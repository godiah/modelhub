<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class JobEngagement extends Model
{
    use HasFactory;

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_DISPUTED = 'disputed';
    const STATUS_SETTLED = 'settled';

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
     * Get the total number of deliverables
     */
    public function getTotalDeliverablesCount()
    {
        return $this->deliverables()->count();
    }

    /**
     * Get the number of approved deliverables
     */
    public function getCompletedDeliverablesCount()
    {
        return $this->deliverables()->where('status', 'approved')->count();
    }

    /**
     * Get the number of pending deliverables (submitted but not approved/rejected)
     */
    public function getPendingDeliverablesCount()
    {
        return $this->deliverables()->where('status', 'submitted')->count();
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
     * Check if the engagement is settled
     */
    public function isSettled()
    {
        return $this->status === 'settled';
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
        return round($this->agreed_amount * $paymentPercentage, 2);
    }

    /**
     * Check if user can process partial payment
     */
    public function canProcessPartialPayment($userId)
    {
        // Only client or admin can process payments
        $isClient = $this->application->poster_id === $userId;
        $isAdmin = auth()->user()->hasRole('admin'); // Assuming you have a role system

        return ($isClient || $isAdmin) &&
            $this->status === self::STATUS_CANCELLED &&
            !$this->hasPendingDeliverables();
    }

    /**
     * Mark engagement as settled after payment acceptance
     */

    public function markAsSettled()
    {
        $this->update([
            'status' => self::STATUS_SETTLED,
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
            'status' => self::STATUS_DISPUTED,
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
        return match ($this->status) {
            'employer_accepted' => [
                'bg' => 'bg-accent/10',
                'text' => 'text-accent',
                'border' => 'border-accent/20'
            ],
            'active' => [
                'bg' => 'bg-secondary/10',
                'text' => 'text-secondary',
                'border' => 'border-secondary/20'
            ],
            'completed' => [
                'bg' => 'bg-green-100',
                'text' => 'text-green-800',
                'border' => 'border-green-200'
            ],
            'cancelled' => [
                'bg' => 'bg-red-100',
                'text' => 'text-red-800',
                'border' => 'border-red-200'
            ],
            'disputed' => [
                'bg' => 'bg-orange-100',
                'text' => 'text-orange-800',
                'border' => 'border-orange-200'
            ],
            'settled' => [
                'bg' => 'bg-blue-100',
                'text' => 'text-blue-800',
                'border' => 'border-blue-200'
            ],
            default => [
                'bg' => 'bg-gray-100',
                'text' => 'text-gray-800',
                'border' => 'border-gray-200'
            ],
        };
    }

    /**
     * Get human-readable status name
     */
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'employer_accepted' => 'Pending',
            'active' => 'Active',
            'completed' => 'Completed',
            'cancelled' => 'Withdrawn',
            'disputed' => 'Disputed',
            'settled' => 'Settled',
            default => 'Unknown',
        };
    }

    /**
     * Get SVG path for status icon
     */
    public function getStatusIconPathAttribute()
    {
        return match ($this->status) {
            'employer_accepted' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />',
            'active' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
            'completed' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
            'cancelled' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
            'disputed' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />',
            'settled' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />',
            default => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        };
    }
}
