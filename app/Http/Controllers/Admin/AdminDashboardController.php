<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DisputeStatus;
use App\Enums\ProductStatus;
use App\Enums\SellerStatus;
use App\Http\Controllers\Controller;
use App\Models\JobPaymentDispute;
use App\Models\Product;
use App\Models\ReviewReport;
use App\Models\SellerProfile;
use App\Models\StaffActivity;
use App\Support\Navigation\StaffMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** The staff portal's home: what is waiting in each queue the signed-in staff member may work, and what happened lately. */
class AdminDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $staff = $request->user();
        $queues = [];

        if ($staff->can('review models')) {
            $queues[] = $this->queue('Model reviews', 'clipboard-check', StaffMenu::count('models'), Product::where('status', ProductStatus::InReview)->min('submitted_at'),
                route('admin.models.index'), 'models are waiting for a decision', 'No models are waiting for review.');
        }

        if ($staff->can('review sellers')) {
            $queues[] = $this->queue('Seller applications', 'clipboard-list', StaffMenu::count('sellers'), SellerProfile::where('status', SellerStatus::Pending)->min('submitted_at'),
                route('admin.sellers.index'), 'applications are waiting for a decision', 'No seller applications are waiting.');
        }

        if ($staff->can('moderate reviews')) {
            $queues[] = $this->queue('Review reports', 'flag', StaffMenu::count('reports'), ReviewReport::where('status', 'open')->min('created_at'),
                route('admin.reviews.index'), 'reviews have open reports', 'No reviews are reported.');
        }

        if ($staff->can('view disputes')) {
            $queues[] = $this->queue('Payment disputes', 'scale', StaffMenu::count('disputes'), JobPaymentDispute::whereIn('status', [DisputeStatus::Pending, DisputeStatus::UnderReview])->min('created_at'),
                route('admin.disputes.index'), 'disputes are open', 'No disputes are open.');
        }

        return view('admin.dashboard', [
            'queues' => $queues,
            'mine' => $staff->can('resolve disputes')
                ? JobPaymentDispute::where('admin_assigned', $staff->id)->where('status', DisputeStatus::UnderReview)
                    ->with(['cancellation.engagement.application.job:id,title'])->latest()->limit(5)->get()
                : collect(),
            'activity' => $staff->can('view audit log') ? StaffActivity::with('staff:id,name,avatar')->latest('id')->limit(8)->get() : collect(),
            'notifications' => $staff->notifications()->limit(5)->get(),
        ]);
    }

    /** @return array{label: string, icon: string, count: int, oldest: ?Carbon, url: string, what: string, empty: string} */
    private function queue(string $label, string $icon, int $count, mixed $oldest, string $url, string $what, string $empty): array
    {
        return ['label' => $label, 'icon' => $icon, 'count' => $count, 'oldest' => $oldest ? Carbon::parse($oldest) : null, 'url' => $url, 'what' => $what, 'empty' => $empty];
    }
}
