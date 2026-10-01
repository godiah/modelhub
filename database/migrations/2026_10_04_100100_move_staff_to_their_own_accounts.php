<?php

use App\Support\Staff\StaffAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Staff move out of the members' table.
 *
 * - The staff roles and permissions are created (guard "staff").
 * - Every member who held a staff role gets a staff account with the SAME id, so each "reviewed by", "hidden by",
 *   "assigned to" and "resolved by" already in the data keeps pointing at the right person. Their password and
 *   avatar are copied; their member account stays as it was, now without any role.
 * - Those columns now reference `staff` instead of `users`; a reference that points at someone who was not staff is cleared.
 * - The old member-side roles and permissions (admin, support, dispute_manager, client, freelancer and seven permissions
 *   that nothing ever checked) are deleted.
 *
 * This cannot be rolled back.
 */
return new class extends Migration
{
    /** table => columns that record which staff member acted */
    private const STAFF_COLUMNS = [
        'job_payment_disputes' => ['admin_assigned', 'resolved_by'],
        'job_cancellations' => ['resolved_by'],
        'job_partial_payments' => ['finalized_by'],
        'seller_profiles' => ['reviewed_by'],
        'products' => ['reviewed_by'],
        'product_reviews' => ['hidden_by'],
        'review_reports' => ['resolved_by'],
    ];

    /** the old role name => the staff role it becomes */
    private const ROLE_MAP = ['admin' => StaffAccess::SUPER_ADMIN, 'support' => 'Support', 'dispute_manager' => 'Dispute manager'];

    public function up(): void
    {
        StaffAccess::sync();

        $tables = config('permission.table_names');
        $roleIds = DB::table($tables['roles'])->where('guard_name', 'web')->pluck('id', 'name');

        // 1. Members who held a staff role become staff, keeping their id
        foreach (self::ROLE_MAP as $old => $new) {
            if (! isset($roleIds[$old])) {
                continue;
            }

            $newRoleId = DB::table($tables['roles'])->where('name', $new)->where('guard_name', 'staff')->value('id');

            DB::table($tables['model_has_roles'])->where('role_id', $roleIds[$old])->where('model_type', 'App\Models\User')->pluck('model_id')->each(
                function ($userId) use ($newRoleId, $tables) {
                    $user = DB::table('users')->find($userId);

                    if (! $user) {
                        return;
                    }

                    DB::table('staff')->insertOrIgnore([
                        'id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'password' => $user->password,
                        'avatar' => $user->avatar ?? null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                    ]);

                    DB::table($tables['model_has_roles'])->insertOrIgnore(['role_id' => $newRoleId, 'model_type' => 'App\Models\Staff', 'model_id' => $user->id]);
                }
            );
        }

        // 2. The columns that say which staff member acted now point at staff
        foreach (self::STAFF_COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign([$column]));
                DB::table($table)->whereNotNull($column)->whereNotIn($column, DB::table('staff')->select('id'))->update([$column => null]);
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->foreign($column)->references('id')->on('staff')->nullOnDelete());
            }
        }

        // A payment made when a dispute is resolved was not "processed" by a member, so that column may now be empty
        Schema::table('job_partial_payments', fn (Blueprint $table) => $table->unsignedBigInteger('processed_by')->nullable()->change());

        // 3. The old member-side roles and permissions go
        DB::table($tables['model_has_roles'])->where('model_type', 'App\Models\User')->delete();
        DB::table($tables['model_has_permissions'])->where('model_type', 'App\Models\User')->delete();
        DB::table($tables['roles'])->where('guard_name', 'web')->delete();
        DB::table($tables['permissions'])->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        throw new RuntimeException('Moving staff to their own accounts cannot be rolled back.');
    }
};
