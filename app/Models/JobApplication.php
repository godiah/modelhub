<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

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
        'status', // 'draft', 'submitted', 'hired, 'rejected', etc.
        'additional_notes',
    ];

    protected $casts = [
        'portfolio' => 'array',
        'terms_accepted' => 'boolean',
        'offer_amount' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
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

    // public function getRouteKeyName()
    // {
    //     return 'slug';
    // }
}
