<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UploadProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit', $this->route('product'));
    }

    public function rules(): array
    {
        return [
            'image' => ['required', File::image()->max(config('marketplace.max_image_mb') * 1024)->extensions(config('marketplace.image_extensions'))->dimensions(Rule::dimensions()->minWidth(400)->minHeight(300))],
        ];
    }

    public function messages(): array
    {
        return [
            'image.max' => 'Each image can be up to '.config('marketplace.max_image_mb').' MB.',
            'image.dimensions' => 'Preview images should be at least 400 × 300 pixels.',
        ];
    }
}
