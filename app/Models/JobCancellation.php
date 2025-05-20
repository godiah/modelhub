<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobCancellation extends Model
{
    use HasFactory;

    // Cancellation types
    const TYPE_CLIENT_INITIATED = 'client_initiated';
    const TYPE_FREELANCER_INITIATED = 'freelancer_initiated';
    const TYPE_MUTUAL_AGREEMENT = 'mutual_agreement';
    const TYPE_ADMIN_TERMINATED = 'admin_terminated';

    // Dispute status
    const DISPUTE_STATUS_PENDING = 'pending';
    const DISPUTE_STATUS_UNDER_REVIEW = 'under_review';
    const DISPUTE_STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'engagement_id',
        'initiator_id',
        'cancellation_type',
        'reason_category',
        'reason_details',
        'process_payment_for_work',
        'partial_payment_amount',
        'payment_calculated_at',
        'partial_payment_processed',
        'partial_payment_processed_at',
        'is_dispute',
        'dispute_resolved',
        'dispute_resolved_at',
        'resolved_by',
        'resolution_notes',
        'dispute_status',
        'freelancer_accepted_payment',
        'freelancer_accepted_at',
    ];

    protected $casts = [
        'process_payment_for_work' => 'boolean',
        'partial_payment_processed' => 'boolean',
        'is_dispute' => 'boolean',
        'dispute_resolved' => 'boolean',
        'freelancer_accepted_payment' => 'boolean',
        'payment_calculated_at' => 'datetime',
        'partial_payment_processed_at' => 'datetime',
        'dispute_resolved_at' => 'datetime',
        'freelancer_accepted_at' => 'datetime',
        'partial_payment_amount' => 'decimal:2',
    ];

    /**
     * Get the engagement this cancellation belongs to
     */
    public function engagement()
    {
        return $this->belongsTo(JobEngagement::class, 'engagement_id');
    }

    /**
     * Get the user who initiated the cancellation
     */
    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    /**
     * Get the admin who resolved the dispute (if applicable)
     */
    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get the dispute details if this cancellation is disputed
     */
    public function dispute()
    {
        return $this->hasOne(JobPaymentDispute::class, 'cancellation_id');
    }

    /**
     * Check if this cancellation requires admin intervention
     */
    public function requiresAdminIntervention()
    {
        return $this->is_dispute && !$this->dispute_resolved;
    }

    /**
     * Check if partial payment is pending
     */
    public function isPartialPaymentPending()
    {
        return $this->process_payment_for_work && !$this->partial_payment_processed;
    }

    /**
     * Check if this cancellation is a dispute
     */
    public function isDispute()
    {
        return $this->is_dispute;
    }

    /**
     * Check if this dispute has been resolved
     */
    public function isResolved()
    {
        return $this->isDispute() && !is_null($this->dispute_resolved_at);
    }

    /**
     * Check if freelancer has accepted the partial payment
     */
    public function isPaymentAccepted()
    {
        return $this->freelancer_accepted_payment;
    }

    /**
     * Mark payment as accepted by freelancer
     */
    public function acceptPayment()
    {
        $this->update([
            'freelancer_accepted_payment' => true,
            'freelancer_accepted_at' => now(),
        ]);

        return $this;
    }

    /**
     * Create a dispute for this cancellation
     */
    public function createDispute($reason, $details, $userId)
    {
        $this->update([
            'is_dispute' => true,
            'dispute_status' => self::DISPUTE_STATUS_PENDING,
        ]);

        return JobPaymentDispute::create([
            'cancellation_id' => $this->id,
            'disputed_by' => $userId,
            'dispute_reason' => $reason,
            'dispute_details' => $details,
            'status' => JobPaymentDispute::STATUS_PENDING,
        ]);
    }

    /**
     * Resolve a dispute
     */
    public function resolveDispute($adminId, $notes, $finalAmount = null)
    {
        $this->update([
            'dispute_resolved' => true,
            'dispute_resolved_at' => now(),
            'resolved_by' => $adminId,
            'resolution_notes' => $notes,
            'dispute_status' => self::DISPUTE_STATUS_RESOLVED,
        ]);

        if ($finalAmount !== null) {
            $this->update([
                'partial_payment_amount' => $finalAmount,
            ]);
        }

        if ($this->dispute) {
            $this->dispute->update([
                'status' => JobPaymentDispute::STATUS_RESOLVED,
                'resolved_at' => now(),
                'resolved_by' => $adminId,
                'resolution_notes' => $notes,
            ]);
        }

        return $this;
    }
}
