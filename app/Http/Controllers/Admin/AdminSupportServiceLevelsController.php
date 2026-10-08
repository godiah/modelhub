<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SupportTicketSeverity;
use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\SupportTicket;
use App\Services\Support\Tickets\TicketTargets;

/**
 * Service levels: how quickly each kind of request should get a first reply, when the clock runs, how urgency is decided, and where the team stands
 * right now. The numbers are read from config/support.php, the same place the clocks are worked out from, so what this page says is what the
 * clocks do and what members are told. They are provisional (O-11), and changing them is a change to that file, not a form.
 */
class AdminSupportServiceLevelsController extends Controller
{
    public function show()
    {
        $levels = collect(array_reverse(SupportTicketSeverity::cases()))->map(fn (SupportTicketSeverity $severity) => [
            'severity' => $severity,
            'first' => TicketTargets::firstReplyAmount($severity),
            'resolution' => TicketTargets::resolutionAmount($severity),
            'members_read' => TicketTargets::sentence($severity),
        ]);

        $overdue = SupportTicket::overdue()->count();
        $waiting = SupportTicket::needingStaff()->count();

        return view('admin.support.service-levels', [
            'levels' => $levels,
            'hours' => TicketTargets::hours(),
            'staleHours' => (int) config('payments.payout_stale_hours'),
            'urgentWords' => (array) config('support.tickets.urgent_words'),
            'status' => [
                'waiting' => $waiting,
                'overdue' => $overdue,
                'soon' => SupportTicket::dueSoon(120)->count(),
                'unassigned' => SupportTicket::needingStaff()->whereNull('assignee_id')->count(),
            ],
            'team' => Staff::permission('manage support tickets')->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn (Staff $person) => [
                    'name' => $person->name,
                    'open' => SupportTicket::active()->where('assignee_id', $person->id)->count(),
                    'overdue' => SupportTicket::overdue()->where('assignee_id', $person->id)->count(),
                ])->all(),
        ]);
    }
}
