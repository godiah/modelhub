<?php

namespace App\Http\Controllers;

use App\Helpers\FlashAlertHelper;
use App\Http\Requests\Marketplace\ProductReviewRequest;
use App\Http\Requests\Marketplace\ReportReviewRequest;
use App\Http\Requests\Marketplace\ReviewReplyRequest;
use App\Models\Product;
use App\Models\ProductReview;
use App\Services\Marketplace\ProductRatingService;

/** Reviews on a model page: a buyer writes, edits and deletes their own; the seller replies; anyone signed in can report. */
class ProductReviewController extends Controller
{
    public function __construct(protected ProductRatingService $ratings) {}

    public function store(ProductReviewRequest $request, Product $product)
    {
        abort_unless(Product::published()->whereKey($product->id)->exists(), 404);

        $result = $this->ratings->review($product, $request->user(), (int) $request->input('rating'), $request->input('comment'));

        if (is_string($result)) {
            return back()->with(FlashAlertHelper::error('Cannot review this model', $result));
        }

        return redirect()->to(route('models.show', $product).'#reviews')->with(FlashAlertHelper::success('Review posted', 'Thank you, it helps other buyers.'));
    }

    public function update(ProductReviewRequest $request, ProductReview $review)
    {
        abort_unless($review->user_id === $request->user()->id, 403);

        $this->ratings->update($review, (int) $request->input('rating'), $request->input('comment'));

        return redirect()->to(route('models.show', $review->product).'#reviews')->with(FlashAlertHelper::success('Review updated'));
    }

    public function destroy(ProductReview $review)
    {
        abort_unless($review->user_id === request()->user()->id, 403);

        $product = $review->product;
        $this->ratings->delete($review);

        return redirect()->to(route('models.show', $product).'#reviews')->with(FlashAlertHelper::success('Review deleted'));
    }

    public function reply(ReviewReplyRequest $request, ProductReview $review)
    {
        if ($error = $this->ratings->reply($review, $request->user(), $request->input('reply'))) {
            return back()->with(FlashAlertHelper::error('Cannot reply', $error));
        }

        return redirect()->to(route('models.show', $review->product).'#review-'.$review->id)->with(FlashAlertHelper::success('Reply posted'));
    }

    public function destroyReply(ProductReview $review)
    {
        abort_unless($review->product->user_id === request()->user()->id, 403);

        $this->ratings->removeReply($review);

        return redirect()->to(route('models.show', $review->product).'#review-'.$review->id)->with(FlashAlertHelper::success('Reply removed'));
    }

    public function report(ReportReviewRequest $request, ProductReview $review)
    {
        if ($error = $this->ratings->report($review, $request->user(), $request->input('reason'), $request->input('details'))) {
            return back()->with(FlashAlertHelper::error('Cannot report this review', $error));
        }

        return back()->with(FlashAlertHelper::success('Report sent', 'A reviewer will take a look.'));
    }
}
