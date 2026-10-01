<?php

use App\Support\Avatars;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Members and stores now pick an avatar from a catalogue instead of uploading a picture.
 *
 * Every existing member and store gets a random avatar (they can change it), then the uploaded profile photos and
 * store logos are deleted from disk and their columns dropped. The uploads cannot be restored by rolling back.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar', 80)->nullable()->after('email');
        });

        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->string('avatar', 80)->nullable()->after('tagline');
        });

        DB::table('users')->whereNull('avatar')->orderBy('id')->each(
            fn ($user) => DB::table('users')->where('id', $user->id)->update(['avatar' => Avatars::random(Avatars::PEOPLE)])
        );

        DB::table('seller_profiles')->whereNull('avatar')->orderBy('id')->each(
            fn ($store) => DB::table('seller_profiles')->where('id', $store->id)->update(['avatar' => Avatars::random(Avatars::STORES)])
        );

        if (Schema::hasColumn('user_profiles', 'avatar')) {
            DB::table('user_profiles')->whereNotNull('avatar')->pluck('avatar')->each(fn ($path) => Storage::disk('public')->delete($path));

            Schema::table('user_profiles', fn (Blueprint $table) => $table->dropColumn('avatar'));
        }

        if (Schema::hasColumn('seller_profiles', 'logo_path')) {
            DB::table('seller_profiles')->whereNotNull('logo_path')->pluck('logo_path')
                ->each(fn ($path) => Storage::disk(config('marketplace.images_disk'))->delete($path));

            Schema::table('seller_profiles', fn (Blueprint $table) => $table->dropColumn('logo_path'));
        }
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('user_id');
        });

        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('tagline');
            $table->dropColumn('avatar');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar');
        });
    }
};
