<?php

// StoreApplicationRequest validates and processes data for creating or updating job applications,
// supporting both draft and submitted states.

namespace App\Http\Requests\Application;

use App\Models\ModelJob;
use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isDraft = $this->input('action') === 'draft';

        $rules = [
            'job_id' => 'required|exists:model_jobs,id',
            'portfolio' => 'nullable|array|max:5',
            'portfolio.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240', // 10MB max
            'existing_portfolio' => 'sometimes|array',
            'removed_files' => 'sometimes|array',
        ];

        // Add stricter validation for submission (not for draft)
        if (! $isDraft) {
            $rules['offer'] = 'required|numeric|min:1';
            $rules['terms'] = 'required|accepted';
        } else {
            // For drafts, make offer optional
            $rules['offer'] = 'nullable|numeric|min:1';
            $rules['proposal'] = 'nullable|string|max:2500';
        }

        return $rules;
    }

    public function isDraft(): bool
    {
        return $this->input('action') === 'draft';
    }

    public function getStatus(): string
    {
        return $this->isDraft() ? 'draft' : 'submitted';
    }

    // The applicant is always the signed-in user and the poster is always the job's owner — neither is
    // read from the form, or an applicant could name themselves as the poster of someone else's job.
    public function getProcessedData(): array
    {
        $job = ModelJob::findOrFail($this->job_id);

        $data = [
            'job_id' => $job->id,
            'applicant_id' => $this->user()->id,
            'poster_id' => $job->user_id,
            'offer_amount' => $this->offer ?? 0,
            'proposal' => $this->proposal,
            'status' => $this->getStatus(),
            'existing_portfolio' => $this->existing_portfolio ?? [],
            'removed_files' => $this->removed_files ?? [],
        ];

        // Only set terms_accepted if provided (required for submission, optional for draft)
        if ($this->has('terms')) {
            $data['terms_accepted'] = true;
        } elseif (! $this->isDraft()) {
            // For submissions, terms must be accepted
            $data['terms_accepted'] = true;
        }

        return $data;
    }
}
