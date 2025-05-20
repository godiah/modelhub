<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

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
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Admin role
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Client role
        $clientRole = Role::create(['name' => 'client']);
        $clientRole->givePermissionTo([
            'view engagements',
            'create engagements',
            'edit engagements',
            'process cancellations',
            'process payments',
            'view payment history',
        ]);

        // Freelancer role
        $freelancerRole = Role::create(['name' => 'freelancer']);
        $freelancerRole->givePermissionTo([
            'view engagements',
            'process cancellations',
            'view payment history',
        ]);

        // Support role
        $supportRole = Role::create(['name' => 'support']);
        $supportRole->givePermissionTo([
            'view engagements',
            'view disputes',
            'view users',
            'view payment history',
        ]);

        // Dispute manager role
        $disputeManagerRole = Role::create(['name' => 'dispute_manager']);
        $disputeManagerRole->givePermissionTo([
            'view engagements',
            'view disputes',
            'resolve disputes',
            'view payment history',
        ]);
    }
}
