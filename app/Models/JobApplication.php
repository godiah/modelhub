<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class JobApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_id',
        'applicant_id',
        'poster_id',
        'offer_amount',
        'service_fee',
        'net_amount',
        'proposal',
        'portfolio',
        'terms_accepted',
        'status', // 'draft', 'submitted', 'hired, 'rejected', 'reviewed' , 'withdrawn'
        'additional_notes',
        'is_archived',
    ];

    protected $casts = [
        'portfolio' => 'array',
        'terms_accepted' => 'boolean',
        'offer_amount' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'is_archived' => 'boolean',
    ];

    // Relationship with the job
    public function job()
    {
        return $this->belongsTo(ModelJob::class, 'job_id');
    }

    // Relationship with the applicant (user who applied)
    public function applicant()
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    // Relationship with the poster (user who created the job)
    public function poster()
    {
        return $this->belongsTo(User::class, 'poster_id');
    }

    // Relationship with job engagement
    public function engagement()
    {
        return $this->hasOne(JobEngagement::class, 'application_id');
    }

    // Check if this application has been converted to an engagement
    public function hasEngagement()
    {
        return $this->engagement()->exists();
    }

    /**
     * All engagements for _any_ application of this same job.
     */
    public function jobEngagements()
    {
        return $this->hasManyThrough(
            JobEngagement::class,
            self::class,
            'job_id',
            'application_id',
            'job_id',
            'id'
        );
    }

    /**
     * “You got hired”: an engagement exists for *this* application.
     */
    public function hasOwnEngagement(): bool
    {
        return (bool) $this->engagement;
    }

    /**
     * “Filled by someone else”: 
     *  there’s an engagement on the same job, but _not_ for this application.
     */
    public function hasOtherEngagement(): bool
    {
        return $this->jobEngagements()
            ->where('application_id', '!=', $this->id)
            ->exists();
    }

    // Scope for active (non-archived) applications
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    // Scope for archived applications
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    // Check if application should show archive option
    public function canBeArchived()
    {
        return in_array($this->status, ['hired', 'rejected']) || $this->hasOtherEngagement();
    }

    // Scope to get draft applications
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // Scope to get submitted applications
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    // Scope to get hired applications
    public function scopeHired($query)
    {
        return $query->where('status', 'hired');
    }

    // Scope to get rejected applications
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Scope to get reviewed applications
    public function scopeReviewed($query)
    {
        return $query->where('status', 'reviewed');
    }
}
