<?php

// UpdateJobRequest manages the validation and processing of requests to update an existing job posting.
// This class validates optional fields like budget, deadline, and job status, ensuring data integrity
// during updates. It also processes the validated data to handle the no_deadline flag and converts
// relevant fields to appropriate types for database storage.

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'budget' => 'nullable|integer',
            'deadline' => 'nullable|date',
            'no_deadline' => 'required|boolean',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function getUpdateData(): array
    {
        $updateData = [
            'no_deadline' => $this->no_deadline,
            'deadline' => $this->no_deadline ? null : $this->deadline,
            'is_active' => $this->boolean('is_active'),
        ];

        if ($this->filled('budget')) {
            $updateData['budget'] = intval($this->budget);
        }

        return $updateData;
    }
}
