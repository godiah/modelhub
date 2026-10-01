<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Helpers\FlashAlertHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\ReviewSellerRequest;
use App\Models\Product;
use App\Models\ProductFile;
use App\Services\Marketplace\ProductReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** The review queue for model listings. Permission: review models (see routes/web.php). */
class AdminProductController extends Controller
{
    public function __construct(protected ProductReviewService $reviews) {}

    public function index(Request $request)
    {
        $status = in_array($request->query('status'), array_merge(['all'], array_column(ProductStatus::cases(), 'value')), true)
            ? $request->query('status')
            : ProductStatus::InReview->value;

        $products = Product::with(['seller.sellerProfile', 'reviewer:id,name', 'category.parent', 'images', 'files', 'software'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByRaw("case status when 'in_review' then 0 else 1 end")
            ->latest('submitted_at')
            ->paginate(8)
            ->withQueryString();

        return view('admin.models.index', [
            'products' => $products,
            'status' => $status,
            'counts' => $this->reviews->counts(),
        ]);
    }

    public function review(ReviewSellerRequest $request, Product $product, string $decision)
    {
        if ($error = $this->reviews->review($product, $request->user(), $decision, $request->input('notes'))) {
            return back()->with(FlashAlertHelper::error('Cannot do that', $error));
        }

        return back()->with(FlashAlertHelper::success(match ($decision) {
            'publish' => 'Model published',
            'reject' => 'Changes requested',
            default => 'Model taken down',
        }, 'The seller has been told.'));
    }

    /** Reviewers open the seller's private files to check them. */
    public function download(Product $product, ProductFile $file)
    {
        abort_unless($file->product_id === $product->id, 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }
}
