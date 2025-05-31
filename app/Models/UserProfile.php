<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'avatar',
        'professional_info',
        'location',
        'telephone_number',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the avatar URL with fallback.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar && file_exists(storage_path('app/public/' . $this->avatar))) {
            return asset('storage/' . $this->avatar);
        }

        // Return default avatar or gravatar
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->user->name) . '&color=7F9CF5&background=EBF4FF';
    }
}
