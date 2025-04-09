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
        Schema::table('model_jobs', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('deadline');
            $table->index('no_deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_jobs', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['deadline']);
            $table->dropIndex(['no_deadline']);
        });
    }
};
