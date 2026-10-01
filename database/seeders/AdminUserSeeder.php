<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Support\Staff\StaffAccess;
use Illuminate\Database\Seeder;

/**
 * A Super admin for LOCAL development only (admin@modelhub.com, password admin1234). It never runs in production:
 * there, create the first one with `php artisan staff:create-admin`.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            $this->command?->warn('Skipping the demo staff account: use php artisan staff:create-admin here.');

            return;
        }

        StaffAccess::sync();

        $staff = Staff::firstOrCreate(['email' => 'admin@modelhub.com'], ['name' => 'Super Admin', 'password' => 'admin1234']);
        $staff->assignRole(StaffAccess::SUPER_ADMIN);
    }
}
