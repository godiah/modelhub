<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** A file attached to a ticket message: a JPEG or PNG that ModelHub re-encoded, or a checked PDF. The file lives on a private disk under a random name. */
class SupportTicketAttachment extends Model
{
    public const IMAGE = 'image';

    public const PDF = 'pdf';

    protected $fillable = ['support_ticket_id', 'support_ticket_message_id', 'original_name', 'path', 'mime', 'kind', 'size', 'checksum'];

    protected static function booted(): void
    {
        // Deleting the row (or its message or ticket, through a model delete) takes the file with it
        static::deleting(fn (self $attachment) => Storage::disk(config('support.tickets.attachments.disk'))->delete($attachment->path));
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(SupportTicketMessage::class, 'support_ticket_message_id');
    }

    public function isImage(): bool
    {
        return $this->kind === self::IMAGE;
    }

    public function humanSize(): string
    {
        return $this->size >= 1048576 ? number_format($this->size / 1048576, 1).' MB' : max(1, (int) round($this->size / 1024)).' KB';
    }
}
