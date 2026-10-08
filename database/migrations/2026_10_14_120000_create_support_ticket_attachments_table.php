<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('support_ticket_message_id')->constrained('support_ticket_messages')->cascadeOnDelete();
            $table->string('original_name', 120); // for display only, cleaned; never used to build a path
            $table->string('path', 160); // a random name on the private disk
            $table->string('mime', 32); // decided from the file's content, never from its name or what the browser said
            $table->string('kind', 8); // image | pdf
            $table->unsignedInteger('size'); // bytes, of what is stored
            $table->char('checksum', 64); // sha256 of what is stored
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_attachments');
    }
};
