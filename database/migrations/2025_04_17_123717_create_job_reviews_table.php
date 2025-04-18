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
        Schema::create('job_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('engagement_id')->constrained('job_engagements')->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users');
            $table->foreignId('reviewee_id')->constrained('users');
            $table->integer('rating')->comment('Rating from 1-5');
            $table->text('review');
            $table->json('tags')->nullable()->comment('Skills or qualities being reviewed');
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_reviews');
    }
};
