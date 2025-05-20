<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update enum column cancellation_type to add 'admin_terminated'
        DB::statement("ALTER TABLE job_cancellations 
            MODIFY cancellation_type ENUM(
                'mutual', 
                'client_initiated', 
                'freelancer_initiated', 
                'dispute',
                'admin_terminated'
            ) NOT NULL");

        // Add new fields after `is_dispute`
        Schema::table('job_cancellations', function (Blueprint $table) {
            $table->enum('dispute_status', ['pending', 'under_review', 'resolved'])->nullable()->after('is_dispute');
            $table->boolean('freelancer_accepted_payment')->default(false)->after('dispute_status');
            $table->timestamp('freelancer_accepted_at')->nullable()->after('freelancer_accepted_payment');
        });
    }

    public function down(): void
    {
        // Revert cancellation_type enum (remove admin_terminated)
        DB::statement("ALTER TABLE job_cancellations 
            MODIFY cancellation_type ENUM(
                'mutual', 
                'client_initiated', 
                'freelancer_initiated', 
                'dispute'
            ) NOT NULL");

        // Drop the added columns
        Schema::table('job_cancellations', function (Blueprint $table) {
            $table->dropColumn('dispute_status');
            $table->dropColumn('freelancer_accepted_payment');
            $table->dropColumn('freelancer_accepted_at');
        });
    }
};
