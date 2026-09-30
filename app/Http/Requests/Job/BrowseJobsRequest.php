<?php

// BrowseJobsRequest handles validation and processing of requests for browsing job postings.
// This class validates search and filter parameters, such as search terms, skill and software IDs,
// budget range, posting window and sorting options. It provides methods to retrieve validated filter
// values and check if the request is made via AJAX, facilitating dynamic job browsing functionality.

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class BrowseJobsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Older links carry a single `skills=3`; the filter rail sends `skills[]=3&skills[]=4`.
    protected function prepareForValidation(): void
    {
        foreach (['skills', 'software'] as $key) {
            if ($this->has($key)) {
                $this->merge([$key => array_values(array_filter(Arr::wrap($this->input($key)), fn ($value) => $value !== null && $value !== ''))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'skills' => 'nullable|array|max:25',
            'skills.*' => 'integer|exists:skills,id',
            'software' => 'nullable|array|max:25',
            'software.*' => 'integer|exists:software,id',
            'budget_min' => 'nullable|numeric|min:0|max:99999999',
            'budget_max' => 'nullable|numeric|min:0|max:99999999|gte:budget_min',
            'posted' => 'nullable|string|in:day,week,month',
            'sort' => 'nullable|string|in:budget_high,budget_low,deadline,newest',
            'page' => 'nullable|integer|min:1',
        ];
    }

    public function getSearch(): ?string
    {
        $search = trim((string) $this->input('search'));

        return $search === '' ? null : $search;
    }

    /** @return list<int> */
    public function getSkillsFilter(): array
    {
        return array_map('intval', (array) $this->input('skills', []));
    }

    /** @return list<int> */
    public function getSoftwareFilter(): array
    {
        return array_map('intval', (array) $this->input('software', []));
    }

    public function getSortOption(): string
    {
        return $this->input('sort') ?: 'newest';
    }

    public function getFilters(): array
    {
        return [
            'search' => $this->getSearch(),
            'skills' => $this->getSkillsFilter(),
            'software' => $this->getSoftwareFilter(),
            'budget_min' => $this->filled('budget_min') ? (float) $this->input('budget_min') : null,
            'budget_max' => $this->filled('budget_max') ? (float) $this->input('budget_max') : null,
            'posted' => $this->input('posted') ?: null,
            'sort' => $this->getSortOption(),
        ];
    }

    public function isAjaxRequest(): bool
    {
        return $this->ajax();
    }
}
