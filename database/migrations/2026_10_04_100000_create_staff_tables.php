<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staff have their own accounts, apart from members: a `staff` table (own sign-in, own password resets), and an
 * activity log of what staff do. Roles and permissions attach to staff only (spatie guard "staff").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('avatar', 80)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Separate from the members' token table: a person can hold both a member and a staff account on one email.
        Schema::create('staff_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Roles are edited in the staff portal, so they get a description to say what each is for
        Schema::table(config('permission.table_names.roles', 'roles'), function (Blueprint $table) {
            $table->string('description', 255)->nullable()->after('guard_name');
        });

        Schema::create('staff_activity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('action', 60)->index();
            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('summary', 255);
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_activity');
        Schema::table(config('permission.table_names.roles', 'roles'), fn (Blueprint $table) => $table->dropColumn('description'));
        Schema::dropIfExists('staff_password_reset_tokens');
        Schema::dropIfExists('staff');
    }
};
