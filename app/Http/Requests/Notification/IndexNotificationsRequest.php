<?php

namespace App\Http\Requests\Notification;

use App\Enums\NotificationCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexNotificationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'filter' => ['nullable', 'string', Rule::in(['all', 'unread', ...array_column(NotificationCategory::cases(), 'value')])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** The active filter: "all", "unread", or a NotificationCategory value. */
    public function filter(): string
    {
        return $this->validated('filter') ?? 'all';
    }
}
