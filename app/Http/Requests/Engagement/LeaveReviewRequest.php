<?php

/**
 * LeaveReviewRequest
 * 
 * Handles validation for engagement review submissions.
 * Validates rating, review content, tags, and privacy settings.
*/

namespace App\Http\Requests\Engagement;

use Illuminate\Foundation\Http\FormRequest;

class LeaveReviewRequest extends FormRequest
{
    // Determine if the user is authorized to make this request
    public function authorize(): bool
    {
        return true;
    }

    // Get the validation rules that apply to the request
    public function rules(): array
    {
        return [
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string|min:10',
            'tags' => 'nullable|array',
            'is_public' => 'nullable|boolean',
        ];
    }

    // Get review data for creation
    public function getReviewData(): array
    {
        return [
            'rating' => $this->rating,
            'review' => $this->review,
            'tags' => $this->tags ?? [],
            'is_public' => $this->is_public ?? true,
        ];
    }

    // Check if review is public
    public function isPublic(): bool
    {
        return $this->boolean('is_public', true);
    }

    // Get tags array
    public function getTags(): array
    {
        return $this->input('tags', []);
    }
}