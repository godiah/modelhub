<?php

namespace App\Http\Requests\Marketplace;

use App\Models\ProductReview;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** A member flags a review for the reviewers. */
class ReportReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::in(array_keys(ProductReview::REPORT_REASONS))],
            'details' => ['nullable', 'string', 'max:500'],
        ];
    }
}
