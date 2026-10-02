<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A buyer paying for a model: from the moment the M-Pesa prompt is sent until it is paid, failed or given up on. What the sale
        // will be split into (commission and the seller's share) and how long the earnings are held are fixed here, when it starts.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 12)->unique(); // ours; this is what M-Pesa shows as the account reference
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete(); // who is paid: copied from the listing, so it survives the listing
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tier', 20);
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('msisdn', 15);
            $table->string('status', 12)->default('pending');
            $table->string('gateway', 20);
            $table->string('gateway_reference')->nullable()->unique(); // the gateway's id for it (M-Pesa's CheckoutRequestID)
            $table->string('receipt', 40)->nullable(); // the M-Pesa receipt number
            $table->decimal('commission_rate', 5, 4);
            $table->unsignedBigInteger('commission_minor');
            $table->unsignedBigInteger('seller_share_minor');
            $table->unsignedSmallInteger('hold_days');
            $table->string('failure_reason')->nullable();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('release_at')->nullable(); // when the seller's share leaves the hold
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
            $table->index(['user_id', 'product_id', 'tier']);
            $table->index(['status', 'release_at']);
        });

        // Which files a buyer took, and when: a download is what ends the right to a refund, so it is kept
        Schema::create('licence_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issued_licence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_file_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_name');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licence_downloads');
        Schema::dropIfExists('payments');
    }
};
