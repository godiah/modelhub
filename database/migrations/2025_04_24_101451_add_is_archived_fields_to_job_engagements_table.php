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
            $table->boolean('is_archived_by_applicant')->default(false)->after('payment_released_at');
            $table->boolean('is_archived_by_poster')->default(false)->after('is_archived_by_applicant');
            $table->dropColumn('is_archived');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_engagements', function (Blueprint $table) {
            $table->dropColumn('is_archived_by_applicant');
            $table->dropColumn('is_archived_by_poster');
            $table->boolean('is_archived')->default(false)->after('payment_released_at');
        });
    }
};
