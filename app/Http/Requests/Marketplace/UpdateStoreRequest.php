<?php

namespace App\Http\Requests\Marketplace;

use App\Models\SellerProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isApprovedSeller();
    }

    public function store(): SellerProfile
    {
        return SellerProfile::where('user_id', $this->user()->id)->firstOrFail();
    }

    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'min:2', 'max:60', Rule::unique('seller_profiles', 'display_name')->ignore($this->store()->id)],
            'tagline' => ['nullable', 'string', 'max:80'],
            'bio' => ['required', 'string', 'min:50', 'max:1000'],
            'focus' => ['required', 'string', 'min:10', 'max:500'],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'logo' => ['nullable', File::image()->max(config('marketplace.max_logo_mb') * 1024)->extensions(config('marketplace.image_extensions'))->dimensions(Rule::dimensions()->minWidth(200)->minHeight(200))],
            'remove_logo' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $store = $this->store();
                $renaming = trim((string) $this->input('display_name')) !== $store->display_name;

                if ($renaming && ($next = $store->nextNameChangeAt())) {
                    $validator->errors()->add('display_name', 'You can change your store name again on '.$next->format('M j, Y').'.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'display_name.unique' => 'Another seller already uses that store name.',
            'bio.min' => 'Tell us a little more about yourself (at least 50 characters).',
            'logo.max' => 'The logo can be up to '.config('marketplace.max_logo_mb').' MB.',
            'logo.dimensions' => 'The logo should be at least 200 × 200 pixels.',
        ];
    }
}
