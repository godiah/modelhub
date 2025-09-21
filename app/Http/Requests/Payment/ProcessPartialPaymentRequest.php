<?php

/**
 * ProcessPartialPaymentRequest
 * 
 * Handles validation for partial payment processing.
 * Validates payment amounts and optional notes for cancelled engagements.
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
            'payment_amount' => 'required|numeric|min:0',
            'payment_notes' => 'nullable|string|max:1000',
        ];
    }

    // Get payment data
    public function getPaymentData(): array
    {
        return [
            'payment_amount' => $this->payment_amount,
            'payment_notes' => $this->payment_notes,
        ];
    }

    // Get payment amount
    public function getPaymentAmount(): float
    {
        return (float) $this->payment_amount;
    }

    // Get payment notes
    public function getPaymentNotes(): ?string
    {
        return $this->payment_notes;
    }
}
