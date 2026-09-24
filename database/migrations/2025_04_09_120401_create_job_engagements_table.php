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
        Schema::create('job_engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('job_applications')->onDelete('cascade');
            $table->enum('status', ['employer_accepted', 'applicant_accepted', 'active', 'completed', 'cancelled'])->default('employer_accepted');
            $table->timestamp('employer_accepted_at')->nullable();
            $table->timestamp('applicant_accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('agreed_amount', 10, 2);
            $table->decimal('service_fee', 10, 2);
            $table->decimal('net_amount', 10, 2);
            $table->timestamp('payment_escrowed_at')->nullable();
            $table->timestamp('payment_released_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_engagements');
    }
};
