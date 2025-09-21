<?php

/**
 * BrowseEngagementsRequest
 * 
 * Handles validation for engagement listing and filtering operations.
 * Manages search terms, status filters, and pagination parameters.
*/

namespace App\Http\Requests\Engagement;

use Illuminate\Foundation\Http\FormRequest;

class BrowseEngagementsRequest extends FormRequest
{
    // Determine if the user is authorized to make this request
    public function authorize(): bool
    {
        return true;
    }

    // Get the validation rules that apply to the request
    public function rules(): array
    {
        return [
            'search' => 'sometimes|string|max:255',
            'status' => 'sometimes|string|in:all,employer_accepted,active,completed,cancelled,disputed,settled',
        ];
    }

    // Get search term
    public function getSearchTerm(): ?string
    {
        return $this->input('search');
    }

    // Get status filter
    public function getStatusFilter(): string
    {
        return $this->input('status', 'all');
    }

    // Check if filters are active
    public function hasActiveFilters(): bool
    {
        return $this->filled('search') || 
               ($this->filled('status') && $this->status !== 'all');
    }

    // Check if request is AJAX
    public function isAjaxRequest(): bool
    {
        return $this->ajax();
    }
}
