<?php

// StoreJobRequest handles the validation and processing of requests to create a new job posting.
// This class ensures that the provided data meets the requirements for creating a job, including
// title uniqueness, valid image uploads, and proper formatting of deadlines and budgets. It also
// processes the validated data to ensure consistent handling of optional fields and deadlines.

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => 'required|string|max:255|unique:model_jobs,title',
            'description' => 'required|string',
            'skills'      => 'sometimes|array',
            'software'    => 'sometimes|array',
            'image'       => 'sometimes|image|max:5120',
            'additional_images' => 'sometimes|array',
            'additional_images.*' => 'image|max:5120',
            'deadline'    => 'nullable|date|after:today',
            'no_deadline' => 'sometimes|boolean',
            'budget'      => 'required|numeric|min:0'
        ];
    }

    public function messages(): array
    {
        return [
            'deadline.after' => 'The deadline must be a future date.',
            'image.image'    => 'Uploaded file must be an image.',
            'image.max'      => 'Image size must not exceed 5MB.',
            'additional_images.*.image' => 'Each additional file must be an image.',
            'additional_images.*.max'   => 'Each additional image must not exceed 5MB.'
        ];
    }

    public function getProcessedData(): array
    {
        $validated = $this->validated();
        
        // Process deadline based on no_deadline flag
        if (!empty($validated['no_deadline'])) {
            $validated['deadline'] = null;
        }

        // Ensure arrays have default values
        $validated['skills'] = $validated['skills'] ?? [];
        $validated['software'] = $validated['software'] ?? [];
        $validated['no_deadline'] = $validated['no_deadline'] ?? false;

        return $validated;
    }
}
