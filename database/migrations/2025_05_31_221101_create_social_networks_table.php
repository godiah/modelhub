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
        Schema::create('social_networks', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'Facebook', 'Twitter/X', 'LinkedIn'
            $table->string('slug')->unique(); // e.g., 'facebook', 'twitter', 'linkedin'
            $table->string('icon')->nullable(); // CSS class or icon name
            $table->string('color')->nullable(); // Brand color for UI
            $table->string('base_url')->nullable(); // e.g., 'https://facebook.com/'
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_networks');
    }
};
