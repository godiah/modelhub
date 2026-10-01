<?php

namespace App\Services\Marketplace;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\ProductImage;
use App\Models\User;
use App\Notifications\ProductSubmittedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** A seller's work on their model listings: drafts, details, files, preview images, and sending for review. */
class ProductService
{
    public function createDraft(User $seller, array $data): Product
    {
        return $seller->products()->create([
            'title' => trim($data['title']),
            'category_id' => $data['category_id'],
            'price_minor' => $data['price_minor'],
            'currency' => config('marketplace.currency'),
            'status' => ProductStatus::Draft,
        ]);
    }

    /** @param array<string,mixed> $data validated details; `tags` as a list, `software` as ids */
    public function updateDetails(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $features = collect(array_keys(Product::FEATURES))->mapWithKeys(fn ($field) => [$field => (bool) ($data[$field] ?? false)])->all();

            $product->update(array_merge([
                'title' => trim($data['title']),
                'category_id' => $data['category_id'],
                'description' => $data['description'] ?? null,
                'tags' => $data['tags'] ?? [],
                'price_minor' => $data['price_minor'],
                'geometry_type' => $data['geometry_type'] ?? null,
                'polygons' => $data['polygons'] ?? null,
                'vertices' => $data['vertices'] ?? null,
                'uv_layout' => $data['uv_layout'] ?? null,
                'render_engine' => filled($data['render_engine'] ?? null) ? trim($data['render_engine']) : null,
            ], $features));

            $product->software()->sync($data['software'] ?? []);

            return $product;
        });
    }

    /** Store an uploaded model file privately under a random name; the original name is kept only as a label. */
    public function addFile(Product $product, UploadedFile $upload): ProductFile|string
    {
        if ($product->files()->count() >= config('marketplace.max_files')) {
            return 'A listing can have up to '.config('marketplace.max_files').' files.';
        }

        $extension = strtolower($upload->getClientOriginalExtension());
        $kind = $this->kindFor($extension);

        if (! $kind) {
            return 'That file type is not accepted.';
        }

        $disk = config('marketplace.files_disk');
        $path = $upload->storeAs("product-files/{$product->id}", Str::uuid().'.'.$extension, $disk);

        return $product->files()->create([
            'disk' => $disk,
            'path' => $path,
            'original_name' => Str::limit(basename($upload->getClientOriginalName()), 200, ''),
            'extension' => $extension,
            'kind' => $kind,
            'size_bytes' => $upload->getSize(),
            'checksum' => hash_file('sha256', $upload->getRealPath()),
        ]);
    }

    public function removeFile(ProductFile $file): void
    {
        $file->deleteFromStorage();
        $file->delete();
    }

    public function addImage(Product $product, UploadedFile $upload): ProductImage|string
    {
        if ($product->images()->count() >= config('marketplace.max_images')) {
            return 'A listing can have up to '.config('marketplace.max_images').' preview images.';
        }

        $disk = config('marketplace.images_disk');
        $path = $upload->storeAs("product-images/{$product->id}", Str::uuid().'.'.strtolower($upload->getClientOriginalExtension()), $disk);

        return $product->images()->create([
            'disk' => $disk,
            'path' => $path,
            'position' => (int) $product->images()->max('position') + 1,
        ]);
    }

    public function removeImage(ProductImage $image): void
    {
        $image->deleteFromStorage();
        $image->delete();
    }

    /** The first image is the cover: moving one to the front makes it the cover. */
    public function makeCover(ProductImage $image): void
    {
        DB::transaction(function () use ($image) {
            $first = (int) $image->product->images()->min('position');
            $image->update(['position' => $first - 1]);
        });
    }

    /** Send a listing for review. Returns what is missing or not allowed, or null once submitted. */
    public function submit(Product $product): ?array
    {
        if (! $product->status->isEditable()) {
            return ['This listing cannot be submitted right now.'];
        }

        if ($problems = $product->readinessProblems()) {
            return $problems;
        }

        $product->update([
            'status' => ProductStatus::InReview,
            'submitted_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_notes' => null,
        ]);

        User::permission('review models')->get()->each->notify(new ProductSubmittedNotification($product->loadMissing('seller')));

        return null;
    }

    /** Take a published (or waiting) listing back to an editable state. */
    public function unpublish(Product $product): bool
    {
        if (! in_array($product->status, [ProductStatus::Published, ProductStatus::InReview], true)) {
            return false;
        }

        $product->update(['status' => ProductStatus::Unpublished]);

        return true;
    }

    /** Remove a listing and its stored files. Only unpublished work can go; a live listing is unpublished first. */
    public function delete(Product $product): bool
    {
        if (! $product->status->isEditable()) {
            return false;
        }

        DB::transaction(function () use ($product) {
            $product->files->each->deleteFromStorage();
            $product->images->each->deleteFromStorage();
            $product->files()->delete();
            $product->images()->delete();
            $product->delete();
        });

        return true;
    }

    /** What a file is for, from its extension: native, exchange, texture, archive or document. */
    public function kindFor(string $extension): ?string
    {
        foreach (config('marketplace.formats') as $kind => $extensions) {
            if (in_array($extension, $extensions, true)) {
                return $kind;
            }
        }

        return null;
    }
}
