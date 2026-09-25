<?php

/**
 * UpdateStaffRoleRequest
 *
 * Handles validation for an admin assigning a staff role ('support'/'dispute_manager') to a user.
 */

namespace App\Http\Requests\Admin;

use App\Services\Admin\StaffManagementService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRoleRequest extends FormRequest
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
            'role' => ['nullable', 'string', Rule::in(StaffManagementService::ASSIGNABLE_ROLES)],
        ];
    }
}
