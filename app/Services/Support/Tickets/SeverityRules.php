<?php

namespace App\Services\Support\Tickets;

use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketSeverity;
use App\Models\IssuedLicence;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\SupportTicket;
use App\Models\User;

/**
 * How urgent a ticket is, worked out from the member's LIVE records and a few fixed rules. The assistant does not set it and the member cannot: a
 * payment that is really under review is high however the request is worded, and a calm "just checking" about a fine payment is not.
 * Staff can change it afterwards (TicketService::setSeverity).
 */
final class SeverityRules
{
    /** @param  list<array{type: string, reference: string, model: object}>  $refs  from EntityRefs::validated */
    public function evaluate(User $member, SupportTicketCategory $category, array $refs, string $text): SupportTicketSeverity
    {
        $severity = $category->baseSeverity();

        foreach ($refs as $ref) {
            $severity = $severity->atLeast($this->forRecord($ref['model']));
        }

        if ($this->hasUrgentWords($text)) {
            $severity = $severity->atLeast(SupportTicketSeverity::High);
        }

        // Writing in again about the same thing within a week is itself a signal
        foreach (EntityRefs::keys($refs) as $key) {
            $earlier = SupportTicket::ownedBy($member)->where('created_at', '>=', now()->subDays(7))->whereJsonContains('entity_refs', $key)->exists();

            if ($earlier) {
                $severity = $severity->raised();
                break;
            }
        }

        return $severity;
    }

    private function forRecord(object $record): SupportTicketSeverity
    {
        if ($record instanceof Payment) {
            $paidNoLicence = $record->status === PaymentStatus::Succeeded && ! $record->isEscrow() && ! IssuedLicence::where('purchase_id', $record->purchase_id)->exists();

            return $record->status === PaymentStatus::Review || $paidNoLicence ? SupportTicketSeverity::High : SupportTicketSeverity::Normal;
        }

        if ($record instanceof Payout) {
            $stale = $record->status === PayoutStatus::Processing && $record->approved_at?->lt(now()->subHours((int) config('payments.payout_stale_hours'))) === true;

            return $stale ? SupportTicketSeverity::High : SupportTicketSeverity::Normal;
        }

        return SupportTicketSeverity::Normal;
    }

    private function hasUrgentWords(string $text): bool
    {
        $lower = mb_strtolower($text);

        foreach ((array) config('support.tickets.urgent_words') as $word) {
            if (preg_match('/\b'.preg_quote((string) $word, '/').'\b/u', $lower)) {
                return true;
            }
        }

        return false;
    }
}
