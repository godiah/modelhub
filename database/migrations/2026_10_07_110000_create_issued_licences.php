<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A listing sells a Standard licence at its price, and optionally an Extended one at a higher price (null = not offered)
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('extended_price_minor')->nullable()->after('price_minor');
            $table->dropColumn('license'); // the unused placeholder, replaced by issued licences
        });

        // A buyer can hold Standard and later upgrade to Extended, or buy again after a refund, so a purchase is no longer unique per
        // buyer and model. The licence service refuses a second active licence of the same tier instead.
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('tier', 20)->default('standard')->after('product_id');
            $table->index(['user_id', 'product_id']);
            $table->dropUnique(['user_id', 'product_id']);
        });

        // The legal record of what a buyer may do with a model. Everything that matters is copied onto it when it is issued, so it
        // still reads the same if the listing, the store name or the price changes later, or the listing is deleted.
        Schema::create('issued_licences', function (Blueprint $table) {
            $table->id();
            $table->string('key', 20)->unique();
            $table->foreignId('purchase_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tier', 20);
            $table->string('terms_version', 20);
            $table->json('terms'); // the wording itself, as it read when the licence was issued
            $table->string('licensee_name');
            $table->string('product_title');
            $table->string('seller_name');
            $table->unsignedBigInteger('price_minor');
            $table->char('currency', 3);
            $table->timestamp('issued_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'product_id', 'tier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issued_licences');

        Schema::table('purchases', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id']);
            $table->dropIndex(['user_id', 'product_id']);
            $table->dropColumn('tier');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('license', 30)->default('standard');
            $table->dropColumn('extended_price_minor');
        });
    }
};
