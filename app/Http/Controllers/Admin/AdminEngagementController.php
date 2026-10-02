<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EngagementStatus;
use App\Http\Controllers\Controller;
use App\Models\JobEngagement;
use App\Models\LedgerTransaction;
use App\Models\Payment;
use App\Services\Payments\EscrowService;
use App\Support\Staff\ListSort;
use Illuminate\Http\Request;

/** Read-only oversight of hires. Permission: view engagements. Private messages are never shown here. */
class AdminEngagementController extends Controller
{
    /** Sortable columns: sort key => the column it orders by. */
    public const SORTS = ['created' => 'created_at', 'amount' => 'agreed_amount', 'started' => 'started_at'];

    public function index(Request $request)
    {
        $statuses = collect(EngagementStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all();
        $status = array_key_exists($request->query('status'), $statuses) ? $request->query('status') : 'all';
        $term = trim((string) $request->query('q'));
        [$sort, $dir] = ListSort::resolve($request, array_keys(self::SORTS), default: 'created', descFirst: ['created', 'amount', 'started']);

        $engagements = JobEngagement::with(['application.job:id,title', 'application.poster:id,name,avatar', 'application.applicant:id,name,avatar'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($term !== '', fn ($query) => $query->whereHas('application', fn ($a) => $a
                ->whereHas('job', fn ($j) => $j->where('title', 'like', "%{$term}%"))
                ->orWhereHas('poster', fn ($u) => $u->where('name', 'like', "%{$term}%"))
                ->orWhereHas('applicant', fn ($u) => $u->where('name', 'like', "%{$term}%"))))
            ->tap(fn ($query) => ListSort::apply($query, $sort, $dir, self::SORTS))
            ->paginate(12)
            ->withQueryString();

        $byStatus = JobEngagement::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.engagements.index', ['engagements' => $engagements, 'statuses' => $statuses, 'status' => $status, 'term' => $term, 'sort' => $sort, 'dir' => $dir, 'counts' => $byStatus]);
    }

    public function show(JobEngagement $engagement)
    {
        $engagement->load(['application.job:id,title,slug,budget', 'application.poster:id,name,avatar', 'application.applicant:id,name,avatar', 'deliverables', 'cancellation.dispute', 'partialPayments', 'escrowRefunds.staff:id,name']);

        // The funding payments, and every posting this job's money made (funding is posted against the payment, the rest against the job)
        $payments = Payment::where('engagement_id', $engagement->id)->where('purpose', Payment::PURPOSE_ESCROW)->orderBy('id')->get();
        $postings = LedgerTransaction::with('entries.account')
            ->where(fn ($q) => $q->where(fn ($j) => $j->where('reference_type', $engagement->getMorphClass())->where('reference_id', $engagement->id))
                ->orWhere(fn ($p) => $p->where('reference_type', (new Payment)->getMorphClass())->whereIn('reference_id', $payments->pluck('id'))))
            ->orderBy('id')->get();

        return view('admin.engagements.show', ['engagement' => $engagement, 'escrow' => app(EscrowService::class)->summary($engagement), 'fundingPayments' => $payments, 'postings' => $postings, 'canRefund' => request()->user()->can('refund payments')]);
    }
}
