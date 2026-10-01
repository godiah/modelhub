<?php

namespace Database\Seeders;

use App\Support\Staff\StaffAccess;
use Illuminate\Database\Seeder;

/** The staff permissions and the default staff roles (see App\Support\Staff\StaffAccess). Safe to run again. */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        StaffAccess::sync();
    }
}
