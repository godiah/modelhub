<?php

use App\Support\Staff\StaffAccess;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The staff permissions for withdrawals, and the Finance role. Idempotent: existing environments get them from here (Super admin gets every
 * permission through ensurePermissions), and a Finance role someone already created or edited is left alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        StaffAccess::ensurePermissions();

        $finance = StaffAccess::defaultRoles()['Finance'];

        if (! Role::where('name', 'Finance')->where('guard_name', StaffAccess::GUARD)->exists()) {
            Role::create(['name' => 'Finance', 'guard_name' => StaffAccess::GUARD, 'description' => $finance['description']])->syncPermissions($finance['permissions']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::where('name', 'Finance')->where('guard_name', StaffAccess::GUARD)->delete();
        Permission::whereIn('name', ['view payouts', 'approve payouts'])->where('guard_name', StaffAccess::GUARD)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
