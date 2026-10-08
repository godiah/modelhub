<?php

namespace App\Models;

use App\Enums\SupportResolutionTag;
use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketSeverity;
use App\Enums\SupportTicketSource;
use App\Enums\SupportTicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A request a member (or, later, a visitor) has handed to staff. A thread, not a form: the member and staff write back and forth, and staff keep
 * internal notes the member never sees. Changed only through TicketService, which keeps the status, the clocks and the audit trail in step.
 */
class SupportTicket extends Model
{
    protected $fillable = [
        'reference', 'requester_id', 'category', 'severity', 'status', 'source', 'conversation_id', 'summary', 'entity_refs', 'evidence', 'assignee_id',
        'first_response_due_at', 'resolution_due_at', 'first_responded_at', 'resolved_at', 'closed_at', 'resolution_tag',
    ];

    protected function casts(): array
    {
        return [
            'category' => SupportTicketCategory::class, 'severity' => SupportTicketSeverity::class, 'status' => SupportTicketStatus::class,
            'source' => SupportTicketSource::class, 'resolution_tag' => SupportResolutionTag::class, 'entity_refs' => 'array', 'evidence' => 'array',
            'first_response_due_at' => 'datetime', 'resolution_due_at' => 'datetime', 'first_responded_at' => 'datetime', 'resolved_at' => 'datetime', 'closed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assignee_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)->orderBy('id');
    }

    /** What the member may read: everything but staff's internal notes. */
    public function memberMessages(): HasMany
    {
        return $this->messages()->where('sender', '!=', SupportTicketMessage::NOTE);
    }

    /** A member's own tickets. Every member-facing query starts here, so someone else's reference is the same "not found" as a missing one. */
    public function scopeOwnedBy(Builder $query, User $member): Builder
    {
        return $query->where('requester_id', $member->getKey());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [SupportTicketStatus::Open->value, SupportTicketStatus::PendingMember->value, SupportTicketStatus::PendingStaff->value]);
    }

    /** The queue: tickets waiting on staff. */
    public function scopeNeedingStaff(Builder $query): Builder
    {
        return $query->whereIn('status', [SupportTicketStatus::Open->value, SupportTicketStatus::PendingStaff->value]);
    }

    /** Waiting on staff and already past its due time (the first reply until there is one, then the resolution). The one definition the queue, the dashboard and the service levels all use. */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->needingStaff()->where(fn (Builder $q) => $q
            ->where(fn (Builder $a) => $a->whereNull('first_responded_at')->where('first_response_due_at', '<', now()))
            ->orWhere(fn (Builder $b) => $b->whereNotNull('first_responded_at')->where('resolution_due_at', '<', now())));
    }

    /** Waiting on staff, not yet late, but due within the next `$minutes`. */
    public function scopeDueSoon(Builder $query, int $minutes = 120): Builder
    {
        $until = now()->addMinutes($minutes);

        return $query->needingStaff()->where(fn (Builder $q) => $q
            ->where(fn (Builder $a) => $a->whereNull('first_responded_at')->whereBetween('first_response_due_at', [now(), $until]))
            ->orWhere(fn (Builder $b) => $b->whereNotNull('first_responded_at')->whereBetween('resolution_due_at', [now(), $until])));
    }

    public function isOverdue(): bool
    {
        if (! $this->status->needsStaff()) {
            return false;
        }

        $due = $this->first_responded_at === null ? $this->first_response_due_at : $this->resolution_due_at;

        return $due !== null && $due->isPast();
    }
}
