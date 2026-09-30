<?php

// UpdateJobRequest validates edits to a project: the whole brief, budget, deadline, images and whether the
// project is taking applications. Only the poster may edit (ModelJobPolicy).

namespace App\Http\Requests\Job;

use App\Models\JobImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateJobRequest extends FormRequest
{
    use JobFormRules;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('job')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normaliseBriefInput();
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        $job = $this->route('job');

        return $this->briefRules($job->id) + [
            'image' => 'nullable|image|max:5120',
            'is_active' => 'boolean',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'integer',
            'deadline' => [
                'exclude_if:no_deadline,true',
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($job) {
                    // Keep an existing date as it is (even a past one on a closed project); a *new* date must not be in the past.
                    $unchanged = $job->deadline && $job->deadline->toDateString() === date('Y-m-d', strtotime((string) $value));
                    $keepsClosed = $unchanged && ! $this->boolean('is_active');

                    if (! $keepsClosed && strtotime((string) $value) < strtotime('today')) {
                        $fail($this->boolean('is_active') && $unchanged
                            ? 'This project’s deadline has passed. Choose a later date to accept applications again.'
                            : 'The deadline must be today or later.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return $this->briefMessages();
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $job = $this->route('job');

            // At most five extra images once removals and additions are applied.
            $removing = JobImage::where('model_job_id', $job->id)->whereIn('id', (array) $this->input('remove_images', []))->count();
            $total = $job->jobImages()->count() - $removing + count((array) $this->file('additional_images', []));

            if ($total > 5) {
                $validator->errors()->add('additional_images', 'A project can have up to 5 extra images. Remove some before adding more.');
            }

            if ($this->boolean('is_active') && ! $job->is_active) {
                if ($job->is_archived) {
                    $validator->errors()->add('is_active', 'Restore this project from your archive before accepting applications again.');
                } elseif ($job->hasEngagementInProgress()) {
                    $validator->errors()->add('is_active', 'A freelancer is already working on this project, so it cannot take new applications.');
                }
            }
        }];
    }

    public function getUpdateData(): array
    {
        $validated = $this->validated();

        return [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'skills' => array_values($validated['skills']),
            'software' => array_values($validated['software']),
            'budget' => $validated['budget'],
            'no_deadline' => $validated['no_deadline'],
            'deadline' => $validated['no_deadline'] ? null : $validated['deadline'],
            'is_active' => $validated['is_active'],
        ];
    }

    /** @return list<int> */
    public function getRemovedImageIds(): array
    {
        return array_map('intval', (array) $this->input('remove_images', []));
    }
}
