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
        Schema::create('user_social_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('social_network_id')->constrained()->onDelete('cascade');
            $table->string('username')->nullable(); // e.g., 'john_doe'
            $table->string('url'); // Full URL to the profile
            $table->string('display_name')->nullable(); // Custom display name
            $table->boolean('is_public')->default(true); // Whether to show publicly
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Allow multiple accounts per platform per user
            $table->index(['user_id', 'social_network_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_social_links');
    }
};
