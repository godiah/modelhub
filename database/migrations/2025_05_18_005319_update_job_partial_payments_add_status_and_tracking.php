<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_partial_payments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'accepted', 'disputed', 'finalized'])->default('pending')->after('processed_at');
            $table->timestamp('accepted_at')->nullable()->after('status');
            $table->foreignId('dispute_id')->nullable()->constrained('job_payment_disputes')->after('accepted_at');
            $table->decimal('final_amount', 10, 2)->nullable()->after('dispute_id');
            $table->timestamp('finalized_at')->nullable()->after('final_amount');
            $table->foreignId('finalized_by')->nullable()->constrained('users')->after('finalized_at');
        });
    }

    public function down(): void
    {
        Schema::table('job_partial_payments', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'accepted_at',
                'dispute_id',
                'final_amount',
                'finalized_at',
                'finalized_by',
            ]);
        });
    }
};
