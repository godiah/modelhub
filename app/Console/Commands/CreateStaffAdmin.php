<?php

namespace App\Console\Commands;

use App\Models\Staff;
use App\Support\Auth\PasswordPolicy;
use App\Support\Staff\StaffAccess;
use App\Support\Staff\StaffAudit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * Creates the first Super admin on a fresh install (or another one later). There is no built-in account and no
 * default password: you choose both here, on the server.
 */
class CreateStaffAdmin extends Command
{
    protected $signature = 'staff:create-admin {email : The new Super admin\'s email address} {--name= : Their full name}';

    protected $description = 'Create a Super admin staff account for the staff portal';

    public function handle(): int
    {
        StaffAccess::sync();

        $email = strtolower(trim($this->argument('email')));
        $name = $this->option('name') ?: $this->ask('Full name');

        $password = $this->secret('Password (at least 12 characters, with letters and numbers)');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            ['name' => ['required', 'string', 'min:2', 'max:100'], 'email' => ['required', 'email', 'unique:staff,email'], 'password' => ['required', PasswordPolicy::rule(staff: true)]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if ($password !== $this->secret('Confirm the password')) {
            $this->error('The passwords did not match.');

            return self::FAILURE;
        }

        $staff = Staff::create(['name' => $name, 'email' => $email, 'password' => $password]);
        $staff->assignRole(StaffAccess::SUPER_ADMIN);
        StaffAudit::log('staff.created-from-console', "Created Super admin {$staff->name} ({$staff->email}) from the console", $staff, staffId: $staff->id);

        $this->info('Super admin created. They can sign in at '.url('/admin/login'));

        return self::SUCCESS;
    }
}
