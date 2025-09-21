<?php

/**
 * ReopenJobRequest
 * 
 * Handles validation for job reopening operations.
 * Validates requests to reopen jobs after engagement cancellation or settlement.
*/

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;

class ReopenJobRequest extends FormRequest
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
            'confirm' => 'sometimes|boolean',
        ];
    }

    // Check if reopening is confirmed
    public function isConfirmed(): bool
    {
        return $this->boolean('confirm', true);
    }
}
