<?php

namespace App\Http\Controllers\Concerns;

use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sending a ticket attachment to someone who has already been shown to be allowed to see the ticket. A picture (which ModelHub re-encoded) is shown in the
 * page; a PDF is only ever downloaded. Either way the browser is told not to guess the type, not to keep a copy, and not to run anything in the file.
 */
trait ServesTicketAttachments
{
    /** @param  bool  $includeNotes  staff may see attachments on internal notes; a member never may */
    private function sendAttachment(SupportTicket $ticket, int|string $id, bool $includeNotes): Response
    {
        $attachment = $ticket->attachments()->whereKey($id)->with('message:id,sender')->firstOrFail();

        if (! $includeNotes && $attachment->message?->sender === SupportTicketMessage::NOTE) {
            abort(404);
        }

        $disk = Storage::disk(config('support.tickets.attachments.disk'));

        abort_unless($disk->exists($attachment->path), 404);

        return $disk->response($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ], $attachment->kind === SupportTicketAttachment::IMAGE ? 'inline' : 'attachment');
    }
}
