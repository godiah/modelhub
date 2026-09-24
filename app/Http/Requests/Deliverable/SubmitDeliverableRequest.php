<?php

/**
 * SubmitDeliverableRequest
 *
 * Handles validation for submitting deliverable files and notes.
 */

namespace App\Http\Requests\Deliverable;

use Illuminate\Foundation\Http\FormRequest;

class SubmitDeliverableRequest extends FormRequest
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
            'submission_files' => 'array|max:5', // Limit to 5 files
            'submission_files.*' => 'file|max:10240', // 10MB max per file
            'submission_notes' => 'nullable|string|max:1000',
        ];
    }
}
