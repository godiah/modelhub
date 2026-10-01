<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\FlashAlertHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\ModerateReviewRequest;
use App\Models\ProductReview;
use App\Services\Marketplace\ProductRatingService;
use App\Support\Staff\StaffAudit;
use Illuminate\Http\Request;

/** Moderation of model reviews: reported and hidden ones. Permission: moderate reviews (see routes/web.php). */
class AdminReviewController extends Controller
{
    public function __construct(protected ProductRatingService $ratings) {}

    public function index(Request $request)
    {
        $status = in_array($request->query('status'), ['reported', 'hidden', 'all'], true) ? $request->query('status') : 'reported';

        $reviews = ProductReview::with(['author', 'product.sellerProfile', 'hiddenBy', 'reports.reporter'])
            ->withCount(['reports as open_reports_count' => fn ($query) => $query->where('status', 'open')])
            ->when($status === 'reported', fn ($query) => $query->visible()->whereHas('reports', fn ($reports) => $reports->where('status', 'open')))
            ->when($status === 'hidden', fn ($query) => $query->where('status', 'hidden'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.reviews.index', [
            'reviews' => $reviews,
            'status' => $status,
            'counts' => [
                'reported' => ProductReview::visible()->whereHas('reports', fn ($reports) => $reports->where('status', 'open'))->count(),
                'hidden' => ProductReview::where('status', 'hidden')->count(),
                'all' => ProductReview::count(),
            ],
        ]);
    }

    public function hide(ModerateReviewRequest $request, ProductReview $review)
    {
        if ($error = $this->ratings->hide($review, $request->user(), $request->input('reason'))) {
            return back()->with(FlashAlertHelper::error('Cannot hide this review', $error));
        }

        StaffAudit::log('review.hidden', 'Hid a review of "'.$review->product->title.'"', $review, ['reason' => $request->input('reason')]);

        return back()->with(FlashAlertHelper::success('Review hidden', 'It no longer counts towards the rating, and the author has been told.'));
    }

    public function restore(ProductReview $review)
    {
        if ($error = $this->ratings->restore($review)) {
            return back()->with(FlashAlertHelper::error('Cannot restore this review', $error));
        }

        StaffAudit::log('review.restored', 'Restored a review of "'.$review->product->title.'"', $review);

        return back()->with(FlashAlertHelper::success('Review restored'));
    }

    public function dismiss(Request $request, ProductReview $review)
    {
        $this->ratings->dismissReports($review, $request->user());
        StaffAudit::log('review.reports-dismissed', 'Dismissed the reports on a review of "'.$review->product->title.'"', $review);

        return back()->with(FlashAlertHelper::success('Reports dismissed', 'The review stays visible.'));
    }

    public function removeReply(ProductReview $review)
    {
        $this->ratings->removeReply($review);
        StaffAudit::log('review.reply-removed', 'Removed a seller reply on "'.$review->product->title.'"', $review);

        return back()->with(FlashAlertHelper::success('Seller reply removed'));
    }
}
