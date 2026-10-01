<?php

namespace App\Support\Staff;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * What staff can be allowed to do, and the roles that ship by default.
 *
 * The permission list lives here, in code, so a new permission is one line: sync() creates it and gives it to the
 * Super admin role. Roles are edited in the staff portal; the defaults below are created once and never overwritten,
 * except Super admin, which always holds every permission.
 */
final class StaffAccess
{
    public const GUARD = 'staff';

    public const SUPER_ADMIN = 'Super admin';

    /**
     * Permissions grouped by area, each with the label and explanation shown when editing a role.
     *
     * @return array<string, array<string, array{label: string, description: string}>>
     */
    public static function catalogue(): array
    {
        return [
            'Moderation' => [
                'review models' => ['label' => 'Review models', 'description' => 'See the models waiting for review, and publish them, ask for changes or take them down.'],
                'review sellers' => ['label' => 'Review sellers', 'description' => 'Approve, reject or suspend seller applications, and be told when a store is renamed.'],
                'moderate reviews' => ['label' => 'Moderate reviews', 'description' => 'Work the reported-reviews queue: hide or restore reviews, dismiss reports, remove seller replies.'],
            ],
            'Disputes' => [
                'view disputes' => ['label' => 'View disputes', 'description' => 'See payment disputes and open them, including the evidence. Cannot change them.'],
                'resolve disputes' => ['label' => 'Resolve disputes', 'description' => 'Take a dispute on and settle it with a final amount.'],
            ],
            'Access' => [
                'manage staff' => ['label' => 'Manage staff', 'description' => 'Invite staff, give them roles and deactivate their accounts.'],
                'manage roles' => ['label' => 'Manage roles', 'description' => 'Create and edit roles and what each one allows.'],
            ],
            'System' => [
                'view audit log' => ['label' => 'View the activity log', 'description' => 'See who did what in the staff portal, and when.'],
            ],
        ];
    }

    /** @return list<string> */
    public static function permissions(): array
    {
        return array_keys(array_merge(...array_values(self::catalogue())));
    }

    /**
     * @return array<string, array{description: string, permissions: list<string>}>
     */
    public static function defaultRoles(): array
    {
        return [
            self::SUPER_ADMIN => ['description' => 'Everything, including new permissions as they are added. Cannot be edited or deleted.', 'permissions' => self::permissions()],
            'Marketplace moderator' => ['description' => 'Reviews models and seller applications and moderates reviews.', 'permissions' => ['review models', 'review sellers', 'moderate reviews']],
            'Dispute manager' => ['description' => 'Takes on payment disputes and resolves them.', 'permissions' => ['view disputes', 'resolve disputes']],
            'Support' => ['description' => 'Can see payment disputes, read-only.', 'permissions' => ['view disputes']],
        ];
    }

    /**
     * Make sure every permission exists and Super admin holds all of them. Safe to run any time, and it never
     * recreates a default role someone deleted (use sync() for that, which is for installs and the seeder).
     */
    public static function ensurePermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::permissions() as $name) {
            Permission::findOrCreate($name, self::GUARD);
        }

        $definition = self::defaultRoles()[self::SUPER_ADMIN];
        $super = Role::where('name', self::SUPER_ADMIN)->where('guard_name', self::GUARD)->first()
            ?? Role::create(['name' => self::SUPER_ADMIN, 'guard_name' => self::GUARD, 'description' => $definition['description']]);

        $super->syncPermissions(self::permissions());
        $super->update(['description' => $definition['description']]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** A full install: every permission, Super admin, and any default role that does not exist yet (never overwriting an edited one). */
    public static function sync(): void
    {
        self::ensurePermissions();

        foreach (self::defaultRoles() as $name => $definition) {
            if (! Role::where('name', $name)->where('guard_name', self::GUARD)->exists()) {
                Role::create(['name' => $name, 'guard_name' => self::GUARD, 'description' => $definition['description']])->syncPermissions($definition['permissions']);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
