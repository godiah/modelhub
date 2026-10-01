<?php

namespace App\Models;

use App\Models\Concerns\HasTwoFactor;
use App\Notifications\StaffPasswordNotification;
use App\Support\Avatars;
use App\Support\Settings\PlatformSettings;
use App\Support\Staff\StaffAccess;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * A member of staff: their own account, apart from members (guard "staff", table `staff`). Roles and permissions attach
 * here and nowhere else. Staff cannot sell, hire, apply or buy: those belong to member accounts.
 */
class Staff extends Authenticatable
{
    use HasFactory, HasRoles, HasTwoFactor, Notifiable;

    protected $table = 'staff';

    protected string $guard_name = StaffAccess::GUARD;

    protected $fillable = ['name', 'email', 'password', 'avatar', 'is_active', 'last_login_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Staff $staff) {
            $staff->avatar ??= Avatars::random(Avatars::PEOPLE);
        });
    }

    protected function platformRequiresSecondFactor(): bool
    {
        return PlatformSettings::bool('security.otp_staff_required');
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(StaffAccess::SUPER_ADMIN);
    }

    public function avatarUrl(): string
    {
        return Avatars::url(Avatars::PEOPLE, $this->avatar, $this->id ?? $this->email ?? 0);
    }

    public function activity()
    {
        return $this->hasMany(StaffActivity::class);
    }

    /** Invitations and resets both send the same "set your password" link, to the staff portal. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new StaffPasswordNotification($token));
    }
}
