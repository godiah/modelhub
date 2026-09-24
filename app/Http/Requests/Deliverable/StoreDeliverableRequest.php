<?php

/**
 * StoreDeliverableRequest
 *
 * Handles validation for adding a new deliverable to an engagement.
 */

namespace App\Http\Requests\Deliverable;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliverableRequest extends FormRequest
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
            'del_title' => 'required|string|max:255',
            'del_description' => 'nullable|string',
            'due_date' => 'nullable|date|after:today',
        ];
    }

    // Get deliverable data ready for creation
    public function getDeliverableData(): array
    {
        return [
            'title' => $this->del_title,
            'description' => $this->del_description,
            'due_date' => $this->due_date,
            'status' => 'pending',
        ];
    }
}
