<?php

use App\Contracts\PaymentGateway;
use App\Enums\GatewayState;
use App\Enums\LicenceTier;
use App\Enums\PaymentStatus;
use App\Models\IssuedLicence;
use App\Models\LedgerTransaction;
use App\Models\LicenceDownload;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\SellerProfile;
use App\Models\User;
use App\Notifications\ModelPurchasedNotification;
use App\Notifications\ModelSoldNotification;
use App\Services\Ledger\LedgerService;
use App\Services\Marketplace\LicenceService;
use App\Services\Payments\FakeGateway;
use App\Services\Payments\PaymentService;
use App\Support\Payments\PaymentOutcome;
use App\Support\Phone;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/*
 * Checkout: paying for a model by M-Pesa (on the fake gateway), from the prompt to the licence and the ledger, and downloading what was bought.
 * The last digit of the phone number steers the fake gateway: 1 insufficient funds, 2 cancelled, 3 times out, 0 refused, anything else pays.
 */

beforeEach(function () {
    config(['marketplace.purchases_enabled' => true, 'payments.fake.delay_seconds' => 0]);
    Notification::fake();
    Storage::fake('local');

    $this->buyer = User::factory()->create(['name' => 'Buyer Person']);
    $this->seller = User::factory()->create(['name' => 'Seller Person']);
    $this->store = SellerProfile::factory()->approved()->create(['user_id' => $this->seller->id, 'display_name' => 'Oak Studio']);
    $this->product = Product::factory()->published()->create(['user_id' => $this->seller->id, 'title' => 'Oak armchair', 'price_minor' => 120000, 'extended_price_minor' => 480000]);
    $this->ledger = app(LedgerService::class);
});

function buy(string $phone = '0712345678', string $tier = 'standard', ?Product $product = null)
{
    $product ??= test()->product;

    return test()->actingAs(test()->buyer)->post(route('checkout.start', $product), ['tier' => $tier, 'phone' => $phone]);
}

/** Start a payment and return it (the fake gateway answers the way the phone's last digit says). */
function startPayment(string $phone = '0712345678', string $tier = 'standard'): Payment
{
    $result = app(PaymentService::class)->start(test()->buyer, test()->product, LicenceTier::from($tier), $phone);
    expect($result)->toBeInstanceOf(Payment::class);

    return $result;
}

/** The ledger balance of one of the platform's accounts. */
function platformBalance(string $key): int
{
    return app(LedgerService::class)->platformAccount($key)->balanceMinor();
}

/** ---------------------------------------------------------------- phone numbers */
it('reads Kenyan mobile numbers in every common form', function () {
    foreach (['0712345678', '0712 345 678', '+254712345678', '254712345678', '712345678', '0712-345-678', '(0712) 345.678'] as $input) {
        expect(Phone::msisdn($input))->toBe('254712345678');
    }
    expect(Phone::msisdn('0112345678'))->toBe('254112345678')->and(Phone::msisdn('0212345678'))->toBeNull()->and(Phone::msisdn('071234567'))->toBeNull()->and(Phone::msisdn('abc'))->toBeNull()->and(Phone::msisdn(null))->toBeNull()
        ->and(Phone::local('254712345678'))->toBe('0712 345 678');
});

/** ---------------------------------------------------------------- starting a payment */
it('sends the M-Pesa prompt and takes the commission and hold from the rules in force at that moment', function () {
    config(['payments.fake.delay_seconds' => 60]); // the buyer has not answered yet
    buy('0712345675')->assertRedirect();

    $payment = Payment::sole();
    expect($payment->status)->toBe(PaymentStatus::Pending)->and($payment->amount_minor)->toBe(120000)->and($payment->tier)->toBe(LicenceTier::Standard)->and($payment->msisdn)->toBe('254712345675')
        ->and((float) $payment->commission_rate)->toBe(0.15)->and($payment->commission_minor)->toBe(18000)->and($payment->seller_share_minor)->toBe(102000)->and($payment->hold_days)->toBe(7)
        ->and($payment->gateway)->toBe('fake')->and($payment->gateway_reference)->toStartWith('ws_CO_')->and($payment->reference)->toHaveLength(12)->and($payment->expires_at->isFuture())->toBeTrue();
    expect(IssuedLicence::count())->toBe(0)->and(LedgerTransaction::count())->toBe(0);

    $this->get(route('payments.show', $payment))->assertOk()->assertSee('Check your phone')->assertSee('0712 345 675')->assertSee($payment->reference);
});

it('charges the Extended price for an Extended licence', function () {
    buy('0712345675', 'extended');

    expect(Payment::sole())->toMatchArray(['amount_minor' => 480000, 'commission_minor' => 72000, 'seller_share_minor' => 408000]);
});

it('uses a seller\'s own commission rate when they have one', function () {
    $this->store->forceFill(['commission_percent' => 8])->save();

    buy();

    expect(Payment::sole())->toMatchArray(['commission_minor' => 9600, 'seller_share_minor' => 110400]);
});

it('does not send a second prompt when "pay" is pressed twice', function () {
    buy();
    buy();

    expect(Payment::count())->toBe(1);
});

it('refuses what cannot be bought, with the reason, and records nothing', function () {
    $own = $this->actingAs($this->seller)->post(route('checkout.start', $this->product), ['tier' => 'standard', 'phone' => '0712345678']);
    $own->assertSessionHas('error');

    $this->actingAs($this->buyer);
    $draft = Product::factory()->create(['price_minor' => 50000]);
    $standardOnly = Product::factory()->published()->create(['user_id' => $this->seller->id, 'price_minor' => 50000, 'extended_price_minor' => null]);
    $cents = Product::factory()->published()->create(['user_id' => $this->seller->id, 'price_minor' => 125050]);
    $huge = Product::factory()->published()->create(['user_id' => $this->seller->id, 'price_minor' => 20000000]);
    $free = Product::factory()->published()->create(['user_id' => $this->seller->id, 'price_minor' => 0]);

    $reason = fn (Product $product, string $tier = 'standard') => app(PaymentService::class)->start($this->buyer, $product, LicenceTier::from($tier), '0712345678');

    expect($reason($draft))->toBe('This model is not for sale.')->and($reason($standardOnly, 'extended'))->toBe('This model is not sold with an Extended licence.')
        ->and($reason($cents))->toContain('not in whole shillings')->and($reason($huge))->toContain('M-Pesa takes between KES 1 and KES 150,000')->and($reason($free))->toBe('This licence is free, so no payment is needed.');

    app(LicenceService::class)->grant($this->buyer, $this->product, LicenceTier::Standard);
    expect($reason($this->product))->toBe('You already hold the Standard licence for this model.');

    expect(Payment::count())->toBe(0);
});

it('rejects a bad phone number or licence before anything starts', function () {
    $this->actingAs($this->buyer);

    buy('hello')->assertSessionHasErrors('phone');
    buy('0212345678')->assertSessionHasErrors('phone');
    $this->post(route('checkout.start', $this->product), ['tier' => 'platinum', 'phone' => '0712345678'])->assertSessionHasErrors('tier');
    $this->post(route('checkout.start', $this->product), ['phone' => '0712345678'])->assertSessionHasErrors('tier');

    expect(Payment::count())->toBe(0);
});

it('records a prompt the gateway refused as failed, with its reason', function () {
    buy('0712345670')->assertRedirect();

    $payment = Payment::sole();
    expect($payment->status)->toBe(PaymentStatus::Failed)->and($payment->failure_reason)->toBe('The prompt could not be sent to that number.')->and($payment->gateway_reference)->toBeNull();
    $this->get(route('payments.show', $payment))->assertSee('did not go through')->assertSee('Try again');
});

it('is closed while purchases are not open, and behind the sign-in', function () {
    config(['marketplace.purchases_enabled' => false]);
    buy()->assertNotFound();

    // Signed out, both routes send the visitor to sign in
    auth()->logout();
    config(['marketplace.purchases_enabled' => true]);
    $this->post(route('checkout.free', $this->product))->assertRedirect(route('login'));
    $this->post(route('checkout.start', $this->product), ['tier' => 'standard', 'phone' => '0712345678'])->assertRedirect(route('login'));
});

/** ---------------------------------------------------------------- a payment that succeeds */
it('settles a paid payment: licence, ledger, notifications and the hold, once', function () {
    $payment = startPayment('0712345675');
    $service = app(PaymentService::class);

    $settled = $service->refresh($payment);

    expect($settled->status)->toBe(PaymentStatus::Succeeded)->and($settled->receipt)->toStartWith('FAKE')->and($settled->completed_at)->not->toBeNull()->and($settled->purchase_id)->not->toBeNull()
        ->and($settled->release_at->isSameDay(now()->addDays(7)))->toBeTrue()->and($settled->released_at)->toBeNull();

    $licence = IssuedLicence::sole();
    expect($licence->tier)->toBe(LicenceTier::Standard)->and($licence->price_minor)->toBe(120000)->and($licence->user_id)->toBe($this->buyer->id)->and($licence->purchase_id)->toBe($settled->purchase_id);

    // The sale is in the ledger: the gateway holds it all, the seller's share waits in the hold, the platform has its commission
    expect(platformBalance('gateway'))->toBe(120000)->and(platformBalance('revenue'))->toBe(18000)->and($this->ledger->balances($this->seller))->toBe(['pending' => 102000, 'available' => 0])
        ->and($this->ledger->trialBalance()['balanced'])->toBeTrue();

    Notification::assertSentTo($this->buyer, ModelPurchasedNotification::class, fn ($n) => $n->licence->is($licence));
    Notification::assertSentTo($this->seller, ModelSoldNotification::class, fn ($n) => $n->payment->is($settled));

    // Asking again, or the gateway calling back as well, changes nothing
    $service->refresh($settled);
    $service->applyOutcome($settled, new PaymentOutcome($settled->gateway_reference, GatewayState::Succeeded, $settled->receipt, 120000));
    expect(IssuedLicence::count())->toBe(1)->and(LedgerTransaction::count())->toBe(1)->and(platformBalance('gateway'))->toBe(120000);
    Notification::assertSentToTimes($this->buyer, ModelPurchasedNotification::class, 1);
});

it('settles when the waiting page checks the status, and sends the buyer to their licence', function () {
    $payment = startPayment('0712345675');
    $this->actingAs($this->buyer);

    $json = $this->getJson(route('payments.status', $payment))->assertOk()->assertJson(['status' => 'succeeded', 'final' => true])->json();

    expect($json['redirect'])->toBe(route('licences.show', IssuedLicence::sole()));
    $this->get(route('payments.show', $payment))->assertRedirect(route('licences.show', IssuedLicence::sole()));
});

it('keeps a payment pending while the phone has not answered', function () {
    config(['payments.fake.delay_seconds' => 60]);
    $payment = startPayment('0712345675');
    $this->actingAs($this->buyer);

    $this->getJson(route('payments.status', $payment))->assertJson(['status' => 'pending', 'final' => false, 'redirect' => null]);
    expect(IssuedLicence::count())->toBe(0)->and(LedgerTransaction::count())->toBe(0);
});

it('uses the commission and hold fixed when the payment started, not what the settings say later', function () {
    $payment = startPayment('0712345675');

    setting('fees.models_percent', 30);
    setting('fees.sale_hold_days', 30);
    app(PaymentService::class)->refresh($payment);

    expect(platformBalance('revenue'))->toBe(18000)->and($this->ledger->balances($this->seller)['pending'])->toBe(102000)->and($payment->fresh()->release_at->isSameDay(now()->addDays(7)))->toBeTrue();
});

it('charges no commission line when the rate is zero', function () {
    $this->store->forceFill(['commission_percent' => 0])->save();
    $payment = startPayment('0712345675');

    app(PaymentService::class)->refresh($payment);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Succeeded)->and(platformBalance('revenue'))->toBe(0)->and($this->ledger->balances($this->seller)['pending'])->toBe(120000)->and($this->ledger->trialBalance()['balanced'])->toBeTrue();
});

/** ---------------------------------------------------------------- a payment that does not */
it('ends a declined, cancelled or unanswered payment without a licence or any money in the ledger', function (string $phone, PaymentStatus $status, string $reason) {
    $payment = startPayment($phone);

    $payment = app(PaymentService::class)->refresh($payment);

    expect($payment->status)->toBe($status)->and($payment->failure_reason)->toBe($reason)->and(IssuedLicence::count())->toBe(0)->and(LedgerTransaction::count())->toBe(0);
    Notification::assertNothingSent();
    $this->actingAs($this->buyer)->get(route('payments.show', $payment))->assertSee('did not go through')->assertSee($reason);
})->with([
    'insufficient funds' => ['0712345671', PaymentStatus::Failed, 'Insufficient funds.'],
    'cancelled on the phone' => ['0712345672', PaymentStatus::Cancelled, 'The payment was cancelled on the phone.'],
    'prompt not answered' => ['0712345673', PaymentStatus::Expired, 'The prompt was not answered in time.'],
]);

it('lets the buyer try again after a failure', function () {
    $first = startPayment('0712345671');
    app(PaymentService::class)->refresh($first);

    $second = startPayment('0712345675');

    expect($second->is($first))->toBeFalse()->and(app(PaymentService::class)->refresh($second)->status)->toBe(PaymentStatus::Succeeded)->and(Payment::count())->toBe(2)->and(IssuedLicence::count())->toBe(1);
});

it('gives up on a prompt nobody answered once its time is up, after one last check', function () {
    config(['payments.fake.delay_seconds' => 3600]);
    $payment = startPayment('0712345675');

    expect(app(PaymentService::class)->refresh($payment)->status)->toBe(PaymentStatus::Pending);

    $this->travel(6)->minutes();
    $this->artisan('payments:expire')->assertSuccessful();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Expired)->and($payment->fresh()->failure_reason)->toBe('The prompt was not answered in time.');
});

it('still counts money that arrives after the prompt had been given up on', function () {
    $payment = startPayment('0712345675');
    $payment->update(['status' => PaymentStatus::Expired, 'failure_reason' => 'The prompt was not answered in time.']);

    app(PaymentService::class)->applyOutcome($payment, new PaymentOutcome($payment->gateway_reference, GatewayState::Succeeded, 'LATE123456', 120000));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Succeeded)->and($payment->fresh()->failure_reason)->toBeNull()->and(IssuedLicence::count())->toBe(1)->and(platformBalance('gateway'))->toBe(120000);
});

it('never undoes a paid payment because a later message says it failed', function () {
    $payment = app(PaymentService::class)->refresh(startPayment('0712345675'));

    app(PaymentService::class)->applyOutcome($payment, new PaymentOutcome($payment->gateway_reference, GatewayState::Failed, reason: 'Late failure message'));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Succeeded)->and(IssuedLicence::count())->toBe(1);
});

/** ---------------------------------------------------------------- money that cannot become a licence */
it('parks a payment of the wrong amount for staff and issues no licence', function () {
    $payment = startPayment('0712345675');

    app(PaymentService::class)->applyOutcome($payment, new PaymentOutcome($payment->gateway_reference, GatewayState::Succeeded, 'ODD0000001', 100000));

    $payment->refresh();
    expect($payment->status)->toBe(PaymentStatus::Review)->and($payment->failure_reason)->toBe('Paid Ksh1,000.00 but Ksh1,200.00 was expected.')->and($payment->receipt)->toBe('ODD0000001')
        ->and(IssuedLicence::count())->toBe(0)->and(platformBalance('gateway'))->toBe(100000)->and(platformBalance('suspense'))->toBe(100000)->and(platformBalance('revenue'))->toBe(0)
        ->and($this->ledger->balances($this->seller))->toBe(['pending' => 0, 'available' => 0])->and($this->ledger->trialBalance()['balanced'])->toBeTrue();
    Notification::assertNothingSent();

    // Arriving again does not park it twice
    app(PaymentService::class)->applyOutcome($payment, new PaymentOutcome($payment->gateway_reference, GatewayState::Succeeded, 'ODD0000001', 100000));
    expect(LedgerTransaction::count())->toBe(1);
    $this->actingAs($this->buyer)->get(route('payments.show', $payment))->assertSee('we need to check it')->assertSee('ODD0000001');
});

it('parks a payment for a licence the buyer got in the meantime instead of selling it twice', function () {
    $payment = startPayment('0712345675');
    app(LicenceService::class)->grant($this->buyer, $this->product, LicenceTier::Standard);

    app(PaymentService::class)->refresh($payment);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Review)->and($payment->fresh()->failure_reason)->toBe('You already hold the Standard licence for this model.')
        ->and(IssuedLicence::count())->toBe(1)->and(platformBalance('suspense'))->toBe(120000);
});

it('still delivers a model that was unpublished while the buyer was paying', function () {
    $payment = startPayment('0712345675');
    $this->product->update(['status' => 'unpublished']);

    app(PaymentService::class)->refresh($payment);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Succeeded)->and(IssuedLicence::sole()->price_minor)->toBe(120000);
});

it('gives the licence the price that was paid, not the price the listing has now', function () {
    $payment = startPayment('0712345675');
    $this->product->update(['price_minor' => 999900]);

    app(PaymentService::class)->refresh($payment);

    expect(IssuedLicence::sole()->price_minor)->toBe(120000);
});

/** ---------------------------------------------------------------- the gateway's callback */
it('settles a payment when the gateway calls back, and always answers with an acknowledgement', function () {
    config(['payments.fake.delay_seconds' => 3600]);
    $payment = startPayment('0712345675');

    $this->postJson(route('webhooks.payments', 'fake'), ['gateway_reference' => $payment->gateway_reference, 'state' => 'succeeded', 'receipt' => 'CB00000001', 'amount_minor' => 120000])
        ->assertOk()->assertExactJson(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Succeeded)->and($payment->fresh()->receipt)->toBe('CB00000001')->and(IssuedLicence::count())->toBe(1);

    // A repeat of the same callback is acknowledged and does nothing more
    $this->postJson(route('webhooks.payments', 'fake'), ['gateway_reference' => $payment->gateway_reference, 'state' => 'succeeded', 'receipt' => 'CB00000001', 'amount_minor' => 120000])->assertOk();
    expect(IssuedLicence::count())->toBe(1)->and(LedgerTransaction::count())->toBe(1);
});

it('settles a declined payment from its callback', function () {
    config(['payments.fake.delay_seconds' => 3600]);
    $payment = startPayment('0712345675');

    $this->postJson(route('webhooks.payments', 'fake'), ['gateway_reference' => $payment->gateway_reference, 'state' => 'cancelled', 'reason' => 'Cancelled by the customer'])->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Cancelled)->and($payment->fresh()->failure_reason)->toBe('Cancelled by the customer')->and(IssuedLicence::count())->toBe(0);
});

it('ignores callbacks it does not understand or cannot match, without acting on them', function () {
    startPayment('0712345675');

    $this->postJson(route('webhooks.payments', 'fake'), ['gateway_reference' => 'ws_CO_UNKNOWN', 'state' => 'succeeded', 'amount_minor' => 120000])->assertOk()->assertJson(['ResultCode' => 0]);
    $this->postJson(route('webhooks.payments', 'fake'), ['nonsense' => true])->assertOk();
    $this->postJson(route('webhooks.payments', 'fake'), ['gateway_reference' => 'x', 'state' => 'exploded'])->assertOk();
    $this->postJson(route('webhooks.payments', 'mpesa'), ['gateway_reference' => 'x', 'state' => 'succeeded'])->assertNotFound();

    expect(IssuedLicence::count())->toBe(0)->and(LedgerTransaction::count())->toBe(0)->and(Payment::sole()->status)->toBe(PaymentStatus::Pending);
});

it('only acts on a callback for the gateway that started the payment', function () {
    $payment = startPayment('0712345675');
    $payment->update(['gateway' => 'mpesa']);

    $this->postJson(route('webhooks.payments', 'fake'), ['gateway_reference' => $payment->gateway_reference, 'state' => 'succeeded', 'amount_minor' => 120000])->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)->and(IssuedLicence::count())->toBe(0);
});

/** ---------------------------------------------------------------- the payment pages */
it('keeps a payment page private to the buyer', function () {
    $payment = startPayment('0712345675');

    $this->get(route('payments.show', $payment))->assertRedirect(route('login'));
    $this->getJson(route('payments.status', $payment))->assertUnauthorized();

    $this->actingAs(User::factory()->create())->get(route('payments.show', $payment))->assertNotFound();
    $this->getJson(route('payments.status', $payment))->assertNotFound();
    $this->actingAs($this->seller)->get(route('payments.show', $payment))->assertNotFound();
});

/** ---------------------------------------------------------------- free models */
it('gives a free licence at once, with no payment and nothing in the ledger', function () {
    $free = Product::factory()->published()->create(['user_id' => $this->seller->id, 'price_minor' => 0, 'extended_price_minor' => null, 'title' => 'Free stool']);

    $this->actingAs($this->buyer)->post(route('checkout.free', $free))->assertRedirect(route('licences.show', IssuedLicence::sole()));

    expect(IssuedLicence::sole())->toMatchArray(['price_minor' => 0])->and(Payment::count())->toBe(0)->and(LedgerTransaction::count())->toBe(0);

    $this->post(route('checkout.free', $free))->assertSessionHas('error');
    expect(IssuedLicence::count())->toBe(1);
});

it('never lets the free route be used to get a paid model', function () {
    $this->actingAs($this->buyer)->post(route('checkout.free', $this->product))->assertNotFound();

    expect(IssuedLicence::count())->toBe(0);
});

/** ---------------------------------------------------------------- the buy box */
it('shows each visitor the right way to buy', function () {
    // A guest is asked to sign in
    $this->get(route('models.show', $this->product))->assertOk()->assertSee('Sign in to buy')->assertDontSee('Pay ');

    // A buyer sees the licences, a phone box and the pay button
    $this->actingAs($this->buyer)->get(route('models.show', $this->product))->assertOk()->assertSee('M-Pesa number')->assertSee('with M-Pesa')->assertSee('Standard')->assertSee('Extended')
        ->assertSee(route('checkout.start', $this->product), false);

    // The seller sees it is theirs
    $this->actingAs($this->seller)->get(route('models.show', $this->product))->assertSee('This is your model.')->assertDontSee('M-Pesa number');

    // Closed while purchases are not open
    config(['marketplace.purchases_enabled' => false]);
    $this->actingAs($this->buyer)->get(route('models.show', $this->product))->assertSee('Purchases open soon')->assertDontSee('M-Pesa number');
});

it('offers a free model as a free licence, and a holder their licence and an Extended upgrade', function () {
    $free = Product::factory()->published()->create(['user_id' => $this->seller->id, 'price_minor' => 0, 'extended_price_minor' => null]);
    $this->actingAs($this->buyer)->get(route('models.show', $free))->assertSee('Get the free licence')->assertDontSee('M-Pesa number');

    app(LicenceService::class)->grant($this->buyer, $this->product, LicenceTier::Standard);
    $this->get(route('models.show', $this->product))->assertSee('You own this model')->assertSee('Open licence and files')->assertSee('Upgrade to the Extended licence')->assertSee('M-Pesa number');

    app(LicenceService::class)->grant($this->buyer, $this->product, LicenceTier::Extended);
    $this->get(route('models.show', $this->product))->assertSee('You own this model')->assertDontSee('M-Pesa number');
});

it('prefills the phone box from the buyer\'s profile', function () {
    $this->buyer->profile()->create(['telephone_number' => '0733111222']);

    $this->actingAs($this->buyer)->get(route('models.show', $this->product))->assertSee('0733111222');
});

/** ---------------------------------------------------------------- downloads */
function modelFile(Product $product, string $name = 'chair.glb'): ProductFile
{
    Storage::disk('local')->put("product-files/{$product->id}/{$name}", 'binary model data');

    return ProductFile::create(['product_id' => $product->id, 'disk' => 'local', 'path' => "product-files/{$product->id}/{$name}", 'original_name' => $name, 'extension' => 'glb', 'kind' => 'exchange', 'size_bytes' => 17, 'checksum' => str_repeat('a', 64)]);
}

it('lets the holder download the files of a model they paid for, and keeps a record of each download', function () {
    $file = modelFile($this->product);
    app(PaymentService::class)->refresh(startPayment('0712345675'));
    $licence = IssuedLicence::sole();
    $this->actingAs($this->buyer);

    $this->get(route('licences.show', $licence))->assertSee('Your files')->assertSee('chair.glb')->assertSee('Download');
    $this->get(route('licences.download', [$licence, $file]))->assertOk()->assertDownload('chair.glb');

    expect(LicenceDownload::sole())->toMatchArray(['issued_licence_id' => $licence->id, 'product_file_id' => $file->id, 'file_name' => 'chair.glb']);
});

it('keeps the files private to the licence holder, and to that model\'s own files', function () {
    $file = modelFile($this->product);
    $other = Product::factory()->published()->create(['user_id' => $this->seller->id]);
    $otherFile = modelFile($other, 'other.glb');
    app(PaymentService::class)->refresh(startPayment('0712345675'));
    $licence = IssuedLicence::sole();

    $this->get(route('licences.download', [$licence, $file]))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route('licences.download', [$licence, $file]))->assertNotFound();
    $this->actingAs($this->buyer)->get(route('licences.download', [$licence, $otherFile]))->assertNotFound();

    expect(LicenceDownload::count())->toBe(0);
});

it('stops downloads when the licence has ended', function () {
    $file = modelFile($this->product);
    app(PaymentService::class)->refresh(startPayment('0712345675'));
    $licence = IssuedLicence::sole();
    app(LicenceService::class)->revoke($licence, 'Refunded.');
    $this->actingAs($this->buyer);

    $this->get(route('licences.download', [$licence, $file]))->assertForbidden();
    $this->get(route('licences.show', $licence))->assertDontSee('Your files')->assertSee('This licence ended');
    expect(LicenceDownload::count())->toBe(0);
});

/** ---------------------------------------------------------------- the gateway itself */
it('will not run the fake gateway in production', function () {
    app()->detectEnvironment(fn () => 'production');
    app()->forgetInstance(PaymentGateway::class);

    expect(fn () => app(PaymentGateway::class))->toThrow(RuntimeException::class, 'cannot run in production');

    config(['payments.allow_fake_in_production' => true]);
    app()->forgetInstance(PaymentGateway::class);
    expect(app(PaymentGateway::class))->toBeInstanceOf(FakeGateway::class);
});

it('refuses an unknown gateway', function () {
    config(['payments.gateway' => 'carrier-pigeon']);
    app()->forgetInstance(PaymentGateway::class);

    expect(fn () => app(PaymentGateway::class))->toThrow(RuntimeException::class, 'Unknown payment gateway [carrier-pigeon]');
});

it('says plainly when a file has gone missing, and does not count it as a download', function () {
    $file = modelFile($this->product);
    app(PaymentService::class)->refresh(startPayment('0712345675'));
    Storage::disk('local')->delete($file->path);

    $this->actingAs($this->buyer)->get(route('licences.download', [IssuedLicence::sole(), $file]))->assertNotFound();

    expect(LicenceDownload::count())->toBe(0);
});
