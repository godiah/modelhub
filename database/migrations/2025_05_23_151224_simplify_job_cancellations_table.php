<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_cancellations', function (Blueprint $table) {
            // Drop dispute-specific columns
            $table->dropColumn([
                'dispute_resolved',
                'dispute_resolved_at',
                'resolution_notes',
                'dispute_status',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_cancellations', function (Blueprint $table) {
            // Recreate dispute-specific columns
            $table->boolean('dispute_resolved')->default(false);
            $table->timestamp('dispute_resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->enum('dispute_status', ['pending', 'under_review', 'resolved'])->nullable();
        });
    }
};
