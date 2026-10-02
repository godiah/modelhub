<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // When what is left in a job's escrow becomes the client's to have back: set when the engagement is cancelled (at once if the freelancer
        // walked away, after the review window otherwise), and when it is settled. Null while a partial payment or dispute is still open.
        Schema::table('job_engagements', function (Blueprint $table) {
            $table->timestamp('escrow_refund_due_at')->nullable()->after('refunded_minor')->index();
        });

        // Money from a job's escrow handed back to the client by staff: the books are posted when it is recorded, and staff send it by M-Pesa themselves.
        Schema::create('escrow_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('engagement_id')->constrained('job_engagements')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // the client who gets it
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('msisdn', 15)->nullable(); // where to send it: the number the client paid from
            $table->string('funding_receipt', 40)->nullable(); // the M-Pesa receipt of the payment it came from
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escrow_refunds');
        Schema::table('job_engagements', function (Blueprint $table) {
            $table->dropColumn('escrow_refund_due_at');
        });
    }
};
