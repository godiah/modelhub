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
        Schema::create('job_payment_disputes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cancellation_id')->constrained('job_cancellations')->onDelete('cascade');
            $table->foreignId('disputed_by')->constrained('users');

            $table->string('dispute_reason');
            $table->text('dispute_details');
            $table->json('supporting_evidence')->nullable();

            $table->enum('status', ['pending', 'under_review', 'resolved'])->default('pending');

            $table->foreignId('admin_assigned')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->text('resolution_notes')->nullable();
            $table->decimal('resolution_amount', 10, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_payment_disputes');
    }
};
