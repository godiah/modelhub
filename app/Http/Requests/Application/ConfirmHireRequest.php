<?php

// ConfirmHireRequest validates data for confirming a job hire, including optional deliverables with titles, descriptions, and due dates.

namespace App\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmHireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'deliverables' => 'sometimes|array',
            'deliverables.*.title' => 'required|string|max:255',
            'deliverables.*.description' => 'nullable|string',
            'deliverables.*.due_date' => 'nullable|date',
        ];
    }

    public function getDeliverables(): array
    {
        return $this->input('deliverables', []);
    }

    /**
     * Check if deliverables are provided
     */
    public function hasDeliverables(): bool
    {
        return $this->has('deliverables') && !empty($this->deliverables);
    }
}
