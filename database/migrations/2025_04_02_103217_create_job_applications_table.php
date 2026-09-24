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
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('model_jobs')->onDelete('cascade');
            $table->foreignId('applicant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('poster_id')->constrained('users')->onDelete('cascade');
            $table->decimal('offer_amount', 10, 2);
            $table->decimal('service_fee', 10, 2);
            $table->decimal('net_amount', 10, 2);
            $table->text('proposal')->nullable();
            $table->json('portfolio')->nullable();
            $table->boolean('terms_accepted')->default(false);
            $table->enum('status', ['draft', 'submitted', 'accepted', 'rejected', 'withdrawn'])->default('draft');
            $table->timestamps();

            // Ensure a user can only apply once to a job (either draft or submitted)
            $table->unique(['job_id', 'applicant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
