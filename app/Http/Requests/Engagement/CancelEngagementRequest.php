<?php

/**
 * CancelEngagementRequest
 * 
 * Handles validation for engagement cancellation submissions.
 * Validates cancellation type, reason, and required terms acceptance.
*/

namespace App\Http\Requests\Engagement;

use Illuminate\Foundation\Http\FormRequest;

class CancelEngagementRequest extends FormRequest
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
            'cancellation_type' => 'required|string|in:mutual,client_initiated,freelancer_initiated,dispute',
            'reason_category' => 'required|string',
            'cancellation_reason' => 'required|string|min:10',
            'terms' => 'required|accepted',
        ];
    }

    // Get cancellation data
    public function getCancellationData(): array
    {
        return [
            'cancellation_type' => $this->cancellation_type,
            'reason_category' => $this->reason_category,
            'cancellation_reason' => $this->cancellation_reason,
        ];
    }

    // Check if cancellation is a dispute
    public function isDispute(): bool
    {
        return $this->cancellation_type === 'dispute';
    }

    // Get cancellation type
    public function getCancellationType(): string
    {
        return $this->cancellation_type;
    }

    // Get reason details
    public function getReasonDetails(): string
    {
        return $this->cancellation_reason;
    }
}
