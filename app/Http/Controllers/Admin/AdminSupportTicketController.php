<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportResolutionTag;
use App\Enums\SupportTicketSeverity;
use App\Enums\SupportTicketStatus;
use App\Helpers\FlashAlertHelper;
use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\SupportTicket;
use App\Services\Support\Tickets\TicketService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The support queue: what members have handed to staff. Permissions: view support tickets (see the queue and a ticket), manage support tickets
 * (reply, note, assign, change urgency, resolve), read support transcripts (the assistant's conversation; added with T6). Whatever a member wrote is
 * drawn escaped by the views; nothing here treats it as markup or as an instruction.
 */
class AdminSupportTicketController extends Controller
{
    public function __construct(private readonly TicketService $tickets) {}

    public function index(Request $request)
    {
        $tabs = ['queue' => 'Needs staff', 'mine' => 'Mine', 'waiting' => 'Waiting for member', 'overdue' => 'Overdue', 'resolved' => 'Resolved', 'all' => 'All'];
        $tab = array_key_exists($request->query('tab'), $tabs) ? $request->query('tab') : 'queue';
        $search = trim((string) $request->query('q'));

        $apply = fn ($query, string $key) => match ($key) {
            'queue' => $query->needingStaff(),
            'mine' => $query->active()->where('assignee_id', auth()->id()),
            'waiting' => $query->where('status', SupportTicketStatus::PendingMember->value),
            'overdue' => $query->needingStaff()->where(fn ($q) => $q->where(fn ($a) => $a->whereNull('first_responded_at')->where('first_response_due_at', '<', now()))
                ->orWhere(fn ($b) => $b->whereNotNull('first_responded_at')->where('resolution_due_at', '<', now()))),
            'resolved' => $query->whereIn('status', [SupportTicketStatus::Resolved->value, SupportTicketStatus::Closed->value]),
            default => $query,
        };

        $tickets = $apply(SupportTicket::with(['requester:id,name', 'assignee:id,name']), $tab)
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('reference', 'like', '%'.$search.'%')->orWhere('summary', 'like', '%'.$search.'%')
                ->orWhereHas('requester', fn ($r) => $r->where('name', 'like', '%'.$search.'%'))))
            // The most urgent first; within a severity, the one whose reply is due soonest (what is already late comes first)
            ->orderByRaw("case severity when 'urgent' then 0 when 'high' then 1 when 'normal' then 2 else 3 end")
            ->orderByRaw('coalesce(first_response_due_at, created_at)')
            ->paginate(15)->withQueryString();

        $counts = collect($tabs)->map(fn ($label, $key) => $apply(SupportTicket::query(), $key)->count())->all();

        return view('admin.support.tickets.index', ['tickets' => $tickets, 'tab' => $tab, 'tabs' => $tabs, 'counts' => $counts, 'search' => $search]);
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['requester:id,name,email', 'assignee:id,name', 'messages.member:id,name', 'messages.staff:id,name', 'messages.attachments']);

        return view('admin.support.tickets.show', [
            'ticket' => $ticket,
            'team' => Staff::permission('manage support tickets')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'tags' => SupportResolutionTag::cases(),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:'.TicketService::BODY_MAX],
            'files' => ['nullable', 'array', 'max:'.(int) config('support.tickets.attachments.max_files')],
            'files.*' => ['file'],
        ]);

        return $this->done($this->tickets->staffReply($ticket, $request->user(), (string) ($data['body'] ?? ''), $request->file('files', [])), 'Reply sent', 'The member has been told.');
    }

    public function note(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:'.TicketService::BODY_MAX]]);

        return $this->done($this->tickets->note($ticket, $request->user(), $data['body']), 'Note added', 'Only staff can see it.');
    }

    public function assign(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate(['assignee_id' => ['nullable', 'integer']]);
        $to = $data['assignee_id'] ?? null ? Staff::permission('manage support tickets')->where('is_active', true)->find($data['assignee_id']) : null;

        if (($data['assignee_id'] ?? null) && ! $to) {
            return back()->with(FlashAlertHelper::error('Cannot assign', 'That person does not answer support tickets.'));
        }

        $this->tickets->assign($ticket, $request->user(), $to);

        return back()->with(FlashAlertHelper::success($to ? "Assigned to {$to->name}" : 'Unassigned'));
    }

    public function severity(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate(['severity' => ['required', Rule::enum(SupportTicketSeverity::class)]]);
        $this->tickets->setSeverity($ticket, $request->user(), SupportTicketSeverity::from($data['severity']));

        return back()->with(FlashAlertHelper::success('Urgency changed', 'The reply times were worked out again from when it was filed.'));
    }

    public function resolve(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate(['tag' => ['required', Rule::enum(SupportResolutionTag::class)], 'body' => ['nullable', 'string', 'max:'.TicketService::BODY_MAX]]);

        return $this->done($this->tickets->resolve($ticket, $request->user(), SupportResolutionTag::from($data['tag']), $data['body'] ?? null), 'Ticket resolved', 'The member can still reply for a while.');
    }

    public function close(Request $request, SupportTicket $ticket)
    {
        $this->tickets->close($ticket, $request->user());

        return back()->with(FlashAlertHelper::success('Ticket closed'));
    }

    /** A service method answers with what it made, or with the reason it could not: show that reason to the staff member. */
    private function done(mixed $result, string $title, string $message)
    {
        return is_string($result)
            ? back()->withInput()->with(FlashAlertHelper::error('Not done', $result))
            : back()->with(FlashAlertHelper::success($title, $message));
    }
}
