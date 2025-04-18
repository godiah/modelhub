<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'engagement_id',
        'reviewer_id',
        'reviewee_id',
        'rating',
        'review',
        'tags',
        'is_public',
    ];

    protected $casts = [
        'rating' => 'integer',
        'tags' => 'array',
        'is_public' => 'boolean',
    ];

    /**
     * Get the engagement that this review is for
     */
    public function engagement()
    {
        return $this->belongsTo(JobEngagement::class, 'engagement_id');
    }

    /**
     * Get the user who wrote the review
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Get the user who received the review
     */
    public function reviewee()
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    /**
     * Scope to get reviews written by employers
     */
    public function scopeByEmployers($query)
    {
        return $query->whereHas('engagement', function ($q) {
            $q->whereHas('application', function ($app) {
                $app->whereColumn('reviewer_id', 'poster_id');
            });
        });
    }

    /**
     * Scope to get reviews written by freelancers
     */
    public function scopeByFreelancers($query)
    {
        return $query->whereHas('engagement', function ($q) {
            $q->whereHas('application', function ($app) {
                $app->whereColumn('reviewer_id', 'applicant_id');
            });
        });
    }

    /**
     * Scope to get public reviews only
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}
