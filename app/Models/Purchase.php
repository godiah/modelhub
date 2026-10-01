<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** A member bought a model. Reviews check this to mark (and allow) only genuine buyers. */
class Purchase extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'product_id', 'price_minor', 'currency', 'status', 'purchased_at'];

    protected $casts = ['price_minor' => 'integer', 'purchased_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
