<?php

namespace App\Services\Marketplace;

use App\Enums\LicenceTier;
use App\Models\IssuedLicence;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Licences: what a model costs under each tier, and issuing the licence a buyer holds. Checkout calls grant() once the money has
 * arrived (or at once for a free model), so a licence only ever exists for a completed purchase.
 */
class LicenceService
{
    /** No 0, O, 1, I or L: a key may be read out or typed from a printout. */
    private const KEY_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * Sell a licence on a model to a buyer: record the purchase at today's price and issue the licence, together. Returns the licence,
     * or the reason it cannot be sold.
     */
    public function grant(User $buyer, Product $product, LicenceTier $tier): IssuedLicence|string
    {
        if ($error = $this->cannotBuy($buyer, $product, $tier)) {
            return $error;
        }

        return DB::transaction(function () use ($buyer, $product, $tier) {
            $price = $product->priceFor($tier);

            $purchase = Purchase::create([
                'user_id' => $buyer->id, 'product_id' => $product->id, 'tier' => $tier, 'price_minor' => $price,
                'currency' => $product->currency, 'status' => 'completed', 'purchased_at' => now(),
            ]);

            return IssuedLicence::create([
                'key' => $this->newKey(), 'purchase_id' => $purchase->id, 'user_id' => $buyer->id, 'product_id' => $product->id, 'tier' => $tier,
                'terms_version' => LicenceTier::TERMS_VERSION, 'terms' => $tier->terms(), 'licensee_name' => $buyer->name, 'product_title' => $product->title,
                'seller_name' => $product->sellerProfile?->display_name ?? $product->seller?->name ?? '', 'price_minor' => $price,
                'currency' => $product->currency, 'issued_at' => now(),
            ]);
        });
    }

    /** Why this buyer cannot buy this licence right now, or null when they can. */
    public function cannotBuy(User $buyer, Product $product, LicenceTier $tier): ?string
    {
        if (! Product::published()->whereKey($product->id)->exists()) {
            return 'This model is not for sale.';
        }

        if ($product->user_id === $buyer->id) {
            return 'You cannot buy your own model.';
        }

        if ($product->priceFor($tier) === null) {
            return "This model is not sold with an {$tier->label()} licence.";
        }

        $held = $buyer->issuedLicences()->active()->where('product_id', $product->id)->pluck('tier');

        if ($held->contains($tier)) {
            return "You already hold the {$tier->label()} licence for this model.";
        }

        if ($tier === LicenceTier::Standard && $held->contains(LicenceTier::Extended)) {
            return 'Your Extended licence for this model already covers everything the Standard one does.';
        }

        return null;
    }

    /** End a licence (a refunded purchase), keeping the record and saying why. */
    public function revoke(IssuedLicence $licence, string $reason): void
    {
        if (! $licence->isActive()) {
            return;
        }

        DB::transaction(function () use ($licence, $reason) {
            $licence->update(['revoked_at' => now(), 'revoked_reason' => trim($reason)]);
            $licence->purchase()->update(['status' => 'refunded']);
        });
    }

    private function newKey(): string
    {
        do {
            $key = 'LIC-'.collect(range(1, 3))->map(fn () => collect(range(1, 4))->map(fn () => self::KEY_ALPHABET[random_int(0, strlen(self::KEY_ALPHABET) - 1)])->implode(''))->implode('-');
        } while (IssuedLicence::where('key', $key)->exists());

        return $key;
    }
}
