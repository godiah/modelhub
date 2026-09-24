<?php

// CheckTitleRequest validates AJAX requests to check the availability of a job title.
// This class ensures that the provided job title is valid and meets length requirements,
// typically used to verify title uniqueness before creating or updating a job posting.

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;

class CheckTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
        ];
    }

    public function getTitle(): string
    {
        return $this->input('title');
    }
}
