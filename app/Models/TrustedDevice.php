<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A browser a member or staff member chose to trust after a correct sign-in code. Only a hash of its token is kept. */
class TrustedDevice extends Model
{
    protected $fillable = ['token_hash', 'user_agent', 'ip_address', 'last_used_at', 'expires_at'];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function authenticatable()
    {
        return $this->morphTo();
    }
}
