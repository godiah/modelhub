<?php

/**
 * ApproveDeliverableRequest
 *
 * Handles validation for approving a submitted deliverable.
 */

namespace App\Http\Requests\Deliverable;

use Illuminate\Foundation\Http\FormRequest;

class ApproveDeliverableRequest extends FormRequest
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
            'feedback' => 'nullable|string|max:1000',
        ];
    }
}
