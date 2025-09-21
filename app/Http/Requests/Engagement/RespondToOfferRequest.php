<?php

/**
 * RespondToOfferRequest
 * 
 * Handles validation for engagement offer responses.
 * Validates acceptance or rejection of job engagement offers.
*/

namespace App\Http\Requests\Engagement;

use Illuminate\Foundation\Http\FormRequest;

class RespondToOfferRequest extends FormRequest
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
            'response' => 'required|in:accepted,declined',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    // Check if the response is accepted
    public function isAccepted(): bool
    {
        return $this->response === 'accepted';
    }

    // Check if the response is declined
    public function isDeclined(): bool
    {
        return $this->response === 'declined';
    }

    // Get response data
    public function getResponseData(): array
    {
        return [
            'response' => $this->response,
            'notes' => $this->notes,
        ];
    }
}
