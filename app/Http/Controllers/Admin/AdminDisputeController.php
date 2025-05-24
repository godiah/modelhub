<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobPaymentDispute;
use App\Services\PartialPaymentService;
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

        $query = JobPaymentDispute::with(['assignedAdmin', 'resolvedBy', 'cancellation.engagement'])
            ->latest();

        // Filter by status if specified
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $disputes = $query->paginate(12)->appends(['status' => $status]);

        // Get counts for each status
        $statusCounts = [
            'all' => JobPaymentDispute::count(),
            'pending' => JobPaymentDispute::where('status', 'pending')->count(),
            'under_review' => JobPaymentDispute::where('status', 'under_review')->count(),
            'resolved' => JobPaymentDispute::where('status', 'resolved')->count(),
        ];

        return view('admin.disputes.index', compact('disputes', 'status', 'statusCounts'));
    }

    public function assign($id)
    {
        $dispute = JobPaymentDispute::findOrFail($id);
        $dispute->assignAdmin(Auth::id());

        return redirect()->back()->with('success', 'Dispute assigned to you successfully.');
    }

    public function resolve(Request $request, JobPaymentDispute $dispute)
    {
        $request->validate([
            'resolution_notes' => 'required|string|max:1000',
            'resolution_amount' => 'nullable|numeric|min:0'
        ]);

        try {
            $result = $this->partialPaymentService->resolveDispute(
                $dispute,
                $request->input('resolution_notes'),
                $request->input('resolution_amount')
            );

            return redirect()->route('admin.disputes.index')->with('success', 'Dispute resolved successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.disputes.index')->with('error', 'Failed to resolve dispute: ' . $e->getMessage());
        }
    }
}
