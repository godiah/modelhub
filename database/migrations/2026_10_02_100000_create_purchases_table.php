<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A member's purchase of a model. Checkout does not exist yet, so nothing creates these in the real app: they are
 * the record the money core will fill (order, payment, ledger references get added then), and what reviews rely on
 * to know who really bought a model. One purchase per member per model.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('price_minor')->default(0);
            $table->char('currency', 3)->default('KES');
            $table->string('status', 20)->default('completed')->index();
            $table->timestamp('purchased_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
