<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Where money can sit: the platform's own accounts, and a pair of accounts per seller
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique(); // e.g. platform.gateway, user.42.available
            $table->string('name');
            $table->string('kind', 12); // asset, liability or income: decides which side a balance grows on
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->char('currency', 3);
            $table->boolean('allow_negative')->default(false); // only the platform's own accounts may go below zero
            $table->timestamps();
        });

        // One business event: always balanced, and never posted twice for the same idempotency key
        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->string('idempotency_key', 120)->unique();
            $table->nullableMorphs('reference');
            $table->string('description');
            $table->json('meta')->nullable();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();
        });

        // The lines of a transaction. Append-only: a mistake is fixed with a new, opposite transaction, never an edit.
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('ledger_transactions')->restrictOnDelete();
            $table->foreignId('account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->string('direction', 6); // debit or credit
            $table->unsignedBigInteger('amount_minor'); // always positive; the direction carries the sign
            $table->char('currency', 3);
            $table->timestamp('created_at')->nullable();

            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_transactions');
        Schema::dropIfExists('ledger_accounts');
    }
};
