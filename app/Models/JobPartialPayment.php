<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
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
}
