<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\EngagementStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'status',
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
        'status' => ApplicationStatus::class,
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

    /** An application can be put away once nothing more can happen on it (not while a hired engagement is live). */
    public function canBeArchived(): bool
    {
        return ! $this->is_archived && $this->standing()['finished'];
    }

    /**
     * Where this application stands, from the applicant's point of view: label, badge tone, a one-line hint,
     * whether it is finished (nothing more will happen) and the engagement when they were hired. Uses the
     * engagement and the job's engagements, loading them once when a list did not.
     *
     * @return array{label: string, tone: string, hint: string, finished: bool, filled: bool, engagement: ?JobEngagement}
     */
    public function standing(): array
    {
        $this->loadMissing(['engagement', 'jobEngagements']);
        $engagement = $this->engagement;

        if ($engagement) {
            [$label, $tone, $hint, $finished] = match ($engagement->status) {
                EngagementStatus::EmployerAccepted => [__('Offer received'), 'amber', __('The client wants to hire you. Review the offer and accept or decline it.'), false],
                EngagementStatus::ApplicantAccepted, EngagementStatus::Active => [__('In progress'), 'blue', __('You are working on this project.'), false],
                EngagementStatus::Disputed => [__('In dispute'), 'red', __('There is a payment dispute on this project.'), false],
                EngagementStatus::Completed => [__('Completed'), 'green', __('The project is complete.'), true],
                EngagementStatus::Settled => [__('Settled'), 'neutral', __('The project was settled.'), true],
                EngagementStatus::Cancelled => [__('Cancelled'), 'neutral', __('The engagement was cancelled.'), true],
            };

            return ['label' => $label, 'tone' => $tone, 'hint' => $hint, 'finished' => $finished, 'filled' => false, 'engagement' => $engagement];
        }

        $filled = $this->jobEngagements->contains(fn (JobEngagement $other) => $other->application_id !== $this->id
            && in_array($other->status, [
                EngagementStatus::EmployerAccepted, EngagementStatus::ApplicantAccepted, EngagementStatus::Active,
                EngagementStatus::Disputed, EngagementStatus::Completed,
            ], true));

        [$label, $tone, $hint, $finished] = match (true) {
            $this->status === ApplicationStatus::Hired => [__('Hired'), 'green', __('You were hired for this project.'), true],
            $this->status === ApplicationStatus::Withdrawn => [__('Withdrawn'), 'neutral', __('You withdrew from this project.'), true],
            $this->status === ApplicationStatus::Rejected => [__('Rejected'), 'red', __('The client chose another freelancer.'), true],
            $filled => [__('Position filled'), 'neutral', __('The client hired another freelancer for this project.'), true],
            $this->status === ApplicationStatus::Reviewed => [__('Reviewed'), 'amber', __('The client has looked at your application.'), false],
            $this->status === ApplicationStatus::Draft => [__('Draft'), 'neutral', __('You have not sent this yet.'), false],
            default => [__('Submitted'), 'blue', __('Waiting for the client to review your application.'), false],
        };

        return ['label' => $label, 'tone' => $tone, 'hint' => $hint, 'finished' => $finished, 'filled' => $filled, 'engagement' => null];
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

    /**
     * Why this application cannot be hired right now, or null when it can. Enforced on the server: the
     * buttons hide these cases, but a direct request must not create a second or a duplicate engagement.
     */
    public function hireBlocker(): ?string
    {
        return match (true) {
            $this->status === ApplicationStatus::Hired => 'You have already hired this applicant.',
            $this->status === ApplicationStatus::Withdrawn => 'This applicant withdrew their application.',
            $this->status === ApplicationStatus::Draft => 'This application has not been submitted yet.',
            (bool) $this->job->is_archived => 'Restore this project from your archive before hiring.',
            $this->job->hasActiveHire() => 'You have already hired someone for this project.',
            default => null,
        };
    }

    /** A hired or withdrawn application's status is final. */
    public function isStatusLocked(): bool
    {
        return in_array($this->status, [ApplicationStatus::Hired, ApplicationStatus::Withdrawn], true);
    }
}
