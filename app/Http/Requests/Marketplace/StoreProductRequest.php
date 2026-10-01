<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    use ProductRules;

    public function authorize(): bool
    {
        return $this->user()->isApprovedSeller();
    }

    public function rules(): array
    {
        return [
            'title' => $this->titleRule(),
            'category_id' => $this->categoryRule(),
            'price' => $this->priceRule(),
        ];
    }
}
