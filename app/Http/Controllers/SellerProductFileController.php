<?php

namespace App\Http\Controllers;

use App\Http\Requests\Marketplace\UploadProductFileRequest;
use App\Http\Requests\Marketplace\UploadProductImageRequest;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\ProductImage;
use App\Services\Marketplace\ProductService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/** Uploading and removing a listing's model files and preview images (JSON, one file per request) and downloading its files. */
class SellerProductFileController extends Controller
{
    public function __construct(protected ProductService $products) {}

    public function storeFile(UploadProductFileRequest $request, Product $product)
    {
        $file = $this->products->addFile($product, $request->file('file'));

        if (is_string($file)) {
            return response()->json(['message' => $file, 'errors' => ['file' => [$file]]], 422);
        }

        return response()->json($this->filePayload($product, $file), 201);
    }

    public function destroyFile(Product $product, ProductFile $file)
    {
        Gate::authorize('edit', $product);
        abort_unless($file->product_id === $product->id, 404);

        $this->products->removeFile($file);

        return response()->json(['deleted' => true]);
    }

    /** The owner (or a reviewer) downloads a stored file. Private disk: never a public URL. */
    public function downloadFile(Product $product, ProductFile $file)
    {
        abort_unless($file->product_id === $product->id, 404);
        Gate::authorize('manage', $product);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    public function storeImage(UploadProductImageRequest $request, Product $product)
    {
        $image = $this->products->addImage($product, $request->file('image'));

        if (is_string($image)) {
            return response()->json(['message' => $image, 'errors' => ['image' => [$image]]], 422);
        }

        return response()->json($this->imagePayload($product, $image), 201);
    }

    public function destroyImage(Product $product, ProductImage $image)
    {
        Gate::authorize('edit', $product);
        abort_unless($image->product_id === $product->id, 404);

        $this->products->removeImage($image);

        return response()->json(['deleted' => true]);
    }

    public function coverImage(Product $product, ProductImage $image)
    {
        Gate::authorize('edit', $product);
        abort_unless($image->product_id === $product->id, 404);

        $this->products->makeCover($image);

        return response()->json(['cover' => true]);
    }

    public function filePayload(Product $product, ProductFile $file): array
    {
        return [
            'id' => $file->id,
            'name' => $file->original_name,
            'kind' => $file->kind,
            'size' => $file->readableSize(),
            'delete_url' => route('seller.models.files.destroy', [$product, $file]),
        ];
    }

    public function imagePayload(Product $product, ProductImage $image): array
    {
        return [
            'id' => $image->id,
            'url' => $image->url(),
            'delete_url' => route('seller.models.images.destroy', [$product, $image]),
            'cover_url' => route('seller.models.images.cover', [$product, $image]),
        ];
    }
}
