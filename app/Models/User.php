<?php

namespace App\Models;

use App\Enums\SellerStatus;
use App\Mail\TwoFactorCode;
use App\Support\Avatars;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
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

    /** The member's avatar picture; a stable fallback stands in if none is stored. */
    public function avatarUrl(): string
    {
        // A query that selected a few columns but not `avatar` would quietly show the fallback avatar instead of the
        // member's own. Outside production that is an error, like lazy loading, so it is caught in development.
        if ($this->exists && ! array_key_exists('avatar', $this->attributes) && ! app()->isProduction()) {
            throw new \LogicException('The avatar column was not selected for this user. Add it to the query that loaded the user.');
        }

        return Avatars::url(Avatars::PEOPLE, $this->avatar, $this->id ?? $this->email ?? 0);
    }

    protected static function booted(): void
    {
        // Everyone starts with a random avatar, so no one ever shows as a blank picture
        static::creating(function (User $user) {
            $user->avatar ??= Avatars::random(Avatars::PEOPLE);
        });
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
        if (! $this->two_factor_code || ! $this->two_factor_expires_at) {
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
        return (bool) $this->two_factor_enabled;
    }

    /**
     * Get the user's profile.
     */
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    /** The member's application to sell 3D models (and its outcome), if they have made one. */
    public function sellerProfile()
    {
        return $this->hasOne(SellerProfile::class);
    }

    public function wishlistItems()
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    /** "Amina O.": how a member is shown beside a review, so full names are not published. */
    public function publicName(): string
    {
        $words = array_values(array_filter(preg_split('/\s+/', trim((string) $this->name))));

        if (count($words) < 2) {
            return $words[0] ?? __('A buyer');
        }

        return $words[0].' '.mb_strtoupper(mb_substr(end($words), 0, 1)).'.';
    }

    /** The 3D model listings this member has created as a seller. */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /** Read from the database each time (one small query), so a decision made a moment ago is never missed. */
    public function isApprovedSeller(): bool
    {
        return SellerProfile::where('user_id', $this->id)->where('status', SellerStatus::Approved->value)->exists();
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
