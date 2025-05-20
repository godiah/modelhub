<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_engagements', function (Blueprint $table) {
            DB::statement("ALTER TABLE job_engagements 
            MODIFY status ENUM(
                'employer_accepted',
                'applicant_accepted',
                'active',
                'completed',
                'cancelled',
                'disputed',
                'settled'
            ) NOT NULL DEFAULT 'employer_accepted'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_engagements', function (Blueprint $table) {
            DB::statement("ALTER TABLE job_engagements 
            MODIFY status ENUM(
                'employer_accepted',
                'applicant_accepted',
                'active',
                'completed',
                'cancelled'
            ) NOT NULL DEFAULT 'employer_accepted'");
        });
    }
};
