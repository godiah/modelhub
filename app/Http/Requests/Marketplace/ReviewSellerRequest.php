<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

class ReviewSellerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review sellers');
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
