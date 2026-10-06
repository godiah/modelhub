<?php

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/*
 * Opening the help article behind a source under an answer. Like everything else the panel does, the browser only talks to ModelHub: the call
 * is signed (including its query string), names the signed-in member, and nothing the browser adds is passed on.
 */

const ARTICLE_CHUNK_ID = '6f1c1c9e-0000-4000-8000-000000000001';

/** True when the request carries the signature the service will recompute from the same parts, for this exact path and query. */
function articleSignedFor(Request $request, string $pathAndQuery): bool
{
    $header = fn (string $name) => $request->header($name)[0] ?? null;
    $canonical = implode("\n", ['GET', $pathAndQuery, $header('X-Support-Timestamp'), $header('X-Support-Nonce'), hash('sha256', '')]);

    return $request->url() === 'http://agent.test'.$pathAndQuery
        && $request->method() === 'GET'
        && $request->body() === ''
        && hash_equals(hash_hmac('sha256', $canonical, 'test-secret'), $header('X-Support-Signature'));
}

function articleClaimsOf(Request $request): array
{
    [, $payload] = explode('.', $request->header('X-Support-User-Context')[0]);

    return json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
}

beforeEach(function () {
    $keypair = sodium_crypto_sign_keypair();

    config([
        'support.enabled' => true,
        'support.agent.url' => 'http://agent.test',
        'support.agent.hmac_key_id' => 'current',
        'support.agent.hmac_secret' => 'test-secret',
        'support.agent.context_private_key' => base64_encode(sodium_crypto_sign_secretkey($keypair)),
    ]);
});

it('is closed to anyone who is not signed in', function () {
    Http::fake();

    $this->getJson(route('support.articles.show', 'selling-a-model'))->assertUnauthorized();

    Http::assertNothingSent();
});

it('sends nothing anywhere while the kill switch is off', function () {
    Http::fake();
    config(['support.enabled' => false]);

    $this->actingAs(User::factory()->create())->getJson(route('support.articles.show', 'selling-a-model'))
        ->assertStatus(503)->assertJsonPath('error.code', 'assistant_disabled');

    Http::assertNothingSent();
});

it('opens an article, signed for that exact path, with the member named', function () {
    $article = ['slug' => 'selling-a-model', 'title' => 'Selling a model', 'sections' => [['heading' => 'Review', 'level' => 2, 'text' => 'Staff publish it.', 'cited' => false]]];
    Http::fake(['agent.test/*' => Http::response($article, 200)]);
    $user = User::factory()->create();

    $this->actingAs($user)->getJson(route('support.articles.show', 'selling-a-model'))
        ->assertOk()->assertExactJson($article)->assertHeader('Cache-Control', 'no-store, private');

    Http::assertSent(fn (Request $request) => articleSignedFor($request, '/v1/articles/selling-a-model')
        && articleClaimsOf($request)['sub'] === (string) $user->id);
});

it('passes the passage to mark on, and signs it as part of the address', function () {
    Http::fake(['agent.test/*' => Http::response(['slug' => 'selling-a-model', 'title' => 'x', 'sections' => []], 200)]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('support.articles.show', ['slug' => 'selling-a-model', 'chunk' => ARTICLE_CHUNK_ID]))->assertOk();

    Http::assertSent(fn (Request $request) => articleSignedFor($request, '/v1/articles/selling-a-model?chunk='.ARTICLE_CHUNK_ID));
});

it('passes nothing else the browser adds', function () {
    Http::fake(['agent.test/*' => Http::response(['slug' => 'a', 'title' => 'x', 'sections' => []], 200)]);
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/support/articles/selling-a-model?user_id=1&sub=1&chunk='.ARTICLE_CHUNK_ID.'&extra=x')->assertOk();

    Http::assertSent(fn (Request $request) => $request->url() === 'http://agent.test/v1/articles/selling-a-model?chunk='.ARTICLE_CHUNK_ID
        && articleClaimsOf($request)['sub'] === (string) $user->id);
});

it('refuses a slug that is not a slug and a passage that is not an id, before anything is sent', function () {
    Http::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/support/articles/Selling_A_Model')->assertNotFound();
    $this->actingAs($user)->getJson('/support/articles/'.str_repeat('a', 81))->assertNotFound();
    $this->actingAs($user)->getJson('/support/articles/selling-a-model?chunk=not-an-id')->assertStatus(422);
    $this->actingAs($user)->getJson('/support/articles/selling-a-model?chunk[]=1')->assertStatus(422);

    Http::assertNothingSent();
});

it("passes on 'not found' as it is, and keeps its own failures private", function (int $agentStatus, array $agentBody, int $memberStatus, string $code) {
    Http::fake(['agent.test/*' => Http::response($agentBody, $agentStatus)]);

    $response = $this->actingAs(User::factory()->create())->getJson(route('support.articles.show', 'selling-a-model'))
        ->assertStatus($memberStatus)->assertJsonPath('error.code', $code);

    if ($memberStatus === 503) {
        expect($response->getContent())->not->toContain('signature')->not->toContain('boom');
    }
})->with([
    'an article that does not exist' => [404, ['error' => ['code' => 'article_not_found']], 404, 'article_not_found'],
    'our signature rejected' => [401, ['error' => ['code' => 'bad_signature']], 503, 'assistant_unavailable'],
    'an article that cannot be shown right now' => [503, ['error' => ['code' => 'article_unavailable']], 503, 'assistant_unavailable'],
    'the service crashed' => [500, ['error' => 'boom'], 503, 'assistant_unavailable'],
]);

it('says the assistant is unavailable when the service cannot be reached', function () {
    Http::fake(fn () => throw new ConnectionException('connection refused'));

    $this->actingAs(User::factory()->create())->getJson(route('support.articles.show', 'selling-a-model'))
        ->assertStatus(503)->assertJsonPath('error.code', 'assistant_unavailable');
});
