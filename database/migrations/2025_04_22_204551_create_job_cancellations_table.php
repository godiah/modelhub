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
        Schema::create('job_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('engagement_id')->constrained('job_engagements')->onDelete('cascade');
            $table->foreignId('initiator_id')->constrained('users');
            $table->enum('cancellation_type', ['mutual', 'client_initiated', 'freelancer_initiated', 'dispute']);
            $table->string('reason_category');
            $table->text('reason_details');
            $table->boolean('process_payment_for_work')->default(false);
            $table->decimal('partial_payment_amount', 10, 2)->nullable();
            $table->timestamp('payment_calculated_at')->nullable();
            $table->boolean('partial_payment_processed')->default(false);
            $table->timestamp('partial_payment_processed_at')->nullable();
            $table->boolean('is_dispute')->default(false);
            $table->boolean('dispute_resolved')->default(false);
            $table->timestamp('dispute_resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_cancellations');
    }
};
