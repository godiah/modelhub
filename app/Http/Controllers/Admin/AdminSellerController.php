<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FlashAlertHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\ReviewSellerRequest;
use App\Models\SellerProfile;
use App\Services\Marketplace\SellerOnboardingService;
use Illuminate\Http\Request;

/** The review queue for seller applications. Permission: review sellers (see routes/web.php). */
class AdminSellerController extends Controller
{
    public function __construct(protected SellerOnboardingService $onboarding) {}

    public function index(Request $request)
    {
        $status = in_array($request->query('status'), ['all', 'pending', 'approved', 'rejected', 'suspended'], true)
            ? $request->query('status')
            : 'pending';

        $sellers = SellerProfile::with(['user', 'reviewer'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByRaw("case status when 'pending' then 0 else 1 end")
            ->latest('submitted_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.sellers.index', [
            'sellers' => $sellers,
            'status' => $status,
            'counts' => $this->onboarding->counts(),
        ]);
    }

    public function review(ReviewSellerRequest $request, SellerProfile $seller, string $decision)
    {
        if ($error = $this->onboarding->review($seller, $request->user(), $decision, $request->input('notes'))) {
            return back()->with(FlashAlertHelper::error('Cannot do that', $error));
        }

        return back()->with(FlashAlertHelper::success(match ($decision) {
            'approve' => 'Seller approved',
            'reject' => 'Application rejected',
            default => 'Seller suspended',
        }, 'The seller has been told.'));
    }

    /** Take down an unsuitable logo. The seller keeps their store and can upload another. */
}
