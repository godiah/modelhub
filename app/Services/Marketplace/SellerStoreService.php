<?php

namespace App\Services\Marketplace;

use App\Models\SellerProfile;
use App\Models\Staff;
use App\Notifications\StoreNameChangedNotification;

/** An approved seller keeping their storefront up to date: name, tagline, text, website and avatar. */
class SellerStoreService
{
    public function update(SellerProfile $store, array $data): SellerProfile
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

        if (filled($data['avatar'] ?? null)) {
            $store->avatar = $data['avatar'];
        }

        $store->save();

        if ($renamed) {
            Staff::permission('review sellers')->where('is_active', true)->get()->each->notify(new StoreNameChangedNotification($store, $oldName));
        }

        return $store;
    }
}
