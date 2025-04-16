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
        Schema::table('job_engagements', function (Blueprint $table) {
            $table->dropColumn('applicant_accepted_at');
            $table->enum('status', ['employer_accepted', 'active', 'completed', 'cancelled'])
                ->default('employer_accepted')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_engagements', function (Blueprint $table) {
            $table->timestamp('applicant_accepted_at')->nullable();
            $table->enum('status', ['employer_accepted', 'applicant_accepted', 'active', 'completed', 'cancelled'])
                ->default('employer_accepted')
                ->change();
        });
    }
};
