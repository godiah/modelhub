<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('slug', 100)->unique();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('title', 150);
            $table->string('slug', 190)->unique();
            $table->text('description')->nullable();
            $table->json('tags')->nullable();
            $table->string('status', 20)->default('draft')->index();

            // Money: minor units + currency (0 = free)
            $table->unsignedBigInteger('price_minor')->default(0);
            $table->char('currency', 3)->default('KES');
            $table->string('license', 30)->default('standard');

            // CGTrader-style technical details
            $table->string('geometry_type', 20)->nullable();
            $table->unsignedInteger('polygons')->nullable();
            $table->unsignedInteger('vertices')->nullable();
            $table->string('uv_layout', 20)->nullable();
            $table->string('render_engine', 80)->nullable();
            $table->boolean('is_rigged')->default(false);
            $table->boolean('is_animated')->default(false);
            $table->boolean('is_low_poly')->default(false);
            $table->boolean('is_pbr')->default(false);
            $table->boolean('has_textures')->default(false);
            $table->boolean('has_materials')->default(false);
            $table->boolean('is_uv_mapped')->default(false);
            $table->boolean('is_print_ready')->default(false);
            $table->boolean('is_vr_ready')->default(false);

            // Review
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index('category_id');
        });

        Schema::create('product_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 30);
            $table->string('path');
            $table->string('original_name');
            $table->string('extension', 12);
            $table->string('kind', 20);
            $table->unsignedBigInteger('size_bytes');
            $table->char('checksum', 64)->nullable();
            $table->timestamps();

            $table->index(['product_id', 'kind']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 30);
            $table->string('path');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'position']);
        });

        Schema::create('product_software', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('software_id')->constrained('software')->cascadeOnDelete();
            $table->string('version', 40)->nullable();

            $table->unique(['product_id', 'software_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_software');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_files');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
