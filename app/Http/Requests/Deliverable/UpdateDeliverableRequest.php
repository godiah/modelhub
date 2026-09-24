<?php

/**
 * UpdateDeliverableRequest
 *
 * Handles validation for partially updating a deliverable's title,
 * description, or due date.
 */

namespace App\Http\Requests\Deliverable;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliverableRequest extends FormRequest
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
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'due_date' => 'sometimes|nullable|date|after_or_equal:today',
        ];
    }
}
