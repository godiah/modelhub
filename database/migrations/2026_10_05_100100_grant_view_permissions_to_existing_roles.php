<?php

use App\Support\Staff\StaffAccess;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The directories are new, and a default role is never overwritten once it exists. So that people who already review
 * models, sellers or disputes can still see what they are acting on, a role that holds an action permission is given
 * the matching view permission. (New default roles, Platform manager and Auditor, are created as well.)
 */
return new class extends Migration
{
    private const IMPLIED = [
        'review models' => ['view models'],
        'review sellers' => ['view sellers'],
        'resolve disputes' => ['read dispute messages', 'view engagements'],
    ];

    public function up(): void
    {
        StaffAccess::sync();

        Role::where('guard_name', StaffAccess::GUARD)->get()->each(function (Role $role) {
            foreach (self::IMPLIED as $held => $granted) {
                if ($role->hasPermissionTo($held)) {
                    $role->givePermissionTo($granted);
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void {}
};
