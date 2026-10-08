<?php

namespace App\Services\Support\Tickets;

use App\Enums\SupportResolutionTag;
use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketSeverity;
use App\Enums\SupportTicketSource;
use App\Enums\SupportTicketStatus;
use App\Models\Staff;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Notifications\SupportTicketMemberRepliedNotification;
use App\Notifications\SupportTicketOpenedNotification;
use App\Notifications\SupportTicketReceivedNotification;
use App\Notifications\SupportTicketRepliedNotification;
use App\Notifications\SupportTicketResolvedNotification;
use App\Support\Staff\StaffAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

/**
 * Everything that happens to a support ticket, in one place, so its status, its clocks and the staff audit trail cannot get out of step. Each method
 * returns the thing it made, or (for what a member can do) the reason they cannot, in their words.
 *
 * Whatever a member or the assistant wrote is plain text and untrusted: it is stored as given (trimmed) and escaped wherever it is drawn.
 */
class TicketService
{
    public const SUMMARY_MIN = 5;

    public const BODY_MAX = 4000;

    private readonly BusinessHours $hours;

    private readonly AttachmentStore $attachments;

    public function __construct(private readonly SeverityRules $severity, ?BusinessHours $hours = null, ?AttachmentStore $attachments = null)
    {
        $this->hours = $hours ?? BusinessHours::fromConfig();
        $this->attachments = $attachments ?? new AttachmentStore;
    }

    /**
     * A member hands something to staff. Severity and the clocks are worked out here from live records, never taken from the caller.
     *
     * @param  list<array{type?: mixed, reference?: mixed}>  $claimedRefs  checked against the member; any that is not theirs is dropped
     * @param  array<string, mixed>|null  $evidence  a snapshot built by code (never by a model); kept as given
     */
    public function open(
        User $member,
        SupportTicketCategory $category,
        string $summary,
        array $claimedRefs = [],
        ?array $evidence = null,
        ?string $conversationId = null,
        SupportTicketSource $source = SupportTicketSource::Bot,
    ): SupportTicket|string {
        $summary = trim($summary);

        if (mb_strlen($summary) < self::SUMMARY_MIN) {
            return 'Tell us a little about what happened, so the person who picks this up knows where to start.';
        }

        if (mb_strlen($summary) > self::BODY_MAX) {
            return 'That is too long for a request. Keep it to the main points; you can add more once it is open.';
        }

        if (SupportTicket::ownedBy($member)->active()->count() >= (int) config('support.tickets.max_open')) {
            return 'You already have several open requests. Please wait for staff to answer those, or add to one of them.';
        }

        if (SupportTicket::ownedBy($member)->where('created_at', '>=', now()->subDay())->count() >= (int) config('support.tickets.max_per_day')) {
            return 'You have sent a lot of requests today. Please add to one that is already open.';
        }

        $refs = EntityRefs::validated($member, $claimedRefs);
        $severity = $this->severity->evaluate($member, $category, $refs, $summary);

        $ticket = DB::transaction(function () use ($member, $category, $summary, $refs, $severity, $evidence, $conversationId, $source) {
            $ticket = SupportTicket::create([
                'requester_id' => $member->getKey(), 'category' => $category, 'severity' => $severity, 'status' => SupportTicketStatus::Open, 'source' => $source,
                'conversation_id' => $conversationId, 'summary' => $summary, 'entity_refs' => EntityRefs::keys($refs) ?: null, 'evidence' => $evidence,
            ] + $this->clocks($severity, now()));

            $ticket->update(['reference' => 'SUP-'.(1000 + $ticket->id)]);
            $ticket->messages()->create(['sender' => SupportTicketMessage::MEMBER, 'member_id' => $member->getKey(), 'body' => $summary]);

            return $ticket;
        });

        Staff::permission('manage support tickets')->where('is_active', true)->get()->each->notify(new SupportTicketOpenedNotification($ticket));
        $member->notify(new SupportTicketReceivedNotification($ticket));

        return $ticket;
    }

    /**
     * The member writes back, with up to a few files. A ticket staff have already resolved reopens for a while; after that, or once closed, they start a
     * new one. A message may be only files. If any file is refused nothing is saved: the message and its files go in together or not at all.
     *
     * @param  array<int, mixed>  $files  uploaded files
     */
    public function memberReply(SupportTicket $ticket, User $member, string $body, array $files = []): SupportTicketMessage|string
    {
        if ($ticket->requester_id !== $member->getKey()) {
            return 'This is not your request.';
        }

        $body = trim($body);
        $hasFiles = array_filter($files) !== [];

        if ($body === '' && ! $hasFiles) {
            return 'Write a message first.';
        }

        if (mb_strlen($body) > self::BODY_MAX) {
            return 'That message is too long. Keep it shorter.';
        }

        if ($ticket->status === SupportTicketStatus::Closed || ($ticket->status === SupportTicketStatus::Resolved && $ticket->resolved_at?->lt(now()->subDays((int) config('support.tickets.reopen_days'))))) {
            return 'This request is closed. Please start a new one.';
        }

        $key = 'support-ticket-reply:'.$member->getKey();

        if (RateLimiter::tooManyAttempts($key, (int) config('support.tickets.max_replies_per_hour'))) {
            return 'You are writing a lot in a short time. Please wait a little.';
        }

        $prepared = $this->attachments->prepare($ticket, $files, $member->getKey());

        if (is_string($prepared)) {
            return $prepared;
        }

        RateLimiter::hit($key, 3600);

        $message = $this->saveWithFiles($ticket, $prepared, function () use ($ticket, $member, $body) {
            $message = $ticket->messages()->create(['sender' => SupportTicketMessage::MEMBER, 'member_id' => $member->getKey(), 'body' => $body]);

            if ($ticket->status === SupportTicketStatus::Resolved) {
                $ticket->update(['status' => SupportTicketStatus::PendingStaff, 'resolved_at' => null, 'resolution_tag' => null]);
            } elseif ($ticket->status === SupportTicketStatus::PendingMember) {
                $ticket->update(['status' => SupportTicketStatus::PendingStaff]);
            }

            return $message;
        });

        // Whoever has it, or everyone who answers tickets if nobody does yet
        $people = $ticket->assignee?->is_active ? collect([$ticket->assignee]) : Staff::permission('manage support tickets')->where('is_active', true)->get();
        $people->each->notify(new SupportTicketMemberRepliedNotification($ticket));

        return $message;
    }

    /**
     * Staff answer the member, optionally with files. The first answer stops the first-response clock.
     *
     * @param  array<int, mixed>  $files  uploaded files
     */
    public function staffReply(SupportTicket $ticket, Staff $by, string $body, array $files = []): SupportTicketMessage|string
    {
        $body = trim($body);
        $hasFiles = array_filter($files) !== [];

        if ($body === '' && ! $hasFiles) {
            return 'Write a reply first (up to '.number_format(self::BODY_MAX).' characters).';
        }

        if (mb_strlen($body) > self::BODY_MAX) {
            return 'Write a reply first (up to '.number_format(self::BODY_MAX).' characters).';
        }

        if ($ticket->status === SupportTicketStatus::Closed) {
            return 'This ticket is closed.';
        }

        $prepared = $this->attachments->prepare($ticket, $files);

        if (is_string($prepared)) {
            return $prepared;
        }

        $message = $this->saveWithFiles($ticket, $prepared, function () use ($ticket, $by, $body, $prepared) {
            $message = $ticket->messages()->create(['sender' => SupportTicketMessage::STAFF, 'staff_id' => $by->id, 'body' => $body]);
            $ticket->update(['status' => SupportTicketStatus::PendingMember, 'first_responded_at' => $ticket->first_responded_at ?? now()]);
            StaffAudit::log('support.ticket.replied', "Replied to {$ticket->reference}", $ticket, ['files' => count($prepared)], $by->id);

            return $message;
        });

        $ticket->requester?->notify(new SupportTicketRepliedNotification($ticket));

        return $message;
    }

    /**
     * Save a message and its files together. The message goes in a transaction and the files are written to disk after it; if anything fails the rows
     * roll back and the files already written are removed, so a failed reply never leaves orphan files behind.
     *
     * @param  list<array{bytes: string, mime: string, ext: string, kind: string, name: string}>  $prepared
     * @param  callable(): SupportTicketMessage  $create
     */
    private function saveWithFiles(SupportTicket $ticket, array $prepared, callable $create): SupportTicketMessage
    {
        $written = [];

        try {
            return DB::transaction(function () use ($ticket, $prepared, $create, &$written) {
                $message = $create();

                foreach ($prepared as $file) {
                    $written[] = $this->attachments->put($ticket, $message, $file);
                }

                return $message;
            });
        } catch (\Throwable $e) {
            foreach ($written as $attachment) {
                Storage::disk(config('support.tickets.attachments.disk'))->delete($attachment->path);
            }

            throw $e;
        }
    }

    /** An internal note: staff only, never shown to the member, and it changes nothing about the ticket. */
    public function note(SupportTicket $ticket, Staff $by, string $body): SupportTicketMessage|string
    {
        $body = trim($body);

        if ($body === '' || mb_strlen($body) > self::BODY_MAX) {
            return 'Write the note first.';
        }

        $message = $ticket->messages()->create(['sender' => SupportTicketMessage::NOTE, 'staff_id' => $by->id, 'body' => $body]);
        StaffAudit::log('support.ticket.noted', "Added a note to {$ticket->reference}", $ticket, [], $by->id);

        return $message;
    }

    public function assign(SupportTicket $ticket, Staff $by, ?Staff $to): SupportTicket
    {
        $ticket->update(['assignee_id' => $to?->id]);
        StaffAudit::log('support.ticket.assigned', $to ? "Assigned {$ticket->reference} to {$to->name}" : "Unassigned {$ticket->reference}", $ticket, ['assignee_id' => $to?->id], $by->id);

        return $ticket;
    }

    /** Staff change the urgency (for example to urgent): the clocks are worked out again from when the ticket was filed. */
    public function setSeverity(SupportTicket $ticket, Staff $by, SupportTicketSeverity $severity): SupportTicket
    {
        $from = $ticket->severity;
        $ticket->update(['severity' => $severity] + $this->clocks($severity, $ticket->created_at));
        StaffAudit::log('support.ticket.severity', "Changed {$ticket->reference} from {$from->value} to {$severity->value}", $ticket, ['from' => $from->value, 'to' => $severity->value], $by->id);

        return $ticket;
    }

    /** Staff say it is sorted, and why (the tag is how the assistant learns what it missed). An optional last reply goes to the member with it. */
    public function resolve(SupportTicket $ticket, Staff $by, SupportResolutionTag $tag, ?string $reply = null): SupportTicket|string
    {
        if (! $ticket->status->isActive()) {
            return 'This ticket is already resolved.';
        }

        $reply = trim((string) $reply);

        if (mb_strlen($reply) > self::BODY_MAX) {
            return 'That reply is too long.';
        }

        $resolved = DB::transaction(function () use ($ticket, $by, $tag, $reply) {
            if ($reply !== '') {
                $ticket->messages()->create(['sender' => SupportTicketMessage::STAFF, 'staff_id' => $by->id, 'body' => $reply]);
            }

            $ticket->update(['status' => SupportTicketStatus::Resolved, 'resolved_at' => now(), 'resolution_tag' => $tag, 'first_responded_at' => $ticket->first_responded_at ?? now()]);
            StaffAudit::log('support.ticket.resolved', "Resolved {$ticket->reference}", $ticket, ['tag' => $tag->value], $by->id);

            return $ticket;
        });

        $ticket->requester?->notify(new SupportTicketResolvedNotification($ticket));

        return $resolved;
    }

    public function close(SupportTicket $ticket, Staff $by): SupportTicket
    {
        $ticket->update(['status' => SupportTicketStatus::Closed, 'closed_at' => now()]);
        StaffAudit::log('support.ticket.closed', "Closed {$ticket->reference}", $ticket, [], $by->id);

        return $ticket;
    }

    /**
     * When a first reply and a resolution are due, in business time, from the moment the ticket was filed.
     *
     * @return array{first_response_due_at: mixed, resolution_due_at: mixed}
     */
    private function clocks(SupportTicketSeverity $severity, $from): array
    {
        $target = (array) config("support.tickets.targets.{$severity->value}");

        return [
            'first_response_due_at' => isset($target['first_response']) ? $this->hours->addMinutes($from, (int) $target['first_response']) : null,
            'resolution_due_at' => isset($target['resolution']) && $target['resolution'] !== null ? $this->hours->addMinutes($from, (int) $target['resolution']) : null,
        ];
    }
}
