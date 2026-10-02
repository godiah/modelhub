<?php

use App\Support\Staff\StaffAccess;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The staff permissions for looking at payments, refunding them and reading the ledger. Idempotent and additive: Super admin gets everything
 * through ensurePermissions, and an existing Finance or Auditor role gets the permissions it should have by default without losing anything
 * someone added or taking away anything they removed from other permissions.
 */
return new class extends Migration
{
    private array $grants = [
        'Finance' => ['view payments', 'refund payments', 'view ledger'],
        'Auditor' => ['view payments', 'view ledger'],
    ];

    public function up(): void
    {
        StaffAccess::ensurePermissions();

        foreach ($this->grants as $role => $permissions) {
            Role::where('name', $role)->where('guard_name', StaffAccess::GUARD)->first()?->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::whereIn('name', ['view payments', 'refund payments', 'view ledger'])->where('guard_name', StaffAccess::GUARD)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
