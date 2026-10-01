<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** A verified buyer's rating (1-5 stars) and written review of a model, with an optional reply from the seller. */
class ProductReview extends Model
{
    use HasFactory;

    public const REPORT_REASONS = [
        'spam' => 'Spam or advertising',
        'abusive' => 'Abusive or offensive',
        'fake' => 'Fake or not about this model',
        'other' => 'Something else',
    ];

    protected $fillable = [
        'product_id', 'user_id', 'purchase_id', 'rating', 'comment', 'status', 'hidden_by', 'hidden_at', 'hidden_reason',
        'seller_reply', 'seller_replied_at', 'edited_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'hidden_at' => 'datetime',
        'seller_replied_at' => 'datetime',
        'edited_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function hiddenBy()
    {
        return $this->belongsTo(User::class, 'hidden_by');
    }

    public function reports()
    {
        return $this->hasMany(ReviewReport::class, 'review_id');
    }

    public function scopeVisible($query)
    {
        return $query->where('product_reviews.status', 'visible');
    }

    public function isVisible(): bool
    {
        return $this->status === 'visible';
    }
}
