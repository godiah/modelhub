<?php

use App\Support\Staff\StaffAccess;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * The permission to read a member's whole conversation with the assistant. Created (Super admin gets it through ensurePermissions) but granted to
 * NO role here: reading a member's chat is given deliberately, to people who need it, and every reading is recorded.
 */
return new class extends Migration
{
    public function up(): void
    {
        StaffAccess::ensurePermissions();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::where('name', 'read support transcripts')->where('guard_name', StaffAccess::GUARD)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
