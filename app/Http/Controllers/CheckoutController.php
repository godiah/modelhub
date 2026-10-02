<?php

namespace App\Http\Controllers;

use App\Enums\LicenceTier;
use App\Helpers\FlashAlertHelper;
use App\Models\Product;
use App\Services\Marketplace\LicenceService;
use App\Services\Payments\PaymentService;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Buying a model: pay by M-Pesa (a prompt goes to the buyer's phone), or take a free model's licence at once. */
class CheckoutController extends Controller
{
    public function __construct(protected PaymentService $payments, protected LicenceService $licences) {}

    public function start(Request $request, Product $product)
    {
        abort_unless(config('marketplace.purchases_enabled'), 404);

        $data = $request->validate([
            'tier' => ['required', Rule::enum(LicenceTier::class)],
            'phone' => ['required', 'string', 'max:20', fn ($attribute, $value, $fail) => Phone::msisdn($value) ?: $fail('Enter a valid Safaricom number, for example 0712 345 678.')],
        ]);

        $result = $this->payments->start($request->user(), $product, LicenceTier::from($data['tier']), $data['phone']);

        if (is_string($result)) {
            return back()->withInput()->with(FlashAlertHelper::error('Cannot start the payment', $result));
        }

        return redirect()->route('payments.show', $result);
    }

    public function free(Request $request, Product $product)
    {
        abort_unless(config('marketplace.purchases_enabled'), 404);

        // Only a licence that really costs nothing can be taken this way: never a way round paying
        abort_unless($product->priceFor(LicenceTier::Standard) === 0, 404);

        $licence = $this->licences->grant($request->user(), $product, LicenceTier::Standard);

        if (is_string($licence)) {
            return back()->with(FlashAlertHelper::error('Cannot get this model', $licence));
        }

        return redirect()->route('licences.show', $licence)->with(FlashAlertHelper::success('Your licence is ready', 'Download the files below.'));
    }
}
