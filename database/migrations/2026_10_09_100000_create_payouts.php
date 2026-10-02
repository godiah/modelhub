<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A member taking their earnings out to M-Pesa. It asks, staff approve, the money is sent, and it ends paid, failed or turned down.
        // The amount leaves their balance when they ask and goes back if it does not end paid.
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 12)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount_minor'); // taken from their balance
            $table->unsignedBigInteger('fee_minor'); // kept by the platform
            $table->unsignedBigInteger('net_minor'); // what is sent to their phone
            $table->char('currency', 3);
            $table->string('msisdn', 15);
            $table->string('status', 12)->default('requested'); // requested, processing, paid, failed, rejected, cancelled
            $table->string('gateway', 20)->nullable();
            $table->string('gateway_reference')->nullable()->unique();
            $table->string('receipt', 40)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('failure_reason')->nullable(); // why it failed, or why staff turned it down
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
