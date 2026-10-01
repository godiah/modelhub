<?php

namespace App\Http\Controllers;

use App\Helpers\FlashAlertHelper;
use App\Http\Requests\Marketplace\ApplyAsSellerRequest;
use App\Models\SellerProfile;
use App\Services\Marketplace\SellerOnboardingService;

/** A member's side of becoming a seller: see where they stand, apply, or apply again after a rejection. */
class SellerController extends Controller
{
    public function __construct(protected SellerOnboardingService $onboarding) {}

    public function index()
    {
        return view('marketplace.sell.index', ['profile' => SellerProfile::where('user_id', auth()->id())->first()]);
    }

    public function apply(ApplyAsSellerRequest $request)
    {
        if ($error = $this->onboarding->apply($request->user(), $request->validated())) {
            return redirect()->route('seller.index')->with(FlashAlertHelper::error('Cannot apply', $error));
        }

        return redirect()->route('seller.index')->with(
            FlashAlertHelper::success('Application sent', 'We will review it and let you know by email and in your notifications.')
        );
    }
}
