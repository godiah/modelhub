<?php

namespace App\Models;

use App\Mail\TwoFactorCode;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'two_factor_enabled',
        'two_factor_code',
        'two_factor_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'two_factor_expires_at' => 'datetime',
        ];
    }

    public function jobs()
    {
        return $this->hasMany(ModelJob::class);
    }

    // Get User Initials
    public function getInitials()
    {
        $name = $this->name;
        if (empty($name)) {
            return '';
        }

        // Split name into words
        $words = explode(' ', $name);
        $initials = '';

        // Take first letter of each word
        foreach ($words as $word) {
            if (!empty(trim($word))) {
                $initials .= strtoupper($word[0]);
            }
        }

        // Limit to 2-3 initials for display
        return substr($initials, 0, 3);
    }

    /**
     * Generate and send a two-factor authentication code.
     */
    public function generateTwoFactorCode(): void
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->update([
            'two_factor_code' => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(5),
        ]);

        Mail::to($this->email)->queue(new TwoFactorCode($code));
    }

    /**
     * Verify the two-factor authentication code.
     */
    public function verifyTwoFactorCode(string $code): bool
    {
        if (!$this->two_factor_code || !$this->two_factor_expires_at) {
            return false;
        }

        if ($this->two_factor_expires_at->isPast()) {
            $this->clearTwoFactorCode();
            return false;
        }

        return Hash::check($code, $this->two_factor_code);
    }

    /**
     * Clear the two-factor authentication code.
     */
    public function clearTwoFactorCode(): void
    {
        $this->update([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ]);
    }

    /**
     * Enable two-factor authentication.
     */
    public function enableTwoFactor(): void
    {
        $this->update(['two_factor_enabled' => true]);
        $this->clearTwoFactorCode();
    }

    /**
     * Disable two-factor authentication.
     */
    public function disableTwoFactor(): void
    {
        $this->update(['two_factor_enabled' => false]);
        $this->clearTwoFactorCode();
    }

    /**
     * Check if two-factor authentication is enabled.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled;
    }

    /**
     * Get the user's profile.
     */
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Get the user's skills.
     */
    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'user_skills')
            ->withTimestamps();
    }

    /**
     * Get the user's preferred software.
     */
    public function software()
    {
        return $this->belongsToMany(Software::class, 'user_software')
            ->withTimestamps();
    }

    /**
     * Create profile if it doesn't exist.
     */
    public function getOrCreateProfile(): UserProfile
    {
        return $this->profile ?: $this->profile()->create();
    }

    /**
     * Get the user's social links.
     */
    public function socialLinks()
    {
        return $this->hasMany(UserSocialLink::class);
    }

    /**
     * Get the user's public social links.
     */
    public function publicSocialLinks()
    {
        return $this->socialLinks()->public()->ordered();
    }

    /**
     * Get reviews where this user is the reviewee (received reviews).
     */
    public function reviewsReceived()
    {
        return $this->hasMany(JobReview::class, 'reviewee_id');
    }

    /**
     * Get reviews where this user is the reviewer (given reviews).
     */
    public function reviewsGiven()
    {
        return $this->hasMany(JobReview::class, 'reviewer_id');
    }

    /**
     * Get public reviews received by this user.
     */
    public function publicReviewsReceived()
    {
        return $this->reviewsReceived()
            ->where('is_public', true)
            ->with(['reviewer', 'engagement'])
            ->orderBy('created_at', 'desc');
    }

    /**
     * Calculate average rating for this user.
     */
    public function getAverageRatingAttribute()
    {
        return $this->reviewsReceived()
            ->where('is_public', true)
            ->avg('rating') ?: 0;
    }

    /**
     * Get total count of public reviews received.
     */
    public function getTotalReviewsAttribute()
    {
        return $this->reviewsReceived()
            ->where('is_public', true)
            ->count();
    }
}
