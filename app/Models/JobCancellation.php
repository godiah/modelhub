<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobCancellation extends Model
{
    use HasFactory;

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
    ];

    protected $casts = [
        'process_payment_for_work' => 'boolean',
        'partial_payment_processed' => 'boolean',
        'is_dispute' => 'boolean',
        'dispute_resolved' => 'boolean',
        'payment_calculated_at' => 'datetime',
        'partial_payment_processed_at' => 'datetime',
        'dispute_resolved_at' => 'datetime',
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
}
