<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesTicketAttachments;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

/** A file on one of the member's own requests. Someone else's request, a file that is not on it and a staff-only file are all the same 404. */
class SupportAttachmentController extends Controller
{
    use ServesTicketAttachments;

    public function show(Request $request, string $reference, int $attachment)
    {
        $ticket = SupportTicket::ownedBy($request->user())->where('reference', $reference)->firstOrFail();

        return $this->sendAttachment($ticket, $attachment, includeNotes: false);
    }
}
