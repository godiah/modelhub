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
        // Create user_skills pivot table
        Schema::create('user_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('skill_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'skill_id']);
        });

        // Create user_software pivot table
        Schema::create('user_software', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('software_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'software_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_software');
        Schema::dropIfExists('user_skills');
    }
};
