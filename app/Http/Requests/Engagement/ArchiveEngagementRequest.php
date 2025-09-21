<?php

/**
 * ArchiveEngagementRequest
 * 
 * Handles validation for engagement archive and restore operations.
 * Validates engagement ID for archiving/unarchiving operations.
*/

namespace App\Http\Requests\Engagement;

use Illuminate\Foundation\Http\FormRequest;

class ArchiveEngagementRequest extends FormRequest
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
            'engagement_id' => 'required|exists:job_engagements,id',
        ];
    }

    // Get engagement ID
    public function getEngagementId(): int
    {
        return (int) $this->engagement_id;
    }
}
