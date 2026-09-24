<?php

/**
 * ProcessPartialPaymentRequest
 *
 * Handles validation for processing a partial payment on a cancelled
 * engagement. The amount is optional — when omitted, the service
 * auto-calculates it from the ratio of approved deliverables; when
 * provided, it overrides that calculation.
 */

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPartialPaymentRequest extends FormRequest
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
            'payment_amount' => 'nullable|numeric|min:0.01',
            'payment_notes' => 'nullable|string|max:1000',
        ];
    }

    // Get the manually-entered payment amount, or null to auto-calculate
    public function getPaymentAmount(): ?float
    {
        return $this->filled('payment_amount') ? (float) $this->payment_amount : null;
    }

    // Get payment notes
    public function getPaymentNotes(): ?string
    {
        return $this->payment_notes;
    }
}
