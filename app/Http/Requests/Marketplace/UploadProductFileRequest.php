<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UploadProductFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit', $this->route('product'));
    }

    public function rules(): array
    {
        $extensions = collect(config('marketplace.formats'))->flatten()->unique()->values()->all();

        return [
            'file' => ['required', File::default()->max(config('marketplace.max_file_mb') * 1024)->extensions($extensions)],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max' => 'Each file can be up to '.config('marketplace.max_file_mb').' MB. Split larger models into several files or compress them.',
            'file.extensions' => 'That file type is not accepted. Use a model, texture, archive or document format.',
        ];
    }
}
