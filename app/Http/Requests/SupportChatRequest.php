<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupportChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Only these fields are ever forwarded. Who the member is does not come from here: it comes from the signed session.
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000', 'regex:/\S/'],
            'conversation_id' => ['nullable', 'uuid'],
            // Where the member is in the app: a hint for the opening of a chat, never used to decide what they may see
            'page_context' => ['nullable', 'array'],
            'page_context.route_key' => ['required_with:page_context', 'string', 'max:64', 'regex:/^[a-z0-9._-]+$/'],
            'page_context.entity_id' => ['nullable', 'string', 'max:64'],
        ];
    }
}
