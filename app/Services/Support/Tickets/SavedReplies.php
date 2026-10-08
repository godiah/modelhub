<?php

namespace App\Services\Support\Tickets;

use App\Models\Staff;
use App\Models\SupportTicket;
use App\Support\Money;
use App\Support\Settings\FeePolicy;
use Illuminate\Support\Str;

/**
 * Fills a saved reply's placeholders from the ticket it is inserted into, from live settings (the minimum withdrawal) and from the service levels, so a
 * reply can never go stale or disagree with what members are told elsewhere. A placeholder it does not know is left as it was written, so a person
 * sees it and fixes it before sending; the editor refuses to save one.
 */
final class SavedReplies
{
    /** @return array<string, array{label: string, sample: string}> */
    public static function variables(): array
    {
        return [
            'member_name' => ['label' => 'Their first name', 'sample' => 'Achieng'],
            'reference' => ['label' => 'The request reference', 'sample' => 'SUP-1042'],
            'staff_name' => ['label' => 'Your first name', 'sample' => 'Grace'],
            'first_reply_time' => ['label' => 'From the service levels', 'sample' => '4 business hours'],
            'min_withdrawal' => ['label' => 'From the live fee settings', 'sample' => 'Ksh500'],
        ];
    }

    /** @return list<string> the placeholders in `$body` that are not in the list above */
    public static function unknown(string $body): array
    {
        preg_match_all('/\{(\w+)\}/', $body, $found);

        return array_values(array_diff(array_unique($found[1]), array_keys(self::variables())));
    }

    public function render(string $body, SupportTicket $ticket, Staff $staff): string
    {
        $values = [
            'member_name' => Str::before((string) ($ticket->requester?->name ?? ''), ' ') ?: __('there'),
            'reference' => $ticket->reference,
            'staff_name' => Str::before($staff->name, ' '),
            'first_reply_time' => TicketTargets::firstReplyAmount($ticket->severity),
            'min_withdrawal' => Money::formatMinor(FeePolicy::minPayoutMinor(), 0),
        ];

        return (string) preg_replace_callback('/\{(\w+)\}/', fn (array $m) => $values[$m[1]] ?? $m[0], $body);
    }
}
