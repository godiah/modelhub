<?php

// JobFilterTrait provides reusable query scopes for filtering and sorting job queries.
// This trait includes methods for applying search, skills, software, and sorting filters to job queries,
// enabling consistent and modular filtering logic across different parts of the application.

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait JobFilterTrait
{
    // Apply search filter to job query
    public function scopeWithSearch(Builder $query, ?string $search): Builder
    {
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    // Apply skills filter to job query
    public function scopeWithSkills(Builder $query, ?array $skills): Builder
    {
        if (!empty($skills)) {
            foreach ($skills as $skill) {
                $query->whereJsonContains('skills', $skill);
            }
        }

        return $query;
    }

    // Apply software filter to job query
    public function scopeWithSoftware(Builder $query, ?array $software): Builder
    {
        if (!empty($software)) {
            foreach ($software as $soft) {
                $query->whereJsonContains('software', $soft);
            }
        }

        return $query;
    }

    // Apply sorting to job query
    public function scopeWithSorting(Builder $query, string $sort = 'newest'): Builder
    {
        switch ($sort) {
            case 'budget_high':
                $query->orderBy('budget', 'desc');
                break;
            case 'budget_low':
                $query->orderBy('budget', 'asc');
                break;
            case 'deadline':
                $query->whereNotNull('deadline')->orderBy('deadline', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        return $query;
    }
}
