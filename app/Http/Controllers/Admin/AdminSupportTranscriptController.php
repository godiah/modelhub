<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\Support\SupportAgentClient;
use App\Support\Staff\StaffAudit;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Throwable;

/**
 * Staff reading the whole chat a member had with the assistant, for one ticket. Needs `read support transcripts` (on top of seeing the queue).
 * The chat is not copied into this app: it is fetched from the assistant with a one-minute claim naming this staff member, this chat and this
 * member, and every opening is written to the activity log BEFORE the fetch, so a failed or slow read still leaves a trace. The member's words
 * are drawn escaped and marked as theirs; nothing in them is ever treated as an instruction.
 */
class AdminSupportTranscriptController extends Controller
{
    public function __construct(private readonly SupportAgentClient $agent) {}

    public function show(Request $request, SupportTicket $ticket)
    {
        // Only a ticket made from a chat has a transcript, and only while the member's account exists to scope it to
        abort_if($ticket->conversation_id === null || $ticket->requester === null, 404);

        $staff = $request->user();

        StaffAudit::log('support.transcript.read', "Read the assistant chat behind {$ticket->reference}", $ticket, ['conversation' => $ticket->conversation_id], $staff->id);

        $messages = null;
        $truncated = false;
        $problem = null;

        try {
            $response = $this->agent->transcript($staff, $ticket->conversation_id, $ticket->requester, $request->header('X-Request-ID'));

            if ($response->successful() && is_array($response->json('messages'))) {
                $messages = $response->json('messages');
                $truncated = (bool) $response->json('truncated');
            } elseif ($response->status() === 404) {
                $problem = __('The assistant no longer has this chat.');
            } else {
                $problem = __('The assistant could not be reached just now. Try again in a moment.');
            }
        } catch (ConnectionException) {
            $problem = __('The assistant could not be reached just now. Try again in a moment.');
        } catch (Throwable $e) {
            report($e);
            $problem = __('The chat could not be loaded.');
        }

        return view('admin.support.tickets.transcript', ['ticket' => $ticket, 'messages' => $messages, 'truncated' => $truncated, 'problem' => $problem]);
    }
}
