<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewReport extends Model
{
    protected $fillable = ['review_id', 'user_id', 'reason', 'details', 'status', 'resolved_by', 'resolved_at'];

    protected $casts = ['resolved_at' => 'datetime'];

    public function review()
    {
        return $this->belongsTo(ProductReview::class, 'review_id');
    }

    public function resolver()
    {
        return $this->belongsTo(Staff::class, 'resolved_by');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
