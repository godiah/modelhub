<?php

use App\Support\Staff\StaffAccess;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The staff permissions for support tickets, and the Support role being able to answer them. Idempotent. Super admin gets every permission through
 * ensurePermissions; an existing Support role keeps whatever else it was given and is only ADDED the two everyday permissions (reading the assistant's
 * conversations is granted deliberately, to people, not to a whole role).
 */
return new class extends Migration
{
    public function up(): void
    {
        StaffAccess::ensurePermissions();

        $support = Role::where('name', 'Support')->where('guard_name', StaffAccess::GUARD)->first();
        $support?->givePermissionTo(['view support tickets', 'manage support tickets']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::whereIn('name', ['view support tickets', 'manage support tickets'])->where('guard_name', StaffAccess::GUARD)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
