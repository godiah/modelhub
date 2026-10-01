<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

/** A buyer's star rating and written review of a model. */
class ProductReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Choose a star rating from 1 to 5.',
            'rating.between' => 'Choose a star rating from 1 to 5.',
            'comment.required' => 'Write a few words about the model.',
            'comment.min' => 'Write at least 10 characters so the review is useful to other buyers.',
        ];
    }
}
