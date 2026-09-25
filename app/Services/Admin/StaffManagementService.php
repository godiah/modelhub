<?php

/**
 * StaffManagementService
 *
 * Lets an admin assign the 'support'/'dispute_manager' staff roles to a user. Deliberately
 * scoped to those two roles only — the 'admin' role itself is not managed here (assigned once,
 * outside the app, via AdminUserSeeder), so this flow can't be used to grant or strip admin access.
 */

namespace App\Services\Admin;

use App\Models\User;

class StaffManagementService
{
    public const ASSIGNABLE_ROLES = ['support', 'dispute_manager'];

    /**
     * Users eligible for staff-role assignment (everyone except existing admins), with their
     * current role preloaded for display.
     */
    public function getStaffAssignableUsers()
    {
        return User::whereDoesntHave('roles', fn ($query) => $query->where('name', 'admin'))
            ->with('roles')
            ->orderBy('name')
            ->paginate(15);
    }

    public function updateRole(User $user, ?string $role): void
    {
        if ($user->hasRole('admin')) {
            throw new \Exception('The admin role cannot be changed here.');
        }

        $user->syncRoles($role ? [$role] : []);
    }
}
