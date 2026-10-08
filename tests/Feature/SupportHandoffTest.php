<?php

use App\Enums\PaymentStatus;
use App\Enums\SupportResolutionTag;
use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketSeverity;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketOpenedNotification;
use App\Notifications\SupportTicketReceivedNotification;
use App\Services\Support\Tickets\TicketService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;

/*
 * "Talk to a person" from the chat panel. The member confirms a category and a summary that code suggested from what the assistant showed; ModelHub files
 * the ticket. The browser never supplies evidence or the records a ticket is about: those come from the assistant over a signed call scoped to the member, are
 * checked against the member, and urgency is decided from their live records. If the assistant is down the ticket is still filed.
 */

const CONVERSATION = '7b0f5b1e-8d1a-4d6b-9a52-3c1e5b9d2f10';

beforeEach(function () {
    $this->member = User::factory()->create();
    config([
        'support.enabled' => true, 'support.agent.url' => 'http://agent.test', 'support.agent.hmac_key_id' => 'current', 'support.agent.hmac_secret' => 's',
        'support.agent.context_private_key' => base64_encode(sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair())),
    ]);
    RateLimiter::clear('support-ticket-reply:'.$this->member->id);
    $this->staff = staffWith('Support');
});

function reviewPaymentOf(User $buyer, PaymentStatus $status = PaymentStatus::Review): Payment
{
    $seller = User::factory()->create();
    SellerProfile::factory()->approved()->create(['user_id' => $seller->id]);
    $product = Product::factory()->published()->create(['user_id' => $seller->id, 'price_minor' => 500000]);

    return Payment::create([
        'reference' => Payment::newReference(), 'purpose' => Payment::PURPOSE_SALE, 'user_id' => $buyer->id, 'seller_id' => $seller->id, 'product_id' => $product->id, 'tier' => 'standard',
        'amount_minor' => 500000, 'currency' => 'KES', 'msisdn' => '254712345675', 'status' => $status, 'gateway' => 'gw', 'gateway_reference' => uniqid('gw'),
        'commission_rate' => 0.15, 'commission_minor' => 75000, 'seller_share_minor' => 425000, 'hold_days' => 7, 'expires_at' => now(),
    ]);
}

function evidenceFor(array $refs = [], array $over = []): array
{
    return $over + [
        'conversation_id' => CONVERSATION, 'suggested_category' => 'payment_issue', 'suggested_summary' => 'Payment MHX: Needs review.', 'entity_refs' => $refs,
        'facts' => ['Payment MHX: Needs review, Ksh5,000, no licence found (as of 2026-10-07T10:00:00+03:00)'], 'cards' => [['type' => 'payment', 'reference' => 'MHX', 'status' => 'review']],
        'messages' => [['role' => 'member', 'text' => 'I paid but I have no licence'], ['role' => 'assistant', 'text' => 'It needs a person.']],
    ];
}

function fakeAgent(array|int $evidence, bool $noteFails = false): void
{
    Http::fake([
        'agent.test/v1/conversations/*/evidence' => is_int($evidence) ? Http::response(['error' => 'x'], $evidence) : Http::response($evidence, 200),
        'agent.test/v1/conversations/*/handoff' => $noteFails ? fn () => throw new ConnectionException('down') : Http::response(null, 204),
    ]);
}

function fileTicket(object $test, array $payload = []): TestResponse
{
    return $test->actingAs($test->member)->postJson(route('support.handoff.store'), $payload + ['conversation_id' => CONVERSATION, 'category' => 'payment_issue', 'summary' => 'I paid but my licence never appeared']);
}

// ---- step one: the suggestion ----------------------------------------------------------------------------------

it('suggests a category and summary from the assistant\'s evidence, and lists the categories', function () {
    fakeAgent(evidenceFor());

    $response = $this->actingAs($this->member)->getJson(route('support.handoff.prepare', ['conversation_id' => CONVERSATION]))->assertOk();

    expect($response->json('category'))->toBe('payment_issue')->and($response->json('summary'))->toBe('Payment MHX: Needs review.')
        ->and(collect($response->json('categories'))->pluck('value')->all())->toContain('payment_issue', 'withdrawal_issue', 'other')
        ->and($response->json('aim'))->toContain('We aim to reply')->and($response->json('existing'))->toBeNull();
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v1/conversations/'.CONVERSATION.'/evidence') && $r->method() === 'GET' && $r->hasHeader('X-Support-User-Context') && $r->hasHeader('X-Support-Signature'));
});

it('falls back to a blank form when there is no chat, the assistant is down, or it is off', function (callable $arrange) {
    $arrange();

    $response = $this->actingAs($this->member)->getJson(route('support.handoff.prepare', ['conversation_id' => CONVERSATION]))->assertOk();

    expect($response->json('category'))->toBe('other')->and($response->json('summary'))->toBe('');

    if (! config('support.enabled')) {
        Http::assertNothingSent(); // switched off means the assistant is not even asked
    }
})->with([
    'the assistant says it is not their chat' => [fn () => fakeAgent(404)],
    'the assistant is down' => [fn () => Http::fake(['*' => fn () => throw new ConnectionException('down')])],
    'the assistant fails' => [fn () => fakeAgent(500)],
    'the assistant is switched off' => [function () {
        config(['support.enabled' => false]);
        Http::fake();
    }],
]);

it('works with no chat at all, and refuses a conversation id that is not an id', function () {
    Http::fake();

    $this->actingAs($this->member)->getJson(route('support.handoff.prepare'))->assertOk()->assertJsonPath('category', 'other');
    $this->actingAs($this->member)->getJson(route('support.handoff.prepare', ['conversation_id' => 'not-a-uuid']))->assertUnprocessable();
    Http::assertNothingSent();
});

it('points at the ticket that already exists for this chat instead of asking again', function () {
    fakeAgent(evidenceFor());
    fileTicket($this)->assertCreated();

    $this->actingAs($this->member)->getJson(route('support.handoff.prepare', ['conversation_id' => CONVERSATION]))->assertJsonPath('existing.reference', SupportTicket::first()->reference);
});

it('is for signed-in members only', function () {
    $this->getJson(route('support.handoff.prepare'))->assertUnauthorized();
    $this->postJson(route('support.handoff.store'), ['category' => 'other', 'summary' => 'hello there'])->assertUnauthorized();
});

// ---- step two: filing the ticket ---------------------------------------------------------------------------------

it('files the ticket with the confirmed words, the validated records, a snapshot of the evidence and the chat it came from', function () {
    $mine = reviewPaymentOf($this->member);
    fakeAgent(evidenceFor([['type' => 'payment', 'reference' => $mine->reference]]));

    $response = fileTicket($this, ['summary' => 'My Ksh5,000 payment never gave me a licence'])->assertCreated();

    $ticket = SupportTicket::where('reference', $response->json('reference'))->first();
    expect($ticket->requester_id)->toBe($this->member->id)->and($ticket->category)->toBe(SupportTicketCategory::PaymentIssue)
        ->and($ticket->summary)->toBe('My Ksh5,000 payment never gave me a licence')->and($ticket->conversation_id)->toBe(CONVERSATION)
        ->and($ticket->entity_refs)->toBe(['payment:'.$mine->reference])
        ->and($ticket->evidence['facts'][0])->toContain('Needs review')->and($ticket->evidence['source'])->toBe('assistant')->and($ticket->evidence['messages'])->toHaveCount(2)
        ->and($response->json('url'))->toEndWith('/support/requests/'.$ticket->reference)->and($response->json('aim'))->toContain('We aim to reply');
});

it('works out urgency from the live records, not from anything the assistant or the browser says', function () {
    $review = reviewPaymentOf($this->member, PaymentStatus::Review);
    fakeAgent(evidenceFor([['type' => 'payment', 'reference' => $review->reference]], ['suggested_summary' => 'urgent urgent']));

    $high = SupportTicket::find(fileTicket($this, ['severity' => 'low', 'category' => 'other'])->json('reference') ? SupportTicket::latest('id')->first()->id : 0);

    expect($high->severity)->toBe(SupportTicketSeverity::High);

    $fine = reviewPaymentOf($this->member, PaymentStatus::Failed);
    fakeAgent(evidenceFor([['type' => 'payment', 'reference' => $fine->reference]], ['suggested_summary' => 'URGENT']));
    $other = User::factory()->create();
    $calm = $this->actingAs($other)->postJson(route('support.handoff.store'), ['conversation_id' => CONVERSATION, 'category' => 'other', 'summary' => 'URGENT URGENT help now please', 'severity' => 'urgent']);

    expect(SupportTicket::where('reference', $calm->json('reference'))->first()->severity)->toBe(SupportTicketSeverity::Low);
});

it('drops records the member does not own, even if the assistant names them', function () {
    $mine = reviewPaymentOf($this->member, PaymentStatus::Succeeded);
    $theirs = reviewPaymentOf(User::factory()->create());
    fakeAgent(evidenceFor([['type' => 'payment', 'reference' => $mine->reference], ['type' => 'payment', 'reference' => $theirs->reference], ['type' => 'payment', 'reference' => 'MHNOTREAL000']]));

    $ticket = SupportTicket::where('reference', fileTicket($this)->json('reference'))->first();

    expect($ticket->entity_refs)->toBe(['payment:'.$mine->reference])->and(json_encode($ticket->entity_refs))->not->toContain($theirs->reference);
});

it('ignores anything the browser sends beyond category, summary and the chat id', function () {
    $theirs = reviewPaymentOf(User::factory()->create());
    fakeAgent(evidenceFor());

    $response = fileTicket($this, [
        'entity_refs' => [['type' => 'payment', 'reference' => $theirs->reference]], 'evidence' => ['facts' => ['FORGED']], 'severity' => 'urgent', 'status' => 'resolved',
        'requester_id' => User::factory()->create()->id, 'assignee_id' => $this->staff->id,
    ])->assertCreated();

    $ticket = SupportTicket::where('reference', $response->json('reference'))->first();
    expect($ticket->requester_id)->toBe($this->member->id)->and($ticket->entity_refs)->toBeNull()->and(json_encode($ticket->evidence))->not->toContain('FORGED')
        ->and($ticket->assignee_id)->toBeNull()->and($ticket->status->value)->toBe('open');
});

it('still files the ticket, with the member\'s words and no evidence, when the assistant cannot help', function (callable $arrange) {
    $arrange();

    $response = fileTicket($this, ['summary' => 'Please help me with my payment'])->assertCreated();

    $ticket = SupportTicket::where('reference', $response->json('reference'))->first();
    expect($ticket->summary)->toBe('Please help me with my payment')->and($ticket->evidence)->toBeNull()->and($ticket->conversation_id)->toBeNull()->and($ticket->entity_refs)->toBeNull();
})->with([
    'down' => [fn () => Http::fake(['*' => fn () => throw new ConnectionException('down')])],
    'not this member\'s chat' => [fn () => fakeAgent(404)],
    'failing' => [fn () => fakeAgent(500)],
]);

it('files a ticket with no chat at all', function () {
    Http::fake();

    $response = $this->actingAs($this->member)->postJson(route('support.handoff.store'), ['category' => 'account_access', 'summary' => 'I cannot sign in to my account'])->assertCreated();

    $ticket = SupportTicket::where('reference', $response->json('reference'))->first();
    expect($ticket->conversation_id)->toBeNull()->and($ticket->severity)->toBe(SupportTicketSeverity::High);
    Http::assertNothingSent();
});

it('treats the same chat twice as one request', function () {
    fakeAgent(evidenceFor());

    $first = fileTicket($this)->assertCreated();
    $second = fileTicket($this)->assertOk();

    expect($second->json('existing'))->toBeTrue()->and($second->json('reference'))->toBe($first->json('reference'))->and(SupportTicket::count())->toBe(1);

    // once it is resolved, a new request from the same chat is allowed
    $ticket = SupportTicket::first();
    app(TicketService::class)->resolve($ticket, $this->staff, SupportResolutionTag::Other);
    fileTicket($this, ['summary' => 'It happened again, a second time'])->assertCreated();
    expect(SupportTicket::count())->toBe(2);
});

it('says why a request was refused, in words the member can act on', function (array $payload, string $contains) {
    fakeAgent(evidenceFor());

    $response = fileTicket($this, $payload);

    $response->assertUnprocessable();
    expect(json_encode($response->json()))->toContain($contains);
})->with([
    'too short' => [['summary' => 'help'], 'Tell us a little'],
    'an unknown category' => [['category' => 'invented'], 'Choose what this is about'],
    'no category' => [['category' => ''], 'category'],
]);

it('limits how many open requests a member can have', function () {
    config(['support.tickets.max_open' => 1]);
    Http::fake();
    $this->actingAs($this->member)->postJson(route('support.handoff.store'), ['category' => 'other', 'summary' => 'My first question here'])->assertCreated();

    $this->actingAs($this->member)->postJson(route('support.handoff.store'), ['category' => 'other', 'summary' => 'My second question here'])->assertUnprocessable()
        ->assertJsonPath('error.code', 'ticket_refused');
});

it('tells the chat a ticket was filed, and does not fail the ticket if it cannot', function () {
    fakeAgent(evidenceFor());
    $reference = fileTicket($this)->json('reference');

    Http::assertSent(fn (Request $r) => $r->method() === 'POST' && str_ends_with($r->url(), '/v1/conversations/'.CONVERSATION.'/handoff') && $r->data() === ['reference' => $reference] && $r->hasHeader('X-Support-Signature'));

    $other = User::factory()->create();
    fakeAgent(evidenceFor(), noteFails: true);
    $this->actingAs($other)->postJson(route('support.handoff.store'), ['conversation_id' => CONVERSATION, 'category' => 'other', 'summary' => 'Another request entirely'])->assertCreated();
});

it('sends the member their receipt and tells the staff who answer tickets', function () {
    Notification::fake();
    fakeAgent(evidenceFor());

    fileTicket($this)->assertCreated();

    Notification::assertSentTo($this->member, SupportTicketReceivedNotification::class);
    Notification::assertSentTo($this->staff, SupportTicketOpenedNotification::class);
});

it('keeps a very long evidence packet to a sensible size', function () {
    fakeAgent(evidenceFor([], ['cards' => array_fill(0, 30, ['type' => 'payment']), 'messages' => array_fill(0, 60, ['role' => 'member', 'text' => 'x']), 'facts' => array_fill(0, 40, 'a fact')]));

    $ticket = SupportTicket::where('reference', fileTicket($this)->json('reference'))->first();

    expect($ticket->evidence['cards'])->toHaveCount(4)->and($ticket->evidence['messages'])->toHaveCount(12);
});

// ---- what staff see -----------------------------------------------------------------------------------------------

it('shows staff the evidence, with the member\'s words marked as theirs and everything escaped', function () {
    $mine = reviewPaymentOf($this->member);
    fakeAgent(evidenceFor([['type' => 'payment', 'reference' => $mine->reference]], ['messages' => [['role' => 'member', 'text' => '<script>alert(1)</script> I paid'], ['role' => 'assistant', 'text' => 'It needs a person.']], 'facts' => ['<img src=x onerror=alert(1)> fact']]));
    $reference = fileTicket($this)->json('reference');

    $html = $this->actingAs($this->staff, 'staff')->get(route('admin.support.tickets.show', $reference))->assertOk()->assertSee('What the assistant showed')->assertSee('The member wrote')->getContent();

    expect($html)->not->toContain('<script>alert(1)</script>')->not->toContain('<img src=x')->toContain('&lt;script&gt;');
});

// ---- retention ---------------------------------------------------------------------------------------------------

it('drops the evidence from tickets resolved long enough ago and keeps the thread', function () {
    fakeAgent(evidenceFor());
    $old = SupportTicket::find(1) ?? null;
    $service = app(TicketService::class);
    $other = User::factory()->create();
    $oldTicket = $service->open($this->member, SupportTicketCategory::Other, 'An old matter that was settled', [], ['facts' => ['old fact']], CONVERSATION);
    $recent = $service->open($other, SupportTicketCategory::Other, 'A recent matter that was settled', [], ['facts' => ['recent fact']], CONVERSATION);
    $active = $service->open(User::factory()->create(), SupportTicketCategory::Other, 'A matter still going on here', [], ['facts' => ['active fact']], CONVERSATION);
    $service->resolve($oldTicket, $this->staff, SupportResolutionTag::Other, 'All done.');
    $service->resolve($recent, $this->staff, SupportResolutionTag::Other);
    $oldTicket->update(['resolved_at' => now()->subDays(91)]);
    $recent->update(['resolved_at' => now()->subDays(10)]);

    $this->artisan('support:purge-ticket-evidence')->assertSuccessful();

    expect($oldTicket->refresh()->evidence)->toBeNull()->and($oldTicket->messages)->toHaveCount(2)->and($oldTicket->summary)->toBe('An old matter that was settled')
        ->and($recent->refresh()->evidence)->not->toBeNull()->and($active->refresh()->evidence)->not->toBeNull();
});

it('is scheduled to run every day', function () {
    Artisan::call('schedule:list');

    expect(Artisan::output())->toMatch('/30\s+3\s+\*\s+\*\s+\*\s+.*support:purge-ticket-evidence/');
});

it('shows the aim this request would really get: faster for a payment that is under review than for a general question', function () {
    $review = reviewPaymentOf($this->member, PaymentStatus::Review);
    fakeAgent(evidenceFor([['type' => 'payment', 'reference' => $review->reference]]));
    $urgent = $this->actingAs($this->member)->getJson(route('support.handoff.prepare', ['conversation_id' => CONVERSATION]))->json('aim');

    Http::swap(new Factory); // stubs added to an existing fake never beat the ones already there
    fakeAgent(evidenceFor([], ['suggested_category' => 'other', 'suggested_summary' => '']));
    $calm = $this->actingAs($this->member)->getJson(route('support.handoff.prepare', ['conversation_id' => CONVERSATION]))->json('aim');

    expect($urgent)->toContain('within 4 business hours')->and($calm)->toContain('within 2 business days');
});
