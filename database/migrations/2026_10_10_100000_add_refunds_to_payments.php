<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // What actually arrived, which for a payment sent to review can differ from what was asked
            $table->unsignedBigInteger('received_minor')->nullable()->after('amount_minor');
            $table->timestamp('refunded_at')->nullable()->after('released_at');
            $table->foreignId('refunded_by')->nullable()->after('refunded_at')->constrained('staff')->nullOnDelete();
            $table->string('refund_reason')->nullable()->after('refunded_by');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('refunded_by');
            $table->dropColumn(['received_minor', 'refunded_at', 'refund_reason']);
        });
    }
};
