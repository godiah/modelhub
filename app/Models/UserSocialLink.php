<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSocialLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'social_network_id',
        'username',
        'url',
        'display_name',
        'is_public',
        'sort_order',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Get the user that owns the social link.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the social network for this link.
     */
    public function socialNetwork()
    {
        return $this->belongsTo(SocialNetwork::class);
    }

    /**
     * Scope to get public social links only.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get social links ordered by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get the display name or fallback to username.
     */
    public function getDisplayNameAttribute($value): string
    {
        return $value ?: $this->username ?: 'Link';
    }

    /**
     * Validate and clean URL.
     */
    public function setUrlAttribute($value): void
    {
        // Ensure URL has protocol
        if ($value && !preg_match('/^https?:\/\//', $value)) {
            $value = 'https://' . $value;
        }

        $this->attributes['url'] = $value;
    }
}
