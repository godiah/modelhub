<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_read_audits', function (Blueprint $table) {
            $table->id();
            $table->string('request_id', 64)->nullable()->index();
            $table->string('principal', 64); // the id of the key that signed the call: who the caller is
            $table->foreignId('user_id')->nullable()->index(); // the member the claim named; no foreign key, the audit outlives the member
            $table->string('endpoint', 64)->nullable(); // the route name, never the path or query (they can hold references)
            $table->unsignedSmallInteger('status');
            $table->string('outcome', 16);
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_read_audits');
    }
};
