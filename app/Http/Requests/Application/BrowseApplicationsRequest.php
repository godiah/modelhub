<?php

// BrowseApplicationsRequest validates filtering and sorting parameters for 
// browsing job applications, supporting status, sort, and search filters.

namespace App\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;

class BrowseApplicationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|string|in:all,submitted,reviewed,hired,rejected,withdrawn',
            'sort' => 'sometimes|string|in:date_desc,date_asc,status',
            'search' => 'sometimes|string|max:255',
        ];
    }

    public function getActiveFilters(): array
    {
        return [
            'status' => $this->filled('status') && $this->status !== 'all' ? $this->status : 'all',
            'sort' => $this->filled('sort') ? $this->sort : 'date_desc',
            'search' => $this->search,
        ];
    }

    public function getStatusFilter(): string
    {
        return $this->input('status', 'all');
    }

    public function getSortOption(): string
    {
        return $this->input('sort', 'date_desc');
    }

    public function getSearchTerm(): ?string
    {
        return $this->input('search');
    }

    public function isAjaxRequest(): bool
    {
        return $this->ajax();
    }

    public function hasActiveFilters(): bool
    {
        return ($this->has('search') && !empty($this->search)) ||
               ($this->has('status') && $this->status !== 'all');
    }
}
