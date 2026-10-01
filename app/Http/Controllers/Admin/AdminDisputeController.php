<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DisputeStatus;
use App\Helpers\FlashAlertHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dispute\ResolveDisputeRequest;
use App\Models\JobCancellation;
use App\Models\JobPaymentDispute;
use App\Services\Payments\PartialPaymentService;
use App\Support\Staff\StaffAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/** The queue of payment disputes between clients and freelancers. Permissions: view disputes, resolve disputes (see routes/web.php). */
class AdminDisputeController extends Controller
{
    public function __construct(protected PartialPaymentService $partialPaymentService) {}

    public function index(Request $request)
    {
        $status = in_array($request->query('status'), ['pending', 'under_review', 'resolved', 'all'], true) ? $request->query('status') : 'pending';

        $disputes = JobPaymentDispute::with([
            'disputedBy:id,name',
            'assignedAdmin:id,name',
            'resolvedBy:id,name',
            'cancellation.engagement.application.job:id,title',
            'cancellation.engagement.application.poster:id,name',
            'cancellation.engagement.application.applicant:id,name',
        ])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            // Open disputes first, the one that has waited longest at the top; resolved ones after, newest first
            ->orderByRaw("case status when 'resolved' then 1 else 0 end")
            ->orderByRaw("case status when 'resolved' then -unix_timestamp(created_at) else unix_timestamp(created_at) end")
            ->paginate(10)
            ->withQueryString();

        $byStatus = JobPaymentDispute::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.disputes.index', [
            'disputes' => $disputes,
            'status' => $status,
            'counts' => [
                'pending' => (int) ($byStatus[DisputeStatus::Pending->value] ?? 0),
                'under_review' => (int) ($byStatus[DisputeStatus::UnderReview->value] ?? 0),
                'resolved' => (int) ($byStatus[DisputeStatus::Resolved->value] ?? 0),
                'all' => (int) $byStatus->sum(),
            ],
        ]);
    }

    /** One dispute, with everything staff need to decide it. Linked from the queue and from new-dispute notifications. */
    public function show(JobCancellation $cancellation)
    {
        $dispute = $cancellation->dispute()->with([
            'disputedBy:id,name,email,avatar',
            'assignedAdmin:id,name',
            'resolvedBy:id,name',
            'cancellation.initiator:id,name,email,avatar',
            'cancellation.engagement.application.job:id,title,slug',
            'cancellation.engagement.application.poster:id,name,email,avatar',
            'cancellation.engagement.application.applicant:id,name,email,avatar',
            'cancellation.engagement.deliverables:id,engagement_id,status',
            'partialPayment.processor:id,name',
            'partialPayment.finalizer:id,name',
        ])->firstOrFail();

        return view('admin.disputes.show', ['dispute' => $dispute]);
    }

    /** Staff open the files the filing party attached. Private disk: never a public URL. */
    public function evidence(JobPaymentDispute $dispute, int $index)
    {
        $path = $dispute->supporting_evidence[$index] ?? null;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }

    public function assign(JobPaymentDispute $dispute)
    {
        if ($dispute->isResolved()) {
            return back()->with(FlashAlertHelper::error('Cannot assign', 'This dispute has already been resolved.'));
        }

        $dispute->assignAdmin(Auth::id());
        StaffAudit::log('dispute.assigned', 'Took on dispute #'.$dispute->id, $dispute);

        return back()->with(FlashAlertHelper::success('Dispute assigned to you', 'It is now under review.'));
    }

    public function resolve(ResolveDisputeRequest $request, JobPaymentDispute $dispute)
    {
        try {
            $this->partialPaymentService->resolveDispute(
                $dispute,
                $request->input('resolution_notes'),
                $request->input('resolution_amount')
            );
            StaffAudit::log('dispute.resolved', 'Resolved dispute #'.$dispute->id, $dispute, ['amount' => $request->input('resolution_amount')]);

            return back()->with(FlashAlertHelper::success('Dispute resolved'));
        } catch (\Exception $e) {
            return back()->with(FlashAlertHelper::error('Could not resolve the dispute', $e->getMessage()));
        }
    }
}
