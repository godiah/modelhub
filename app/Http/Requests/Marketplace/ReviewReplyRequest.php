<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

/** The seller's public reply to a review. */
class ReviewReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reply' => ['required', 'string', 'min:2', 'max:1000'],
        ];
    }
}
