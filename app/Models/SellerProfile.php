<?php

namespace App\Models;

use App\Enums\SellerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'status', 'display_name', 'bio', 'focus', 'portfolio_url', 'terms_accepted_at',
        'submitted_at', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected $casts = [
        'status' => SellerStatus::class,
        'terms_accepted_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isApproved(): bool
    {
        return $this->status === SellerStatus::Approved;
    }

    /** A rejected applicant may improve their application and send it again; a suspended seller may not. */
    public function canReapply(): bool
    {
        return $this->status === SellerStatus::Rejected;
    }
}
