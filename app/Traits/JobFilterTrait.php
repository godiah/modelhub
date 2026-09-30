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
        if (! empty($search)) {
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
        if (! empty($skills)) {
            foreach ($skills as $skill) {
                $query->whereJsonContains('skills', $skill);
            }
        }

        return $query;
    }

    // Apply software filter to job query
    public function scopeWithSoftware(Builder $query, ?array $software): Builder
    {
        if (! empty($software)) {
            foreach ($software as $soft) {
                $query->whereJsonContains('software', $soft);
            }
        }

        return $query;
    }

    // Jobs that need ANY of the given skills (browse filter: ticking more boxes widens the results)
    public function scopeWithAnySkill(Builder $query, ?array $skills): Builder
    {
        return $this->matchAnyJsonValue($query, 'skills', $skills);
    }

    // Jobs that need ANY of the given software
    public function scopeWithAnySoftware(Builder $query, ?array $software): Builder
    {
        return $this->matchAnyJsonValue($query, 'software', $software);
    }

    // Budget between the given bounds; either bound may be omitted
    public function scopeWithBudgetBetween(Builder $query, ?float $min, ?float $max): Builder
    {
        if ($min !== null) {
            $query->where('budget', '>=', $min);
        }

        if ($max !== null) {
            $query->where('budget', '<=', $max);
        }

        return $query;
    }

    // Posted within the last day / week / month
    public function scopePostedWithin(Builder $query, ?string $window): Builder
    {
        $since = match ($window) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            default => null,
        };

        return $since ? $query->where('created_at', '>=', $since) : $query;
    }

    private function matchAnyJsonValue(Builder $query, string $column, ?array $values): Builder
    {
        $values = array_values(array_filter((array) $values));

        if ($values === []) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($column, $values) {
            foreach ($values as $value) {
                $q->orWhereJsonContains($column, $value);
            }
        });
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
