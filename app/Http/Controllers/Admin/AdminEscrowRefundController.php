<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EngagementStatus;
use App\Enums\PaymentStatus;
use App\Helpers\FlashAlertHelper;
use App\Http\Controllers\Controller;
use App\Models\EscrowRefund;
use App\Models\JobEngagement;
use App\Services\Payments\EscrowService;
use App\Support\Money;
use Illuminate\Http\Request;

/**
 * Money left in a job's escrow that has become the client's to have back: staff record it (which posts the books) and send it by M-Pesa to the
 * number the client paid from. Permissions: view payments, refund payments.
 */
class AdminEscrowRefundController extends Controller
{
    public function __construct(protected EscrowService $escrow) {}

    public function index(Request $request)
    {
        $tabs = ['due' => 'Due back', 'waiting' => 'Waiting', 'returned' => 'Returned'];
        $tab = array_key_exists($request->query('tab'), $tabs) ? $request->query('tab') : 'due';

        $held = fn () => JobEngagement::where('escrow_minor', '>', 0)->whereRaw('escrow_minor - released_net_minor - released_fee_minor - refunded_minor > 0');
        $waiting = fn () => $held()->where(fn ($q) => $q->where('escrow_refund_due_at', '>', now())->orWhere(fn ($n) => $n->whereNull('escrow_refund_due_at')->whereIn('status', [EngagementStatus::Cancelled, EngagementStatus::Disputed, EngagementStatus::Settled])));

        $with = ['application.job:id,title', 'application.poster:id,name,avatar', 'application.applicant:id,name', 'payments' => fn ($q) => $q->where('purpose', 'escrow')->where('status', PaymentStatus::Succeeded)];

        $rows = match ($tab) {
            'due' => JobEngagement::escrowRefundDue()->with($with)->orderBy('escrow_refund_due_at')->paginate(15),
            'waiting' => $waiting()->with($with)->orderByRaw('escrow_refund_due_at is null, escrow_refund_due_at')->paginate(15),
            'returned' => EscrowRefund::with(['engagement.application.job:id,title', 'client:id,name,avatar', 'staff:id,name'])->latest('id')->paginate(15),
        };

        return view('admin.escrow-refunds.index', [
            'tabs' => $tabs, 'tab' => $tab, 'rows' => $rows,
            'counts' => ['due' => JobEngagement::escrowRefundDue()->count(), 'waiting' => $waiting()->count(), 'returned' => EscrowRefund::count()],
            'canRefund' => $request->user()->can('refund payments'),
        ]);
    }

    public function refund(Request $request, JobEngagement $engagement)
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:255']]);
        $result = $this->escrow->refundRemaining($engagement, $request->user(), $data['note'] ?? null);

        if (is_string($result)) {
            return back()->with(FlashAlertHelper::error('Cannot record this refund', $result));
        }

        return back()->with(FlashAlertHelper::success('Return recorded', Money::formatMinor($result->amount_minor).' for '.$result->client->name.'. Now send it from the M-Pesa portal'.($result->msisdn ? ' to the number they paid from.' : '.')));
    }
}
