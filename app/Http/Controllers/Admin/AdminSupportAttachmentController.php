<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ServesTicketAttachments;
use App\Http\Controllers\Controller;
use App\Models\SupportTicket;

/** A file on a ticket, for staff who may see tickets (view support tickets). */
class AdminSupportAttachmentController extends Controller
{
    use ServesTicketAttachments;

    public function show(SupportTicket $ticket, int $attachment)
    {
        return $this->sendAttachment($ticket, $attachment, includeNotes: true);
    }
}
