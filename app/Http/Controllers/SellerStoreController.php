<?php

namespace App\Http\Controllers;

use App\Helpers\FlashAlertHelper;
use App\Http\Requests\Marketplace\UpdateStoreRequest;
use App\Models\SellerProfile;
use App\Services\Marketplace\SellerStoreService;

/** An approved seller's store settings: the details and avatar buyers see on their storefront. */
class SellerStoreController extends Controller
{
    public function __construct(protected SellerStoreService $stores) {}

    public function edit()
    {
        return view('marketplace.sell.store', ['store' => SellerProfile::where('user_id', auth()->id())->firstOrFail()]);
    }

    public function update(UpdateStoreRequest $request)
    {
        $this->stores->update($request->store(), $request->validated());

        return redirect()->route('seller.store.edit')->with(FlashAlertHelper::success('Store updated', 'Your storefront now shows the new details.'));
    }
}
