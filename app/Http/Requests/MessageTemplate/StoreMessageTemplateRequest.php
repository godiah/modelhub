<?php

namespace App\Http\Requests\MessageTemplate;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ];
    }

    public function getTemplateData(): array
    {
        return [
            'user_id' => $this->user()->id,
            'name' => $this->name,
            'subject' => $this->subject,
            'message' => $this->message,
        ];
    }
}
