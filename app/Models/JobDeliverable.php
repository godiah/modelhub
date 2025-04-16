<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobDeliverable extends Model
{
    use HasFactory;

    protected $fillable = [
        'engagement_id',
        'title',
        'description',
        'due_date',
        'status',
        'submission_notes',
        'submission_files',
        'feedback',
        'submitted_at',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'submission_files' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Get the engagement that owns this deliverable
     */
    public function engagement()
    {
        return $this->belongsTo(JobEngagement::class, 'engagement_id');
    }

    /**
     * Check if the deliverable is overdue
     */
    public function isOverdue()
    {
        if (!$this->due_date) {
            return false;
        }

        return $this->due_date->isPast() && $this->status === 'pending';
    }

    /**
     * Check if the deliverable is submitted
     */
    public function isSubmitted()
    {
        return in_array($this->status, ['submitted', 'approved', 'rejected']);
    }

    /**
     * Check if the deliverable is approved
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Get the number of days remaining until due date
     */
    public function daysRemaining()
    {
        if (!$this->due_date || $this->status !== 'pending') {
            return null;
        }

        return now()->diffInDays($this->due_date, false);
    }
}
