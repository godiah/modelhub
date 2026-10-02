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
            'Platform' => [
                'view platform overview' => ['label' => 'View the platform overview', 'description' => 'See platform-wide numbers: members, projects, hires, models and escrow, with trends.'],
            ],
            'Members' => [
                'view members' => ['label' => 'View members', 'description' => 'Search the member directory and open a member: their activity, projects, models and staff notes. Email addresses and phone numbers stay masked.'],
                'view contact details' => ['label' => 'View contact details', 'description' => 'See members\' full email addresses and phone numbers. Each time is recorded in the activity log.'],
                'manage members' => ['label' => 'Manage members', 'description' => 'Suspend and reinstate accounts, add staff notes, send password-reset and verification emails.'],
            ],
            'Projects and hires' => [
                'view projects' => ['label' => 'View projects', 'description' => 'Browse every project on the board and its applicants, whatever its status.'],
                'moderate projects' => ['label' => 'Moderate projects', 'description' => 'Take a project down (with a reason the poster sees) and restore it.'],
                'view engagements' => ['label' => 'View engagements', 'description' => 'Read-only oversight of hires: parties, amounts, escrow dates and deliverable progress. Messages are not included.'],
            ],
            'Models and stores' => [
                'view models' => ['label' => 'View models', 'description' => 'Browse every model in every status, with its files, reviews and review history.'],
                'review models' => ['label' => 'Review models', 'description' => 'Work the review queue and publish models, ask for changes or take them down.'],
                'view sellers' => ['label' => 'View stores', 'description' => 'Browse every store, whatever its status, with its models and rating.'],
                'review sellers' => ['label' => 'Review sellers', 'description' => 'Approve, reject or suspend seller applications, and be told when a store is renamed.'],
                'moderate reviews' => ['label' => 'Moderate reviews', 'description' => 'Work the reported-reviews queue: hide or restore reviews, dismiss reports, remove seller replies.'],
            ],
            'Disputes' => [
                'view disputes' => ['label' => 'View disputes', 'description' => 'See payment disputes and open them, including the evidence. Cannot change them.'],
                'read dispute messages' => ['label' => 'Read dispute conversations', 'description' => 'Read the messages between the two people in a disputed hire. Each time is recorded in the activity log.'],
                'resolve disputes' => ['label' => 'Resolve disputes', 'description' => 'Take a dispute on and settle it with a final amount.'],
            ],
            'Payments' => [
                'view payouts' => ['label' => 'View withdrawals', 'description' => 'See every withdrawal members have asked for and where each one has got to. Phone numbers stay masked.'],
                'approve payouts' => ['label' => 'Approve withdrawals', 'description' => 'Approve a withdrawal (which sends the money to the member\'s M-Pesa) or turn it down with a reason. Sees the full phone number.'],
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
            'Platform manager' => ['description' => 'Oversees members, projects, hires, models and stores: can suspend members and take projects down. Sees contact details.', 'permissions' => [
                'view platform overview', 'view members', 'view contact details', 'manage members', 'view projects', 'moderate projects', 'view engagements',
                'view models', 'view sellers', 'view disputes', 'view audit log',
            ]],
            'Auditor' => ['description' => 'Read-only view of the whole platform and the activity log. No actions, no contact details.', 'permissions' => [
                'view platform overview', 'view members', 'view projects', 'view engagements', 'view models', 'view sellers', 'view disputes', 'view audit log',
            ]],
            'Marketplace moderator' => ['description' => 'Reviews models and seller applications and moderates reviews.', 'permissions' => ['view models', 'review models', 'view sellers', 'review sellers', 'moderate reviews']],
            'Dispute manager' => ['description' => 'Takes on payment disputes, reads the conversation and resolves them.', 'permissions' => ['view disputes', 'read dispute messages', 'resolve disputes', 'view engagements']],
            'Finance' => ['description' => 'Approves members\' withdrawals and sees where the money went. Cannot change members, models or staff.', 'permissions' => ['view payouts', 'approve payouts', 'view members', 'view audit log']],
            'Support' => ['description' => 'Helps members: sees them and their contact details, and can see payment disputes. Read-only.', 'permissions' => ['view members', 'view contact details', 'view disputes']],
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
