<?php

namespace App\Http\Controllers;

use App\Enums\SupportTicketStatus;
use App\Helpers\FlashAlertHelper;
use App\Models\SupportTicket;
use App\Services\Support\Tickets\TicketService;
use Illuminate\Http\Request;

/**
 * "My requests": the member's own support tickets and their thread. Every query starts from the member's own tickets (ownedBy), so someone else's
 * reference is the same 404 as one that does not exist. Staff's internal notes are never loaded here.
 */
class SupportRequestController extends Controller
{
    public function __construct(private readonly TicketService $tickets) {}

    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'resolved' ? 'resolved' : 'open';
        $mine = SupportTicket::ownedBy($request->user());

        $tickets = (clone $mine)
            ->when($tab === 'open', fn ($q) => $q->active(), fn ($q) => $q->whereIn('status', [SupportTicketStatus::Resolved->value, SupportTicketStatus::Closed->value]))
            ->latest('updated_at')->paginate(10)->withQueryString();

        return view('support.tickets.index', [
            'tickets' => $tickets,
            'tab' => $tab,
            'counts' => ['open' => (clone $mine)->active()->count(), 'resolved' => (clone $mine)->whereIn('status', [SupportTicketStatus::Resolved->value, SupportTicketStatus::Closed->value])->count()],
        ]);
    }

    public function show(Request $request, string $reference)
    {
        $ticket = SupportTicket::ownedBy($request->user())->where('reference', $reference)->firstOrFail();
        $ticket->load(['memberMessages.staff:id,name', 'memberMessages.member:id,name', 'memberMessages.attachments']);

        return view('support.tickets.show', ['ticket' => $ticket]);
    }

    public function reply(Request $request, string $reference)
    {
        $ticket = SupportTicket::ownedBy($request->user())->where('reference', $reference)->firstOrFail();
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:'.TicketService::BODY_MAX],
            'files' => ['nullable', 'array', 'max:'.(int) config('support.tickets.attachments.max_files')],
            'files.*' => ['file'],
        ]);

        $result = $this->tickets->memberReply($ticket, $request->user(), (string) ($data['body'] ?? ''), $request->file('files', []));

        return is_string($result)
            ? back()->withInput()->with(FlashAlertHelper::error('Not sent', $result))
            : redirect()->route('support.requests.show', $ticket)->with(FlashAlertHelper::success('Reply sent', 'Staff will see it.'));
    }
}
