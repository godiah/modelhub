<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 16)->nullable()->unique(); // SUP-1042, set as soon as the row has an id
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete(); // null only for the contact form (later): unverified
            $table->string('category', 32);
            $table->string('severity', 16);
            $table->string('status', 16);
            $table->string('source', 16)->default('bot');
            $table->string('conversation_id', 36)->nullable()->index(); // the assistant's chat: the transcript is fetched from it, never copied here
            $table->text('summary');
            $table->json('entity_refs')->nullable(); // validated against the member: ["payment:MH...", "withdrawal:PO..."]
            $table->json('evidence')->nullable(); // a snapshot built by code when the ticket was filed; untrusted text inside is marked as such
            $table->foreignId('assignee_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('first_response_due_at')->nullable();
            $table->timestamp('resolution_due_at')->nullable();
            $table->timestamp('first_responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('resolution_tag', 32)->nullable();
            $table->timestamps();

            $table->index(['status', 'severity']);
            $table->index(['requester_id', 'status']);
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->string('sender', 8); // member | staff | system | note (a note is internal and is never shown to the member)
            $table->foreignId('member_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['support_ticket_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
