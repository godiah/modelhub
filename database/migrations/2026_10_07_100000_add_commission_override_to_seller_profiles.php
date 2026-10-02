<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A special commission rate for one seller (a percentage); null means the platform's model-sales rate applies
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->decimal('commission_percent', 5, 2)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->dropColumn('commission_percent');
        });
    }
};
