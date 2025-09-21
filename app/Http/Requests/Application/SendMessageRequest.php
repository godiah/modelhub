<?php

// SendMessageRequest validates data for sending messages related to job applications, ensuring valid subject and message content.

namespace App\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ];
    }

    public function getMessageData(): array
    {
        return [
            'subject' => $this->subject,
            'message' => $this->message,
        ];
    }
}
