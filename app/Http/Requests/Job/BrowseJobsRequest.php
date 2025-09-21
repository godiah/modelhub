<?php

// BrowseJobsRequest handles validation and processing of requests for browsing job postings.
// This class validates search and filter parameters, such as search terms, skill and software IDs,
// and sorting options. It provides methods to retrieve validated filter values and check if the request
// is made via AJAX, facilitating dynamic job browsing functionality.

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;

class BrowseJobsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'sometimes|string|max:255',
            'skills' => 'sometimes|integer|exists:skills,id',
            'software' => 'sometimes|integer|exists:software,id', 
            'sort' => 'sometimes|string|in:budget_high,budget_low,deadline,newest',
        ];
    }

    public function getSearch(): ?string
    {
        return $this->input('search');
    }

    public function getSkillsFilter(): ?int
    {
        return $this->query('skills') ? (int) $this->query('skills') : null;
    }

    public function getSoftwareFilter(): ?int
    {
        return $this->query('software') ? (int) $this->query('software') : null;
    }

    public function getSortOption(): string
    {
        return $this->input('sort', 'newest');
    }

    public function getFilters(): array
    {
        return [
            'search' => $this->getSearch(),
            'skills' => $this->getSkillsFilter(),
            'software' => $this->getSoftwareFilter(),
            'sort' => $this->getSortOption()
        ];
    }

    public function isAjaxRequest(): bool
    {
        return $this->ajax();
    }
}
