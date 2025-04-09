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
        Schema::create('job_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('model_job_id');
            $table->string('image_path');
            $table->timestamps();

            $table->foreign('model_job_id')->references('id')->on('model_jobs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_images');
    }
};
