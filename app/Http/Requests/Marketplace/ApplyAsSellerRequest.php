<?php

namespace App\Http\Requests\Marketplace;

use App\Models\SellerProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyAsSellerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // The store name is public and unique; the applicant's own existing profile (a reapplication) may keep theirs.
            'display_name' => ['required', 'string', 'min:2', 'max:60', Rule::unique('seller_profiles', 'display_name')->ignore(SellerProfile::where('user_id', $this->user()->id)->value('id'))],
            'bio' => ['required', 'string', 'min:50', 'max:1000'],
            'focus' => ['required', 'string', 'min:10', 'max:500'],
            'portfolio_url' => ['nullable', 'url:http,https', 'max:255'],
            'terms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'terms.accepted' => 'You need to agree to the Terms of Service to apply.',
            'display_name.unique' => 'Another seller already uses that store name.',
            'bio.min' => 'Tell us a little more about yourself (at least 50 characters).',
        ];
    }
}
