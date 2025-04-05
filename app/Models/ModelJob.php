<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'skills',
        'software',
        'images',
        'deadline',
        'no_deadline',
        'budget',
        'is_active',
    ];

    protected $casts = [
        'skills' => 'array',
        'software' => 'array',
        'no_deadline' => 'boolean',
        'deadline' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Check if job is still active
    public function isActive()
    {
        if ($this->no_deadline || is_null($this->deadline)) {
            return true;
        }
        return Carbon::now()->lessThanOrEqualTo($this->deadline);
    }

    // Ensures binding by slug
    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class, 'job_id');
    }

    //  Scope to get active jobs
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('no_deadline', true)
                ->orWhere('deadline', '>=', now());
        });
    }
}
