<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One line of a ticket thread. `note` is internal: staff only, never shown to the member. Bodies are plain text and are always escaped when drawn. */
class SupportTicketMessage extends Model
{
    public const MEMBER = 'member';

    public const STAFF = 'staff';

    public const SYSTEM = 'system';

    public const NOTE = 'note';

    protected $fillable = ['support_ticket_id', 'sender', 'member_id', 'staff_id', 'body'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class);
    }
}
