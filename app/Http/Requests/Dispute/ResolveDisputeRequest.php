<?php

/**
 * ResolveDisputeRequest
 *
 * Handles validation for an admin resolving a payment dispute.
 */

namespace App\Http\Requests\Dispute;

use Illuminate\Foundation\Http\FormRequest;

class ResolveDisputeRequest extends FormRequest
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
            'resolution_notes' => 'required|string|max:1000',
            'resolution_amount' => 'nullable|numeric|min:0',
        ];
    }
}
