<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_active'
    ];

    // Scope to get active skills
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
