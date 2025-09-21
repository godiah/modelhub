<?php

// UpdateApplicationStatusRequest validates data for updating the status of a job application, including optional notes.

namespace App\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:submitted,reviewed,hired,rejected',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function isHiring(): bool
    {
        return $this->status === 'hired';
    }

    public function getUpdateData(): array
    {
        $data = [
            'status' => $this->status,
        ];

        if ($this->filled('notes')) {
            $data['additional_notes'] = $this->notes;
        }

        return $data;
    }
}
