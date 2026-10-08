<?php

namespace App\Console\Commands;

use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use Illuminate\Console\Command;

/**
 * The assistant's evidence snapshot on a ticket holds facts about a member's money and quotes from their chat, so it does not outlive the ticket for
 * long: some days after a ticket is resolved (config support.tickets.evidence_retention_days) the snapshot is dropped. The thread, the member's own
 * words in it and the staff's replies stay.
 */
class PurgeTicketEvidence extends Command
{
    protected $signature = 'support:purge-ticket-evidence';

    protected $description = 'Drop the assistant\'s evidence snapshot from tickets resolved long enough ago';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) config('support.tickets.evidence_retention_days'));

        $purged = SupportTicket::whereNotNull('evidence')
            ->whereIn('status', [SupportTicketStatus::Resolved->value, SupportTicketStatus::Closed->value])
            ->where('resolved_at', '<', $cutoff)
            ->update(['evidence' => null]);

        $this->info("Dropped the evidence from {$purged} resolved tickets older than ".config('support.tickets.evidence_retention_days').' days.');

        return self::SUCCESS;
    }
}
