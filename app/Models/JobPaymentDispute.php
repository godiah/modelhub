<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPaymentDispute extends Model
{
    use HasFactory;

    // Dispute statuses
    const STATUS_PENDING = 'pending';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'cancellation_id',
        'disputed_by',
        'dispute_reason',
        'dispute_details',
        'supporting_evidence',
        'status',
        'admin_assigned',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
        'resolution_amount',
    ];

    protected $casts = [
        'supporting_evidence' => 'array',
        'resolved_at' => 'datetime',
        'resolution_amount' => 'decimal:2',
    ];

    /**
     * Get the cancellation this dispute belongs to
     */
    public function cancellation()
    {
        return $this->belongsTo(JobCancellation::class, 'cancellation_id');
    }

    /**
     * Get the user who initiated the dispute
     */
    public function disputedBy()
    {
        return $this->belongsTo(User::class, 'disputed_by');
    }

    /**
     * Get the admin assigned to handle this dispute
     */
    public function assignedAdmin()
    {
        return $this->belongsTo(User::class, 'admin_assigned');
    }

    /**
     * Get the admin who resolved this dispute
     */
    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get the partial payment related to this dispute
     */
    public function partialPayment()
    {
        return $this->hasOne(JobPartialPayment::class, 'dispute_id');
    }

    /**
     * Get the engagement through the cancellation
     */
    public function engagement()
    {
        return $this->hasOneThrough(
            JobEngagement::class,
            JobCancellation::class,
            'id',
            'id',
            'cancellation_id',
            'engagement_id'
        );
    }

    /**
     * Assign an admin to review this dispute
     */
    public function assignAdmin($adminId)
    {
        $this->update([
            'status' => self::STATUS_UNDER_REVIEW,
            'admin_assigned' => $adminId,
        ]);

        return $this;
    }

    /**
     * Resolve this dispute
     */
    public function resolve($adminId, $notes, $amount = null)
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => $adminId,
            'resolution_notes' => $notes,
        ]);

        if ($amount !== null) {
            $this->update([
                'resolution_amount' => $amount,
            ]);
        }

        return $this;
    }

    /**
     * Check if this dispute is pending review
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if this dispute is under review
     */
    public function isUnderReview()
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }

    /**
     * Check if this dispute is resolved
     */
    public function isResolved()
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * Add supporting evidence to this dispute
     */
    public function addEvidence($evidence)
    {
        $currentEvidence = $this->supporting_evidence ?? [];
        $currentEvidence[] = $evidence;

        $this->update([
            'supporting_evidence' => $currentEvidence,
        ]);

        return $this;
    }
}
