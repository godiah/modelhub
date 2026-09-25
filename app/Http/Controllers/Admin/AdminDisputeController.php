<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DisputeStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dispute\ResolveDisputeRequest;
use App\Models\JobPaymentDispute;
use App\Services\Payments\PartialPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminDisputeController extends Controller
{
    protected $partialPaymentService;

    public function __construct(PartialPaymentService $partialPaymentService)
    {
        $this->partialPaymentService = $partialPaymentService;
    }

    public function index(Request $request)
    {
        $status = $request->get('status', 'all');

        $query = JobPaymentDispute::with(['assignedAdmin', 'cancellation'])
            ->latest();

        // Filter by status if specified
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $disputes = $query->paginate(12)->appends(['status' => $status]);

        // Get counts for each status
        $statusCounts = [
            'all' => JobPaymentDispute::count(),
            'pending' => JobPaymentDispute::where('status', DisputeStatus::Pending)->count(),
            'under_review' => JobPaymentDispute::where('status', DisputeStatus::UnderReview)->count(),
            'resolved' => JobPaymentDispute::where('status', DisputeStatus::Resolved)->count(),
        ];

        return view('admin.disputes.index', compact('disputes', 'status', 'statusCounts'));
    }

    public function assign(JobPaymentDispute $dispute)
    {
        $dispute->assignAdmin(Auth::id());

        return redirect()->back()->with('success', 'Dispute assigned to you successfully.');
    }

    public function resolve(ResolveDisputeRequest $request, JobPaymentDispute $dispute)
    {
        try {
            $result = $this->partialPaymentService->resolveDispute(
                $dispute,
                $request->input('resolution_notes'),
                $request->input('resolution_amount')
            );

            return redirect()->back()->with('success', 'Dispute resolved successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to resolve dispute: '.$e->getMessage());
        }
    }
}
