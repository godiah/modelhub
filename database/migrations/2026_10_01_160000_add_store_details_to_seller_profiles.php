<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->string('tagline', 80)->nullable()->after('slug');
            $table->string('logo_path')->nullable()->after('tagline');
            $table->string('website_url')->nullable()->after('portfolio_url');
            $table->timestamp('name_changed_at')->nullable()->after('website_url');
        });
    }

    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->dropColumn(['tagline', 'logo_path', 'website_url', 'name_changed_at']);
        });
    }
};
