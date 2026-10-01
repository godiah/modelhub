<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->string('slug', 80)->nullable()->unique()->after('display_name');
        });

        // Give every existing seller a storefront address
        $taken = [];
        foreach (DB::table('seller_profiles')->orderBy('id')->get(['id', 'display_name']) as $row) {
            $base = Str::slug($row->display_name) ?: 'seller';
            $slug = $base;
            for ($i = 2; in_array($slug, $taken, true); $i++) {
                $slug = $base.'-'.$i;
            }
            $taken[] = $slug;
            DB::table('seller_profiles')->where('id', $row->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
