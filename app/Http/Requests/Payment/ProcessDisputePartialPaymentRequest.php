<?php

/**
 * ProcessDisputePartialPaymentRequest
 *
 * Handles validation for a freelancer disputing a partial payment,
 * including optional supporting evidence.
 */

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class ProcessDisputePartialPaymentRequest extends FormRequest
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
            'reason' => 'required|string|max:255',
            'details' => 'required|string',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ];
    }
}
