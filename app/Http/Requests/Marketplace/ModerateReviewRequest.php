<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

/** A reviewer hides a review and says why; the author is shown the reason. */
class ModerateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('moderate reviews');
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}
