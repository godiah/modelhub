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
        'freelancer_accepted_payment',
        'freelancer_accepted_at',
    ];

    protected $casts = [
        'process_payment_for_work' => 'boolean',
        'partial_payment_processed' => 'boolean',
        'is_dispute' => 'boolean',
        'freelancer_accepted_payment' => 'boolean',
        'payment_calculated_at' => 'datetime',
        'partial_payment_processed_at' => 'datetime',
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
     * Get the dispute details if this cancellation is disputed
     */
    public function dispute()
    {
        return $this->hasOne(JobPaymentDispute::class, 'cancellation_id');
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
    public function resolveDispute($finalAmount = null)
    {
        $this->update([
            'is_dispute' => false,
        ]);

        if ($finalAmount !== null) {
            $this->update([
                'partial_payment_amount' => $finalAmount,
            ]);
        }

        return $this;
    }
}
