<?php

use Database\Seeders\CategorySeeder;
use Illuminate\Database\Migrations\Migration;

/** Categories are reference data the marketplace cannot work without, so they ship with the schema. */
return new class extends Migration
{
    public function up(): void
    {
        (new CategorySeeder)->run();
    }

    public function down(): void
    {
        // Reference data: left in place (products may point at it).
    }
};
