<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->decimal('rating_avg', 3, 2)->nullable()->after('focus');
            $table->unsignedInteger('rating_count')->default(0)->after('rating_avg');
        });

        // Backfill: visible reviews on the seller's published, not-deleted models
        $reviews = "from product_reviews r join products p on p.id = r.product_id
            where p.user_id = seller_profiles.user_id and p.status = 'published' and p.deleted_at is null and r.status = 'visible'";

        DB::statement("update seller_profiles set rating_count = (select count(*) {$reviews}), rating_avg = (select round(avg(r.rating), 2) {$reviews})");
    }

    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->dropColumn(['rating_avg', 'rating_count']);
        });
    }
};
