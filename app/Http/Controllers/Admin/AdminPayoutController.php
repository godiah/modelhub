<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PayoutStatus;
use App\Helpers\FlashAlertHelper;
use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Services\Payments\PayoutService;
use App\Support\Money;
use App\Support\Staff\ListSort;
use Illuminate\Http\Request;

/** The withdrawals queue: what members have asked to take out, to approve (which sends the money) or turn down. Permissions: view payouts, approve payouts. */
class AdminPayoutController extends Controller
{
    public const SORTS = ['requested' => 'created_at', 'member' => 'user_id', 'amount' => 'amount_minor'];

    public function __construct(protected PayoutService $payouts) {}

    public function index(Request $request)
    {
        $statuses = ['requested' => 'Waiting', 'processing' => 'Being sent', 'paid' => 'Paid', 'failed' => 'Failed', 'rejected' => 'Turned down or cancelled', 'all' => 'All'];
        $status = array_key_exists($request->query('status'), $statuses) ? $request->query('status') : 'requested';
        [$sort, $dir] = ListSort::resolve($request, array_keys(self::SORTS), default: 'requested', descFirst: ['amount']);

        // Working the queue, the oldest request comes first; looking back, the newest does
        if (! $request->query('dir') && $sort === 'requested' && $status !== 'requested') {
            $dir = 'desc';
        }

        $payouts = Payout::with('user:id,name,email,avatar', 'approver:id,name')
            ->when($status === 'rejected', fn ($query) => $query->whereIn('status', [PayoutStatus::Rejected, PayoutStatus::Cancelled]))
            ->when(! in_array($status, ['all', 'rejected'], true), fn ($query) => $query->where('status', $status))
            ->tap(fn ($query) => ListSort::apply($query, $sort, $dir, self::SORTS))
            ->paginate(15)->withQueryString();

        $byStatus = Payout::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.payouts.index', [
            'payouts' => $payouts, 'statuses' => $statuses, 'status' => $status, 'sort' => $sort, 'dir' => $dir,
            'counts' => collect($statuses)->mapWithKeys(fn ($label, $key) => [$key => match ($key) {
                'all' => (int) $byStatus->sum(),
                'rejected' => (int) (($byStatus['rejected'] ?? 0) + ($byStatus['cancelled'] ?? 0)),
                default => (int) ($byStatus[$key] ?? 0),
            }])->all(),
            'canApprove' => $request->user()->can('approve payouts'),
        ]);
    }

    public function approve(Request $request, Payout $payout)
    {
        $result = $this->payouts->approve($payout, $request->user());

        if (is_string($result)) {
            return back()->with(FlashAlertHelper::error('Cannot approve', $result));
        }

        return back()->with($result->status === PayoutStatus::Failed
            ? FlashAlertHelper::error('The transfer could not be started', $result->failure_reason.' The money is back in the member\'s balance.')
            : FlashAlertHelper::success('Withdrawal approved', Money::formatMinor($result->net_minor, 0).' is being sent to '.$result->user->name.'.'));
    }

    public function reject(Request $request, Payout $payout)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:255']]);
        $result = $this->payouts->reject($payout, $request->user(), $data['reason']);

        return is_string($result)
            ? back()->with(FlashAlertHelper::error('Cannot turn this down', $result))
            : back()->with(FlashAlertHelper::success('Withdrawal turned down', "The money is back in {$result->user->name}'s balance and they have been told why."));
    }
}
