<?php

/**
 * RejectDeliverableRequest
 *
 * Handles validation for rejecting a submitted deliverable. Feedback is
 * required unless the engagement has been cancelled.
 */

namespace App\Http\Requests\Deliverable;

use App\Enums\EngagementStatus;
use Illuminate\Foundation\Http\FormRequest;

class RejectDeliverableRequest extends FormRequest
{
    // Determine if the user is authorized to make this request
    public function authorize(): bool
    {
        return true;
    }

    // Get the validation rules that apply to the request
    public function rules(): array
    {
        $rules = [];

        if ($this->route('deliverable')->engagement->status !== EngagementStatus::Cancelled) {
            $rules['feedback'] = 'required|string|max:1000';
        }

        return $rules;
    }
}
