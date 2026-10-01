<?php

namespace App\Services\Admin;

use App\Models\Staff;
use App\Notifications\StaffPasswordNotification;
use App\Notifications\TwoFactorResetNotification;
use App\Support\Staff\StaffAccess;
use App\Support\Staff\StaffAudit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Staff accounts: invite someone, change their roles, deactivate or reactivate them. Two safeguards run through all of it:
 * you cannot lock yourself out, and the last active Super admin can never be demoted or deactivated.
 */
class StaffManagementService
{
    /** A new account: a random password nobody knows, and an email inviting them to set their own. */
    /** For a colleague who lost their phone and recovery codes: removes their authenticator app and tells them by email. */
    public function resetTwoFactor(Staff $staff, Staff $by): void
    {
        $staff->resetTwoFactor();
        $staff->notify(new TwoFactorResetNotification(route('admin.account.edit')));
        StaffAudit::log('staff.two-factor-reset', "Reset two-step sign-in for {$staff->name}", $staff, staffId: $by->id);
    }

    public function invite(string $name, string $email, array $roles): Staff
    {
        $staff = Staff::create(['name' => trim($name), 'email' => strtolower(trim($email)), 'password' => Hash::make(Str::random(64))]);
        $staff->syncRoles($this->validRoles($roles));

        $this->sendInvitation($staff);
        StaffAudit::log('staff.invited', "Invited {$staff->name} ({$staff->email})", $staff, ['roles' => $this->roleNames($staff)]);

        return $staff;
    }

    public function sendInvitation(Staff $staff): void
    {
        $staff->notify(new StaffPasswordNotification(Password::broker('staff')->createToken($staff), invited: true));
    }

    /** Returns why the change was refused, or null when it went through. */
    public function updateRoles(Staff $staff, array $roles): ?string
    {
        $new = $this->validRoles($roles);

        if ($staff->isSuperAdmin() && ! in_array(StaffAccess::SUPER_ADMIN, $new, true) && $this->isLastActiveSuperAdmin($staff)) {
            return 'There must always be at least one active Super admin, so this one cannot lose the role.';
        }

        $before = $this->roleNames($staff);
        $staff->syncRoles($new);

        if ($before !== $this->roleNames($staff)) {
            StaffAudit::log('staff.roles-changed', "Changed the roles of {$staff->name}", $staff, ['from' => $before, 'to' => $this->roleNames($staff)]);
        }

        return null;
    }

    public function deactivate(Staff $staff, Staff $actor): ?string
    {
        if ($staff->is($actor)) {
            return 'You cannot deactivate your own account.';
        }

        if ($this->isLastActiveSuperAdmin($staff)) {
            return 'There must always be at least one active Super admin, so this account cannot be deactivated.';
        }

        $staff->update(['is_active' => false]);
        StaffAudit::log('staff.deactivated', "Deactivated {$staff->name}", $staff);

        return null;
    }

    public function reactivate(Staff $staff): void
    {
        $staff->update(['is_active' => true]);
        StaffAudit::log('staff.reactivated', "Reactivated {$staff->name}", $staff);
    }

    private function isLastActiveSuperAdmin(Staff $staff): bool
    {
        return $staff->is_active && $staff->isSuperAdmin()
            && Staff::role(StaffAccess::SUPER_ADMIN)->where('is_active', true)->whereKeyNot($staff->id)->doesntExist();
    }

    /** Only roles that exist for the staff guard are accepted. @return list<string> */
    private function validRoles(array $roles): array
    {
        return Role::where('guard_name', StaffAccess::GUARD)->whereIn('name', $roles)->pluck('name')->all();
    }

    /** @return list<string> */
    private function roleNames(Staff $staff): array
    {
        return $staff->fresh()->roles->pluck('name')->sort()->values()->all();
    }
}
