<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
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
}
