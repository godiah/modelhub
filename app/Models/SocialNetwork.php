<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialNetwork extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'color',
        'base_url',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope to get active social networks.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get social networks ordered by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get all user social links for this network.
     */
    public function userSocialLinks()
    {
        return $this->hasMany(UserSocialLink::class);
    }

    /**
     * Generate a full URL from username for this social network.
     */
    public function generateUrl(string $username): string
    {
        if (empty($this->base_url)) {
            return $username; // For websites and custom URLs
        }

        return $this->base_url . $username;
    }
}
