<?php

namespace App\Http\Requests\Job;

use Illuminate\Validation\Rule;

/**
 * Rules the create and edit forms share: the brief (title, description, skills, software), budget and images.
 * The deadline and the image counts differ between the two and stay in their own requests.
 */
trait JobFormRules
{
    /** @return array<string, mixed> */
    protected function briefRules(?int $ignoreJobId = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255', Rule::unique('model_jobs', 'title')->ignore($ignoreJobId)],
            'description' => 'required|string|max:20000',
            'skills' => 'required|array|min:1|max:15',
            'skills.*' => ['string', Rule::exists('skills', 'name')->where('is_active', true)],
            'software' => 'required|array|min:1|max:15',
            'software.*' => ['string', Rule::exists('software', 'name')->where('is_active', true)],
            'budget' => 'required|numeric|min:1|max:99999999',
            'no_deadline' => 'boolean',
            'additional_images' => 'nullable|array|max:5',
            'additional_images.*' => 'image|max:5120',
        ];
    }

    /** @return array<string, string> */
    protected function briefMessages(): array
    {
        return [
            'skills.required' => 'Pick at least one skill.',
            'skills.min' => 'Pick at least one skill.',
            'software.required' => 'Pick at least one piece of software.',
            'software.min' => 'Pick at least one piece of software.',
            'skills.*.exists' => 'One of the selected skills is not available.',
            'software.*.exists' => 'One of the selected software options is not available.',
            'deadline.required' => 'Choose a deadline, or tick “No fixed deadline”.',
            'deadline.after_or_equal' => 'The deadline must be today or later.',
            'image.image' => 'The cover must be an image.',
            'image.max' => 'The cover image must not exceed 5MB.',
            'additional_images.max' => 'You can add up to 5 extra images.',
            'additional_images.*.image' => 'Each extra file must be an image.',
            'additional_images.*.max' => 'Each extra image must not exceed 5MB.',
        ];
    }

    protected function normaliseBriefInput(): void
    {
        $this->merge([
            'no_deadline' => $this->boolean('no_deadline'),
            'title' => is_string($this->input('title')) ? trim($this->input('title')) : $this->input('title'),
        ]);
    }
}
