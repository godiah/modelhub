<?php

namespace App\Models;

use App\Enums\PartialPaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPartialPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'engagement_id',
        'amount',
        'notes',
        'processed_by',
        'processed_at',
        'status',
        'accepted_at',
        'dispute_id',
        'final_amount',
        'finalized_at',
        'finalized_by',
    ];

    protected $casts = [
        'status' => PartialPaymentStatus::class,
        'amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'accepted_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    /**
     * Get the engagement this payment belongs to
     */
    public function engagement()
    {
        return $this->belongsTo(JobEngagement::class, 'engagement_id');
    }

    /**
     * Get the user who processed this payment
     */
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the user who finalized this payment
     */
    public function finalizer()
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    /**
     * Get the dispute associated with this payment
     */
    public function dispute()
    {
        return $this->belongsTo(JobPaymentDispute::class, 'dispute_id');
    }

    /**
     * Mark the payment as accepted by freelancer
     */
    public function markAsAccepted()
    {
        $this->update([
            'status' => PartialPaymentStatus::Accepted,
            'accepted_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark the payment as disputed
     */
    public function markAsDisputed($disputeId)
    {
        $this->update([
            'status' => PartialPaymentStatus::Disputed,
            'dispute_id' => $disputeId,
        ]);

        return $this;
    }

    /**
     * Finalize the payment (after dispute resolution)
     */
    public function finalize($adminId, $finalAmount = null)
    {
        $this->update([
            'status' => PartialPaymentStatus::Finalized,
            'finalized_at' => now(),
            'finalized_by' => $adminId,
            'final_amount' => $finalAmount ?? $this->amount,
        ]);

        return $this;
    }

    /**
     * Check if payment is pending acceptance
     */
    public function isPending()
    {
        return $this->status === PartialPaymentStatus::Pending;
    }

    /**
     * Check if payment is disputed
     */
    public function isDisputed()
    {
        return $this->status === PartialPaymentStatus::Disputed;
    }

    /**
     * Check if payment is accepted
     */
    public function isAccepted()
    {
        return $this->status === PartialPaymentStatus::Accepted;
    }

    /**
     * Check if payment is finalized
     */
    public function isFinalized()
    {
        return $this->status === PartialPaymentStatus::Finalized;
    }
}
