<?php

namespace App\Services\Marketplace;

use App\Enums\LicenceTier;
use App\Enums\ProductStatus;
use App\Models\IssuedLicence;
use App\Models\LicenceDownload;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A member's licences as a library: searched, filtered by kind or state and sorted, with the figures that go with it (how many are live, what
 * they cost, how often their files were taken) and, for each licence, what the member might want to do next: download, rate the model, or upgrade.
 */
class LicenceLibrary
{
    public const TABS = ['all' => 'All', 'active' => 'Active', 'ended' => 'Ended', 'standard' => 'Standard', 'extended' => 'Extended'];

    public const SORTS = ['newest' => 'Newest first', 'oldest' => 'Oldest first', 'title' => 'Title, A to Z', 'price' => 'Price, high to low'];

    public static function tab(?string $key): string
    {
        return array_key_exists((string) $key, self::TABS) ? $key : 'all';
    }

    public static function sort(?string $key): string
    {
        return array_key_exists((string) $key, self::SORTS) ? $key : 'newest';
    }

    /** One page of the library, each licence carrying `library` (downloads, last download, can rate, upgrade price). */
    public function page(User $user, string $tab, string $term, string $sort, int $perPage = 12): LengthAwarePaginator
    {
        $page = $this->query($user, $tab, $term)
            ->with(['product' => fn ($product) => $product->select('id', 'user_id', 'slug', 'title', 'status', 'extended_price_minor', 'deleted_at'), 'product.images', 'product.files:id,product_id,extension,kind,size_bytes'])
            ->tap(fn ($query) => match ($sort) {
                'oldest' => $query->oldest('issued_at')->oldest('id'),
                'title' => $query->orderBy('product_title')->latest('id'),
                'price' => $query->orderByDesc('price_minor')->latest('id'),
                default => $query->latest('issued_at')->latest('id'),
            })
            ->paginate($perPage)->withQueryString();

        $this->decorate($user, $page->getCollection());

        return $page;
    }

    /** @return array<string, int> licences per tab, whatever the search says, for the tab badges */
    public function counts(User $user): array
    {
        $licences = IssuedLicence::where('user_id', $user->id);

        return [
            'all' => (clone $licences)->count(),
            'active' => (clone $licences)->active()->count(),
            'ended' => (clone $licences)->whereNotNull('revoked_at')->count(),
            'standard' => (clone $licences)->where('tier', LicenceTier::Standard->value)->count(),
            'extended' => (clone $licences)->where('tier', LicenceTier::Extended->value)->count(),
        ];
    }

    /** @return array{active: int, extended: int, spent: int, downloads: int} what the member holds now, what they paid for it, and how often they took the files */
    public function stats(User $user): array
    {
        $active = IssuedLicence::where('user_id', $user->id)->active();

        return [
            'active' => (clone $active)->count(),
            'extended' => (clone $active)->where('tier', LicenceTier::Extended->value)->count(),
            'spent' => (int) (clone $active)->sum('price_minor'),
            'downloads' => LicenceDownload::whereIn('issued_licence_id', IssuedLicence::where('user_id', $user->id)->select('id'))->count(),
        ];
    }

    private function query(User $user, string $tab, string $term)
    {
        return IssuedLicence::where('user_id', $user->id)
            ->when($tab === 'active', fn ($q) => $q->active())
            ->when($tab === 'ended', fn ($q) => $q->whereNotNull('revoked_at'))
            ->when(in_array($tab, ['standard', 'extended'], true), fn ($q) => $q->where('tier', $tab))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w->where('product_title', 'like', "%{$term}%")->orWhere('seller_name', 'like', "%{$term}%")->orWhere('key', 'like', "%{$term}%")));
    }

    /** Give each licence its `library` extras (see page()). The licences' products must be loaded with their images and files. @param Collection<int, IssuedLicence> $licences */
    public function decorate(User $user, Collection $licences): void
    {
        $downloads = LicenceDownload::selectRaw('issued_licence_id, count(*) as total, max(created_at) as last_at')->whereIn('issued_licence_id', $licences->pluck('id'))->groupBy('issued_licence_id')->get()->keyBy('issued_licence_id');
        $reviewed = ProductReview::where('user_id', $user->id)->whereIn('product_id', $licences->pluck('product_id'))->pluck('product_id')->flip();
        $holdsExtended = IssuedLicence::where('user_id', $user->id)->active()->where('tier', LicenceTier::Extended->value)->pluck('product_id')->flip();

        foreach ($licences as $licence) {
            $product = $licence->product;
            $live = $product && ! $product->trashed() && $product->status === ProductStatus::Published;
            $took = $downloads->get($licence->id);

            $licence->setAttribute('library', (object) [
                'downloads' => (int) ($took->total ?? 0),
                'last_download' => $took?->last_at ? Carbon::parse($took->last_at) : null,
                'can_rate' => $licence->isActive() && $live && $product->user_id !== $user->id && ! $reviewed->has($licence->product_id),
                'upgrade_minor' => $licence->isActive() && $licence->tier === LicenceTier::Standard && $live && $product->extended_price_minor && ! $holdsExtended->has($licence->product_id) ? $product->extended_price_minor : null,
                'live' => $live,
                'cover' => $product?->images->first(),
                'formats' => $product ? $product->files->whereIn('kind', ['native', 'exchange'])->pluck('extension')->unique()->values() : collect(),
                'file_count' => $product?->files->count() ?? 0,
                'file_bytes' => (int) ($product?->files->sum('size_bytes') ?? 0),
            ]);
        }
    }
}
