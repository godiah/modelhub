<?php

use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/*
 * The panel side of "check my withdrawal": the quick-action chip (shown only where reads are available to this member), the card that
 * renders what the assistant found, and the one extra field the chat endpoint forwards.
 */

function panelHtml(User $user): string
{
    config(['support.enabled' => true, 'support.ui_preview' => false]);

    return test()->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();
}

beforeEach(function () {
    $this->member = User::factory()->create();
    config([
        'support.reads.enabled' => true,
        'support.reads.stage' => 'pilot',
        'support.reads.pilot_member_ids' => [$this->member->id],
        'support.reads.capabilities.withdrawals' => true,
    ]);
});

it('offers "Check my latest withdrawal" first, as one of four, to a member the assistant may read for', function () {
    $html = panelHtml($this->member);

    expect($html)->toContain('Check my latest withdrawal')->toContain('\u0022action\u0022:\u0022withdrawal\u0022');
    // four suggestions at most: it takes the place of the last general question
    expect(substr_count($html, '\u0022key\u0022:\u0022ask\u0022'))->toBe(4)->and($html)->not->toContain('How do I reset my password?');
});

it('does not offer it when reads are off, to a member outside the stage, or with the capability off', function (callable $change) {
    $change($this->member);

    expect(panelHtml($this->member))->not->toContain('Check my latest withdrawal')->toContain('How do I reset my password?');
})->with([
    'reads off' => [fn () => config(['support.reads.enabled' => false])],
    'not in the pilot' => [fn (User $m) => config(['support.reads.pilot_member_ids' => [$m->id + 1]])],
    'unknown stage' => [fn () => config(['support.reads.stage' => 'typo'])],
    'capability off' => [fn () => config(['support.reads.capabilities.withdrawals' => false])],
]);

it('gives the panel one link by route key and no other address', function () {
    $html = panelHtml($this->member);

    expect($html)->toContain('\u0022links\u0022:{\u0022earnings.index\u0022:')->and(substr_count($html, '\u0022links\u0022'))->toBe(1);
});

it('renders a withdrawal card as text only, never as markup the assistant could inject', function () {
    $card = file_get_contents(resource_path('views/components/support/card/withdrawal.blade.php'));

    expect($card)->not->toContain('x-html')
        ->toContain('x-text="card.reference"')
        ->toContain('x-text="card.reason"')
        ->toContain('As of')
        // the link is looked up in a short list, never taken from the card as an address
        ->toContain('links[card.link && card.link.route]');
    expect(file_get_contents(resource_path('views/components/support/widget.blade.php')))
        ->toContain("card.type === 'withdrawal'")
        ->toContain('assistant.cards = Array.isArray(payload.cards)');
});

it('forwards the chip action to the assistant and nothing else new', function () {
    config(['support.enabled' => true, 'support.agent.url' => 'http://agent.test', 'support.agent.hmac_secret' => 's', 'support.agent.context_private_key' => base64_encode(sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair()))]);
    Http::fake(['agent.test/*' => Http::response("event: done\r\ndata: {}\r\n\r\n", 200, ['Content-Type' => 'text/event-stream'])]);

    $this->actingAs($this->member)->postJson(route('support.chat'), ['message' => 'Check my latest withdrawal', 'action' => 'withdrawal', 'role' => 'admin', 'user_id' => 1])->assertOk();

    Http::assertSent(fn (Request $request) => $request->data() === ['message' => 'Check my latest withdrawal', 'action' => 'withdrawal']);
});

it('leaves the action out when the member did not use a chip, and refuses one that is not on the list', function () {
    config(['support.enabled' => true, 'support.agent.url' => 'http://agent.test', 'support.agent.hmac_secret' => 's', 'support.agent.context_private_key' => base64_encode(sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair()))]);
    Http::fake(['agent.test/*' => Http::response("event: done\r\ndata: {}\r\n\r\n", 200, ['Content-Type' => 'text/event-stream'])]);

    $this->actingAs($this->member)->postJson(route('support.chat'), ['message' => 'hello'])->assertOk();
    Http::assertSent(fn (Request $request) => $request->data() === ['message' => 'hello']);

    $this->actingAs($this->member)->postJson(route('support.chat'), ['message' => 'hello', 'action' => 'refund'])->assertUnprocessable();
});
