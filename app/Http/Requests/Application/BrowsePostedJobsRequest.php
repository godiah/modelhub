<?php

// BrowsePostedJobsRequest validates filtering and sorting parameters for browsing posted jobs, supporting status and sort options.

namespace App\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;

class BrowsePostedJobsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|string|in:all,active,closed,inactive',
            'sort' => 'sometimes|string|in:newest,deadline,budget_high,budget_low',
            'search' => 'nullable|string|max:100',
        ];
    }

    public function getStatusFilter(): string
    {
        return $this->input('status', 'all');
    }

    public function getSearch(): ?string
    {
        $search = trim((string) $this->input('search'));

        return $search === '' ? null : $search;
    }

    public function getSortOption(): string
    {
        return $this->input('sort', 'newest');
    }

    public function hasActiveFilters(): bool
    {
        return ($this->has('status') && $this->status !== 'all') ||
               ($this->has('sort') && $this->sort !== 'newest') ||
               $this->getSearch() !== null;
    }

    public function isAjaxRequest(): bool
    {
        return $this->ajax();
    }
}
