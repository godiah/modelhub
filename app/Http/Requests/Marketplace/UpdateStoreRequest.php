<?php

namespace App\Http\Requests\Marketplace;

use App\Models\SellerProfile;
use App\Support\Avatars;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'avatar' => ['sometimes', 'string', fn ($attribute, $value, $fail) => Avatars::isValid(Avatars::STORES, $value) || $fail('Choose an avatar from the list.')],
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
        ];
    }
}
