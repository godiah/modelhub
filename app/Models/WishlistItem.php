<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A model a member saved to come back to. Only published models show up on the wishlist; the rest stay saved but hidden. */
class WishlistItem extends Model
{
    protected $fillable = ['user_id', 'product_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
