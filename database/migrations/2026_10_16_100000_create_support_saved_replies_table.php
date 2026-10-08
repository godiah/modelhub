<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_saved_replies', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('topic', 40);
            $table->text('body');
            // null = shared with everyone who answers tickets; otherwise only this staff member sees and uses it
            $table->foreignId('owner_id')->nullable()->constrained('staff')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->unsignedInteger('uses')->default(0);
            $table->timestamps();

            $table->index(['owner_id', 'topic']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_saved_replies');
    }
};
