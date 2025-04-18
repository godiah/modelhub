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
}
