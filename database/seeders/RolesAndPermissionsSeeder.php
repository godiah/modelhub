<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Engagement permissions
            'view engagements',
            'create engagements',
            'edit engagements',
            'delete engagements',

            // Cancellation permissions
            'process cancellations',
            'view disputes',
            'resolve disputes',

            // Payment permissions
            'process payments',
            'view payment history',

            // User management
            'manage users',
            'view users',

            // Marketplace
            'review sellers',
            'review models',
            'moderate reviews',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Create roles and assign permissions

        // Admin role
        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->givePermissionTo(Permission::all());

        // Client role
        $clientRole = Role::findOrCreate('client', 'web');
        $clientRole->givePermissionTo([
            'view engagements',
            'create engagements',
            'edit engagements',
            'process cancellations',
            'process payments',
            'view payment history',
        ]);

        // Freelancer role
        $freelancerRole = Role::findOrCreate('freelancer', 'web');
        $freelancerRole->givePermissionTo([
            'view engagements',
            'process cancellations',
            'view payment history',
        ]);

        // Support role
        $supportRole = Role::findOrCreate('support', 'web');
        $supportRole->givePermissionTo([
            'view engagements',
            'view disputes',
            'view users',
            'view payment history',
        ]);

        // Dispute manager role
        $disputeManagerRole = Role::findOrCreate('dispute_manager', 'web');
        $disputeManagerRole->givePermissionTo([
            'view engagements',
            'view disputes',
            'resolve disputes',
            'view payment history',
        ]);
    }
}
