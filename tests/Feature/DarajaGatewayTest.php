<?php

use App\Contracts\PaymentGateway;
use App\Contracts\PayoutGateway;
use App\Enums\GatewayState;
use App\Enums\LicenceTier;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Models\IssuedLicence;
use App\Models\LedgerTransaction;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\StaffActivity;
use App\Models\User;
use App\Notifications\PayoutNotConfirmedNotification;
use App\Notifications\PayoutNotSentNotification;
use App\Notifications\PayoutPaidNotification;
use App\Services\Ledger\LedgerService;
use App\Services\Payments\DarajaCallbacks;
use App\Services\Payments\DarajaGateway;
use App\Services\Payments\DarajaPayoutGateway;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PayoutService;
use App\Support\Ledger\LedgerLine;
use App\Support\Payments\PayoutRequest;
use Godiah\Common\Mpesa\MpesaClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/*
 * Real M-Pesa through godiah/laravel-common: collecting with STK Push and paying out with B2C. Safaricom is faked at the HTTP layer, so
 * these check the exact requests we send and how every answer, callback and silence is acted on.
 */

const SECRET = 'a-long-random-callback-secret-1234';

beforeEach(function () {
    config([
        'marketplace.purchases_enabled' => true, 'payments.gateway' => 'daraja', 'payments.payout_gateway' => 'daraja', 'payments.daraja.callback_secret' => SECRET,
        'mpesa.default_environment' => 'sandbox', 'mpesa.cache.enabled' => false,
        'mpesa.sandbox.consumer_key' => 'key', 'mpesa.sandbox.consumer_secret' => 'secret', 'mpesa.sandbox.business_short_code' => '600111', 'mpesa.sandbox.lipa_na_mpesa_passkey' => 'passkey',
        'mpesa.sandbox.initiator_name' => 'initiator', 'mpesa.sandbox.security_credential' => 'CRED==',
    ]);
    foreach ([MpesaClient::class, PaymentGateway::class, PayoutGateway::class, PaymentService::class, PayoutService::class] as $abstract) {
        app()->forgetInstance($abstract);
    }
    Notification::fake();

    $this->buyer = User::factory()->create(['name' => 'Buyer Person']);
    $this->seller = User::factory()->create(['name' => 'Seller Person']);
    SellerProfile::factory()->approved()->create(['user_id' => $this->seller->id]);
    $this->product = Product::factory()->published()->create(['user_id' => $this->seller->id, 'title' => 'Oak armchair', 'price_minor' => 120000]);
    $this->finance = staffWith('Finance');
});

function oauthOk(array $more = []): void
{
    // A fresh fake each time: stubs added to an existing one never beat the ones already registered
    Http::swap(new Factory);
    Http::fake(['*/oauth/*' => Http::response(['access_token' => 'tok', 'expires_in' => '3599'], 200)] + $more);
}

function pendingDarajaPayment(string $checkoutId = 'ws_CO_TEST1'): Payment
{
    return Payment::create([
        'reference' => Payment::newReference(), 'user_id' => test()->buyer->id, 'seller_id' => test()->seller->id, 'product_id' => test()->product->id, 'tier' => LicenceTier::Standard, 'amount_minor' => 120000,
        'currency' => 'KES', 'msisdn' => '254712345675', 'status' => PaymentStatus::Pending, 'gateway' => 'daraja', 'gateway_reference' => $checkoutId, 'commission_rate' => 0.15, 'commission_minor' => 18000,
        'seller_share_minor' => 102000, 'hold_days' => 7, 'expires_at' => now()->addMinutes(5),
    ]);
}

function stkCallback(string $checkoutId, int $code, int $amount = 1200, string $receipt = 'RCP123ABCD'): array
{
    $callback = ['MerchantRequestID' => 'm-1', 'CheckoutRequestID' => $checkoutId, 'ResultCode' => $code, 'ResultDesc' => $code === 0 ? 'The service request is processed successfully.' : 'Request cancelled by user'];

    if ($code === 0) {
        $callback['CallbackMetadata'] = ['Item' => [['Name' => 'Amount', 'Value' => $amount], ['Name' => 'MpesaReceiptNumber', 'Value' => $receipt], ['Name' => 'PhoneNumber', 'Value' => 254712345675]]];
    }

    return ['Body' => ['stkCallback' => $callback]];
}

/** A withdrawal being sent by the Daraja gateway: KES 6,000 asked, KES 5,970 sent. */
function sendingWithdrawal(): Payout
{
    $payout = Payout::create([
        'reference' => Payout::newReference(), 'user_id' => test()->seller->id, 'amount_minor' => 600000, 'fee_minor' => 3000, 'net_minor' => 597000, 'currency' => 'KES', 'msisdn' => '254712345675',
        'status' => PayoutStatus::Processing, 'gateway' => 'daraja', 'approved_by' => test()->finance->id, 'approved_at' => now()->subMinutes(5),
    ]);
    $payout->update(['gateway_reference' => $payout->reference]);

    // The money the withdrawal holds: it left the seller's balance when it was asked for
    $ledger = app(LedgerService::class);
    $ledger->post('seed', 'seed:'.$payout->id, [
        LedgerLine::debit($ledger->platformAccount('gateway'), 600000), LedgerLine::credit($ledger->platformAccount('payout_clearing'), 600000),
    ], 'Money for the withdrawal');

    return $payout;
}

function b2cResult(string $reference, int $code = 0, string $receipt = 'TA57ZBCAM3', int $amount = 5970): array
{
    $result = ['ResultType' => 0, 'ResultCode' => $code, 'ResultDesc' => $code === 0 ? 'The service request is processed successfully.' : 'The initiator information is invalid.', 'OriginatorConversationID' => $reference, 'ConversationID' => 'AG_1'];

    if ($code === 0) {
        $result += ['TransactionID' => $receipt, 'ResultParameters' => ['ResultParameter' => [['Key' => 'TransactionAmount', 'Value' => $amount], ['Key' => 'TransactionReceipt', 'Value' => $receipt], ['Key' => 'ReceiverPartyPublicName', 'Value' => '254712345675 - Jane']]]];
    }

    return ['Result' => $result];
}

/** ---------------------------------------------------------------- the gateways are chosen by config */
it('uses the Daraja gateways when configured, and refuses to build them without a callback secret', function () {
    expect(app(PaymentGateway::class))->toBeInstanceOf(DarajaGateway::class)->and(app(PayoutGateway::class))->toBeInstanceOf(DarajaPayoutGateway::class);

    config(['payments.daraja.callback_secret' => 'short']);
    expect(fn () => DarajaCallbacks::paymentUrl())->toThrow(RuntimeException::class, 'PAYMENTS_DARAJA_CALLBACK_SECRET');
});

it('builds callback URLs that avoid the words Daraja rejects and carry the secret', function () {
    foreach ([DarajaCallbacks::paymentUrl(), DarajaCallbacks::payoutUrl(), DarajaCallbacks::payoutTimeoutUrl()] as $url) {
        expect($url)->toContain('/webhooks/')->and($url)->toContain('/daraja')->and($url)->toContain('token='.SECRET)->and(strtolower($url))->not->toContain('mpesa')->not->toContain('safaricom');
    }
});

/** ---------------------------------------------------------------- collecting: STK push */
it('sends an STK push with whole shillings, our reference and a secret callback URL', function () {
    oauthOk(['*/stkpush/*' => Http::response(['MerchantRequestID' => 'm-1', 'CheckoutRequestID' => 'ws_CO_ABC', 'ResponseCode' => '0', 'ResponseDescription' => 'Success', 'CustomerMessage' => 'Success. Request accepted for processing'])]);

    $started = app(PaymentService::class)->start($this->buyer, $this->product, LicenceTier::Standard, '0712345675');

    expect($started)->toBeInstanceOf(Payment::class)->and($started->gateway)->toBe('daraja')->and($started->gateway_reference)->toBe('ws_CO_ABC')->and($started->status)->toBe(PaymentStatus::Pending);

    Http::assertSent(function (Request $request) use ($started) {
        if (! str_contains($request->url(), '/stkpush/')) {
            return true;
        }

        return $request->url() === 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' && $request->hasHeader('Authorization', 'Bearer tok')
            && $request['Amount'] === 1200 && $request['PhoneNumber'] === '254712345675' && $request['PartyA'] === '254712345675' && $request['PartyB'] === '600111' && $request['BusinessShortCode'] === '600111'
            && $request['AccountReference'] === $started->reference && $request['CallBackURL'] === DarajaCallbacks::paymentUrl() && $request['TransactionType'] === 'CustomerPayBillOnline';
    });
});

it('fails the payment with a plain message when Daraja refuses the push', function () {
    oauthOk(['*/stkpush/*' => Http::response(['requestId' => 'x', 'errorCode' => '400.002.02', 'errorMessage' => 'Bad Request - Invalid PhoneNumber'], 400)]);

    $payment = app(PaymentService::class)->start($this->buyer, $this->product, LicenceTier::Standard, '0712345675');

    expect($payment->status)->toBe(PaymentStatus::Failed)->and($payment->failure_reason)->toBe('The M-Pesa prompt could not be sent. Check the number and try again.')->and(IssuedLicence::count())->toBe(0);
});

it('does not fail a payment because Safaricom could not be reached', function () {
    Http::fake(['*/oauth/*' => Http::response(['error' => 'down'], 503)]);

    $payment = app(PaymentService::class)->start($this->buyer, $this->product, LicenceTier::Standard, '0712345675');

    expect($payment->status)->toBe(PaymentStatus::Failed)->and($payment->failure_reason)->toBe('Payments are not available right now. Please try again in a moment.');
});

it('reads the status query: paid, cancelled, timed out, declined, still processing, or no answer', function () {
    $gateway = app(PaymentGateway::class);
    $query = fn (array $body, int $status = 200) => tap($gateway, fn () => oauthOk(['*/stkpushquery/*' => Http::response($body, $status)]))->queryPayment('ws_CO_1');

    $paid = $query(['ResponseCode' => '0', 'ResultCode' => '0', 'ResultDesc' => 'The service request is processed successfully.']);
    expect($paid->state)->toBe(GatewayState::Succeeded)->and($paid->receipt)->toBeNull()->and($paid->amountMinor)->toBeNull();

    expect($query(['ResultCode' => '1032', 'ResultDesc' => 'Request cancelled by user'])->state)->toBe(GatewayState::Cancelled)
        ->and($query(['ResultCode' => '1037', 'ResultDesc' => 'DS timeout user cannot be reached'])->state)->toBe(GatewayState::TimedOut);

    $declined = $query(['ResultCode' => '1', 'ResultDesc' => 'The balance is insufficient for the transaction']);
    expect($declined->state)->toBe(GatewayState::Failed)->and($declined->reason)->toBe('The balance is insufficient for the transaction');

    // Still being processed: Daraja answers with an error body and no ResultCode
    expect($query(['requestId' => 'r', 'errorCode' => '500.001.1001', 'errorMessage' => 'The transaction is being processed'], 500)->state)->toBe(GatewayState::Pending)
        ->and($query([], 502)->state)->toBe(GatewayState::Pending);
});

it('settles a payment from the secret callback: licence, ledger and receipt, once', function () {
    $payment = pendingDarajaPayment();
    $url = route('webhooks.payments', ['name' => 'daraja', 'token' => SECRET]);

    $this->postJson($url, stkCallback('ws_CO_TEST1', 0))->assertOk()->assertJson(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    $this->postJson($url, stkCallback('ws_CO_TEST1', 0))->assertOk();

    $payment->refresh();
    expect($payment->status)->toBe(PaymentStatus::Succeeded)->and($payment->receipt)->toBe('RCP123ABCD')->and($payment->received_minor)->toBe(120000)->and(IssuedLicence::count())->toBe(1)
        ->and(LedgerTransaction::where('type', 'sale')->count())->toBe(1);
});

it('parks a callback whose amount is not what was asked for, for staff to review', function () {
    $payment = pendingDarajaPayment();

    $this->postJson(route('webhooks.payments', ['name' => 'daraja', 'token' => SECRET]), stkCallback('ws_CO_TEST1', 0, 1100))->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Review)->and($payment->fresh()->received_minor)->toBe(110000)->and(IssuedLicence::count())->toBe(0);
});

it('ends a payment on a cancelled or declined callback', function () {
    $cancelled = pendingDarajaPayment();
    $this->postJson(route('webhooks.payments', ['name' => 'daraja', 'token' => SECRET]), stkCallback('ws_CO_TEST1', 1032))->assertOk();
    expect($cancelled->fresh()->status)->toBe(PaymentStatus::Cancelled);

    $declined = pendingDarajaPayment('ws_CO_TEST2');
    $this->postJson(route('webhooks.payments', ['name' => 'daraja', 'token' => SECRET]), stkCallback('ws_CO_TEST2', 1))->assertOk();
    expect($declined->fresh()->status)->toBe(PaymentStatus::Failed)->and($declined->fresh()->failure_reason)->toBe('Request cancelled by user');
});

it('refuses payment callbacks without the secret, with a wrong one, or for another gateway, and changes nothing', function () {
    $payment = pendingDarajaPayment();
    $body = stkCallback('ws_CO_TEST1', 0);

    $this->postJson(route('webhooks.payments', ['name' => 'daraja']), $body)->assertForbidden();
    $this->postJson(route('webhooks.payments', ['name' => 'daraja', 'token' => 'wrong']), $body)->assertForbidden();
    $this->postJson(route('webhooks.payments', ['name' => 'daraja', 'token' => SECRET.'x']), $body)->assertForbidden();
    $this->postJson(route('webhooks.payments', ['name' => 'fake', 'token' => SECRET]), $body)->assertNotFound();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)->and(IssuedLicence::count())->toBe(0);
});

it('acknowledges but ignores callbacks it does not understand or for payments it never started', function () {
    $payment = pendingDarajaPayment();
    $url = route('webhooks.payments', ['name' => 'daraja', 'token' => SECRET]);

    $this->postJson($url, ['nonsense' => true])->assertOk()->assertJson(['ResultCode' => 0]);
    $this->postJson($url, stkCallback('ws_CO_UNKNOWN', 0))->assertOk();
    $this->postJson($url, [])->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('fills in the receipt from the callback when the status query settled the payment first', function () {
    $payment = pendingDarajaPayment();
    oauthOk(['*/stkpushquery/*' => Http::response(['ResultCode' => '0', 'ResultDesc' => 'ok'])]);

    app(PaymentService::class)->refresh($payment);
    expect($payment->fresh()->status)->toBe(PaymentStatus::Succeeded)->and($payment->fresh()->receipt)->toBeNull()->and($payment->fresh()->received_minor)->toBe(120000);

    $this->postJson(route('webhooks.payments', ['name' => 'daraja', 'token' => SECRET]), stkCallback('ws_CO_TEST1', 0))->assertOk();

    expect($payment->fresh()->receipt)->toBe('RCP123ABCD')->and(IssuedLicence::count())->toBe(1)->and(LedgerTransaction::where('type', 'sale')->count())->toBe(1);
});

/** ---------------------------------------------------------------- paying out: B2C */
it('sends a B2C payment with our reference, whole shillings and secret result URLs', function () {
    oauthOk(['*/b2c/*' => Http::response(['ConversationID' => 'AG_1', 'OriginatorConversationID' => 'POREF1', 'ResponseCode' => '0', 'ResponseDescription' => 'Accept the service request successfully.'])]);

    $response = app(PayoutGateway::class)->sendPayout(new PayoutRequest('POREF1', '254712345675', 597000, 'Withdrawal'));

    expect($response->accepted)->toBeTrue()->and($response->gatewayReference)->toBe('POREF1');

    Http::assertSent(function (Request $request) {
        if (! str_contains($request->url(), '/b2c/')) {
            return true;
        }

        return $request->url() === 'https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest' && $request['OriginatorConversationID'] === 'POREF1' && $request['InitiatorName'] === 'initiator'
            && $request['SecurityCredential'] === 'CRED==' && $request['CommandID'] === 'BusinessPayment' && $request['Amount'] === 5970 && $request['PartyA'] === '600111' && $request['PartyB'] === '254712345675'
            && $request['ResultURL'] === DarajaCallbacks::payoutUrl() && $request['QueueTimeOutURL'] === DarajaCallbacks::payoutTimeoutUrl() && $request['Remarks'] === 'Withdrawal';
    });
});

it('treats a refusal Daraja names as a definite no, and anything unclear as sent', function () {
    $gateway = app(PayoutGateway::class);
    $send = fn () => $gateway->sendPayout(new PayoutRequest('POREF2', '254712345675', 100000, 'Withdrawal'));

    oauthOk(['*/b2c/*' => Http::response(['requestId' => 'r', 'errorCode' => '401.002.01', 'errorMessage' => 'Error Occurred - Invalid Access Token'], 401)]);
    $refused = $send();
    expect($refused->accepted)->toBeFalse()->and($refused->message)->toBe('The transfer could not be started.');

    oauthOk(['*/b2c/*' => Http::response(['ConversationID' => '', 'ResponseCode' => '2001', 'ResponseDescription' => 'Invalid initiator'])]);
    expect($send()->accepted)->toBeFalse();

    // No usable answer: it may have been queued, so it must not be given back
    oauthOk(['*/b2c/*' => Http::response('', 503)]);
    $unclear = $send();
    expect($unclear->accepted)->toBeTrue()->and($unclear->gatewayReference)->toBe('POREF2');

    oauthOk(['*/b2c/*' => fn () => throw new ConnectionException('cURL error 28: timed out')]);
    $dropped = $send();
    expect($dropped->accepted)->toBeTrue()->and($dropped->gatewayReference)->toBe('POREF2');
});

it('treats a missing access token or credentials as nothing sent', function () {
    Http::fake(['*/oauth/*' => Http::response(['error' => 'invalid_client'], 401)]);
    $response = app(PayoutGateway::class)->sendPayout(new PayoutRequest('POREF3', '254712345675', 100000, 'Withdrawal'));
    expect($response->accepted)->toBeFalse()->and($response->message)->toContain('not available right now');

    config(['mpesa.sandbox.security_credential' => '']);
    app()->forgetInstance(MpesaClient::class);
    app()->forgetInstance(PayoutGateway::class);
    Http::fake();
    expect(app(PayoutGateway::class)->sendPayout(new PayoutRequest('POREF4', '254712345675', 100000, 'Withdrawal'))->accepted)->toBeFalse();
    Http::assertNothingSent();
});

it('cannot ask Daraja where a B2C payment is, so it stays pending', function () {
    Http::fake();

    expect(app(PayoutGateway::class)->queryPayout('POREF1')->state)->toBe(GatewayState::Pending);
    Http::assertNothingSent();
});

it('closes a withdrawal as paid from the secret result callback, once', function () {
    $payout = sendingWithdrawal();
    $url = route('webhooks.payouts', ['name' => 'daraja', 'token' => SECRET]);

    $this->postJson($url, b2cResult($payout->reference))->assertOk()->assertJson(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    $this->postJson($url, b2cResult($payout->reference))->assertOk();

    expect($payout->fresh()->status)->toBe(PayoutStatus::Paid)->and($payout->fresh()->receipt)->toBe('TA57ZBCAM3')->and(LedgerTransaction::where('type', 'payout_paid')->count())->toBe(1);
    Notification::assertSentToTimes($this->seller, PayoutPaidNotification::class, 1);
});

it('gives the money back when the result says the transfer failed', function () {
    $payout = sendingWithdrawal();

    $this->postJson(route('webhooks.payouts', ['name' => 'daraja', 'token' => SECRET]), b2cResult($payout->reference, 2001))->assertOk();

    expect($payout->fresh()->status)->toBe(PayoutStatus::Failed)->and($payout->fresh()->failure_reason)->toBe('The initiator information is invalid.')->and(app(LedgerService::class)->balances($this->seller)['available'])->toBe(600000);
    Notification::assertSentTo($this->seller, PayoutNotSentNotification::class);
});

it('refuses payout results and timeouts without the secret, and changes nothing', function () {
    $payout = sendingWithdrawal();

    $this->postJson(route('webhooks.payouts', ['name' => 'daraja']), b2cResult($payout->reference))->assertForbidden();
    $this->postJson(route('webhooks.payouts', ['name' => 'daraja', 'token' => 'nope']), b2cResult($payout->reference))->assertForbidden();
    $this->postJson(route('webhooks.payouts.timeout', ['name' => 'daraja']), b2cResult($payout->reference, 1))->assertForbidden();
    $this->postJson(route('webhooks.payouts', ['name' => 'fake', 'token' => SECRET]), b2cResult($payout->reference))->assertNotFound();

    expect($payout->fresh()->status)->toBe(PayoutStatus::Processing)->and(LedgerTransaction::where('type', 'payout_paid')->count())->toBe(0);
});

it('logs a queue timeout and leaves the withdrawal being sent, because the money may still go', function () {
    Log::spy();
    $payout = sendingWithdrawal();

    $this->postJson(route('webhooks.payouts.timeout', ['name' => 'daraja', 'token' => SECRET]), b2cResult($payout->reference, 1))->assertOk()->assertJson(['ResultCode' => 0]);

    expect($payout->fresh()->status)->toBe(PayoutStatus::Processing);
    Log::shouldHaveReceived('warning')->withArgs(fn ($message) => str_contains($message, 'queue timeout'))->once();
});

it('ignores payout results it does not understand', function () {
    $payout = sendingWithdrawal();
    $url = route('webhooks.payouts', ['name' => 'daraja', 'token' => SECRET]);

    $this->postJson($url, ['nope' => 1])->assertOk();
    $this->postJson($url, b2cResult('NOT-OURS'))->assertOk();

    expect($payout->fresh()->status)->toBe(PayoutStatus::Processing);
});

/** ---------------------------------------------------------------- when we cannot tell whether it was sent */
it('keeps an approved withdrawal being sent, instead of paying it back, when the gateway call blows up', function () {
    $gateway = Mockery::mock(PayoutGateway::class);
    $gateway->shouldReceive('name')->andReturn('daraja');
    $gateway->shouldReceive('sendPayout')->andThrow(new RuntimeException('boom'));
    $this->app->instance(PayoutGateway::class, $gateway);
    app()->forgetInstance(PayoutService::class);

    $ledger = app(LedgerService::class);
    $ledger->post('seed', 'seed:funds', [LedgerLine::debit($ledger->platformAccount('gateway'), 700000), LedgerLine::credit($ledger->userAccount($this->seller, 'available'), 700000)], 'Funds');
    $payouts = app(PayoutService::class);
    $payout = $payouts->request($this->seller, 600000, '0712345675');

    $approved = $payouts->approve($payout, $this->finance);

    expect($approved->status)->toBe(PayoutStatus::Processing)->and($approved->gateway_reference)->toBe($payout->reference)->and($ledger->balances($this->seller)['available'])->toBe(100000);
});

/** ---------------------------------------------------------------- not confirmed: told, then settled by hand */
it('tells the approvers once a day about a withdrawal M-Pesa has not confirmed, and not before', function () {
    $payout = sendingWithdrawal();
    $payouts = app(PayoutService::class);

    $payouts->checkProcessing();
    Notification::assertNothingSentTo($this->finance);

    $payout->update(['approved_at' => now()->subHours(7)]);
    $payouts->checkProcessing();
    $payouts->checkProcessing();
    Notification::assertSentToTimes($this->finance, PayoutNotConfirmedNotification::class, 1);

    $this->travel(25)->hours();
    $payouts->checkProcessing();
    Notification::assertSentToTimes($this->finance, PayoutNotConfirmedNotification::class, 2);
    expect($payout->fresh()->status)->toBe(PayoutStatus::Processing);
});

it('lets staff record a stuck withdrawal as sent, with the receipt, closing the books as a result would', function () {
    $payout = sendingWithdrawal();

    $this->actingAs($this->finance, 'staff')->post(route('admin.payouts.settle', $payout), ['outcome' => 'sent', 'receipt' => 'tb12cd34ef'])->assertRedirect()->assertSessionHas('success');

    expect($payout->fresh()->status)->toBe(PayoutStatus::Paid)->and($payout->fresh()->receipt)->toBe('TB12CD34EF')->and(LedgerTransaction::where('type', 'payout_paid')->count())->toBe(1);
    expect(StaffActivity::where('action', 'payout.settled_sent')->where('staff_id', $this->finance->id)->exists())->toBeTrue();
    Notification::assertSentTo($this->seller, PayoutPaidNotification::class);
});

it('lets staff record a stuck withdrawal as not sent, giving the money back with their note', function () {
    $payout = sendingWithdrawal();

    $this->actingAs($this->finance, 'staff')->post(route('admin.payouts.settle', $payout), ['outcome' => 'not_sent', 'note' => 'Not in the portal at all.'])->assertRedirect()->assertSessionHas('success');

    expect($payout->fresh()->status)->toBe(PayoutStatus::Failed)->and($payout->fresh()->failure_reason)->toBe('Not in the portal at all.')->and(app(LedgerService::class)->balances($this->seller)['available'])->toBe(600000);
    expect(StaffActivity::where('action', 'payout.settled_not_sent')->exists())->toBeTrue();
    Notification::assertSentTo($this->seller, PayoutNotSentNotification::class);
});

it('validates settling, refuses it once the withdrawal is closed, and keeps it to those who may approve', function () {
    $payout = sendingWithdrawal();
    $this->actingAs($this->finance, 'staff');

    $this->post(route('admin.payouts.settle', $payout), [])->assertSessionHasErrors('outcome');
    $this->post(route('admin.payouts.settle', $payout), ['outcome' => 'sent'])->assertSessionHasErrors('receipt');
    $this->post(route('admin.payouts.settle', $payout), ['outcome' => 'sent', 'receipt' => 'bad receipt!'])->assertSessionHasErrors('receipt');
    $this->post(route('admin.payouts.settle', $payout), ['outcome' => 'not_sent'])->assertSessionHasErrors('note');
    $this->post(route('admin.payouts.settle', $payout), ['outcome' => 'not_sent', 'note' => 'abc'])->assertSessionHasErrors('note');
    expect($payout->fresh()->status)->toBe(PayoutStatus::Processing);

    $this->actingAs(staffWith('Auditor'), 'staff')->post(route('admin.payouts.settle', $payout), ['outcome' => 'sent', 'receipt' => 'TB12CD34EF'])->assertForbidden();
    expect($payout->fresh()->status)->toBe(PayoutStatus::Processing);

    $this->actingAs($this->finance, 'staff')->post(route('admin.payouts.settle', $payout), ['outcome' => 'sent', 'receipt' => 'TB12CD34EF']);
    $this->post(route('admin.payouts.settle', $payout), ['outcome' => 'not_sent', 'note' => 'Changed my mind.'])->assertSessionHas('error');
    expect($payout->fresh()->status)->toBe(PayoutStatus::Paid)->and(LedgerTransaction::where('type', 'payout_returned')->count())->toBe(0);
});

it('shows the Settle action on withdrawals being sent, to those who may approve only', function () {
    $payout = sendingWithdrawal();

    $this->actingAs($this->finance, 'staff')->get(route('admin.payouts.index', ['status' => 'processing']))->assertOk()->assertSee('Settle')->assertSee('Settle this withdrawal by hand')->assertSee('settle-payout', false);
    $viewer = staffWith('Auditor');
    $viewer->givePermissionTo('view payouts');
    $this->actingAs($viewer, 'staff')->get(route('admin.payouts.index', ['status' => 'processing']))->assertOk()->assertDontSee('Settle this withdrawal by hand');
});
