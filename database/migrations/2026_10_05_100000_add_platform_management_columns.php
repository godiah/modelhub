<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What staff need to manage the platform: suspending a member (and when they last signed in), private staff notes on a
 * member, and taking a project down. Every action points at the staff member who did it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('email_verified_at')->index();
            $table->string('suspended_reason', 500)->nullable()->after('suspended_at');
            $table->foreignId('suspended_by')->nullable()->after('suspended_reason')->constrained('staff')->nullOnDelete();
            $table->timestamp('last_login_at')->nullable()->after('suspended_by');
        });

        Schema::create('member_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::table('model_jobs', function (Blueprint $table) {
            $table->timestamp('taken_down_at')->nullable()->after('is_archived')->index();
            $table->string('taken_down_reason', 500)->nullable()->after('taken_down_at');
            $table->foreignId('taken_down_by')->nullable()->after('taken_down_reason')->constrained('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('model_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('taken_down_by');
            $table->dropColumn(['taken_down_at', 'taken_down_reason']);
        });

        Schema::dropIfExists('member_notes');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('suspended_by');
            $table->dropColumn(['suspended_at', 'suspended_reason', 'last_login_at']);
        });
    }
};
