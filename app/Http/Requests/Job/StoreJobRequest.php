<?php

// StoreJobRequest validates a new project brief. A project needs a title, description, at least one skill and
// one piece of software, a budget, a cover image and either a deadline or "no fixed deadline".

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobRequest extends FormRequest
{
    use JobFormRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normaliseBriefInput();
    }

    public function rules(): array
    {
        return $this->briefRules() + [
            'image' => 'required|image|max:5120',
            // A deadline is a date and the project stays open through that day, so today is allowed.
            'deadline' => 'exclude_if:no_deadline,true|required|date|after_or_equal:today',
        ];
    }

    public function messages(): array
    {
        return $this->briefMessages() + ['image.required' => 'Add a cover image so freelancers can see what the project is about.'];
    }

    public function getProcessedData(): array
    {
        $validated = $this->validated();

        if (! empty($validated['no_deadline'])) {
            $validated['deadline'] = null;
        }

        $validated['no_deadline'] = (bool) ($validated['no_deadline'] ?? false);

        return $validated;
    }
}
