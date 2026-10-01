<?php

namespace App\Services\Marketplace;

use App\Models\SellerProfile;
use App\Models\User;
use App\Notifications\StoreNameChangedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** An approved seller keeping their storefront up to date: name, tagline, text, website and logo. */
class SellerStoreService
{
    public function update(SellerProfile $store, array $data, ?UploadedFile $logo, bool $removeLogo): SellerProfile
    {
        $oldName = $store->display_name;
        $newName = trim($data['display_name']);
        $renamed = $newName !== $oldName;

        $store->fill([
            'display_name' => $newName,
            'tagline' => filled($data['tagline'] ?? null) ? trim($data['tagline']) : null,
            'bio' => $data['bio'],
            'focus' => $data['focus'],
            'website_url' => filled($data['website_url'] ?? null) ? trim($data['website_url']) : null,
        ]);

        // The storefront address (slug) is never changed, so links to the store keep working.
        if ($renamed) {
            $store->name_changed_at = now();
        }

        if ($logo) {
            $this->deleteLogo($store);
            $disk = config('marketplace.images_disk');
            $store->logo_path = $logo->storeAs("seller-logos/{$store->id}", Str::uuid().'.'.strtolower($logo->getClientOriginalExtension()), $disk);
        } elseif ($removeLogo) {
            $this->deleteLogo($store);
            $store->logo_path = null;
        }

        $store->save();

        if ($renamed) {
            User::permission('review sellers')->get()->each->notify(new StoreNameChangedNotification($store, $oldName));
        }

        return $store;
    }

    /** A reviewer removes an unsuitable logo; the seller can upload another. */
    public function removeLogo(SellerProfile $store): void
    {
        $this->deleteLogo($store);
        $store->update(['logo_path' => null]);
    }

    private function deleteLogo(SellerProfile $store): void
    {
        if ($store->logo_path) {
            Storage::disk(config('marketplace.images_disk'))->delete($store->logo_path);
        }
    }
}
