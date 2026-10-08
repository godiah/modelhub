<?php

use App\Enums\SupportTicketCategory;
use App\Models\Staff;
use App\Models\StaffActivity;
use App\Models\User;
use App\Services\Support\Tickets\TicketService;
use App\Support\Staff\StaffAccess;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

/*
 * Staff reading a member's whole chat with the assistant (T6). A separate permission nobody gets by default except Super admin; every opening is logged
 * before the chat is fetched; the chat is fetched with a claim for that one staff member, chat and member; and what the member wrote is never markup.
 */

const TRANSCRIPT_CONVERSATION = '5c0f5b1e-8d1a-4d6b-9a52-3c1e5b9d2f77';

beforeEach(function () {
    $this->secretKey = sodium_crypto_sign_secretkey($pair = sodium_crypto_sign_keypair());
    $this->publicKey = sodium_crypto_sign_publickey($pair);
    config([
        'support.enabled' => true, 'support.agent.url' => 'http://agent.test', 'support.agent.hmac_key_id' => 'current', 'support.agent.hmac_secret' => 's',
        'support.agent.context_private_key' => base64_encode($this->secretKey),
    ]);
    $this->member = User::factory()->create(['name' => 'Wanjiru Member']);
    $this->ticket = app(TicketService::class)->open($this->member, SupportTicketCategory::Other, 'Need help', conversationId: TRANSCRIPT_CONVERSATION);
});

function transcriptB64Decode(string $value): string
{
    return base64_decode(strtr($value, '-_', '+/'));
}

function transcriptReader(): Staff
{
    StaffAccess::sync();
    $role = Role::create(['name' => 'Transcript reader '.uniqid(), 'guard_name' => 'staff']);
    $role->givePermissionTo(['view support tickets', 'read support transcripts']);
    $staff = Staff::factory()->create();
    $staff->assignRole($role);

    return $staff;
}

function fakeTranscript(array|int $reply = []): void
{
    Http::swap(new Factory);
    Http::fake(['agent.test/v1/transcripts/*' => is_int($reply) ? Http::response(['error' => 'x'], $reply) : Http::response($reply + [
        'conversation_id' => TRANSCRIPT_CONVERSATION, 'member_id' => '1', 'truncated' => false,
        'messages' => [
            ['role' => 'member', 'text' => 'my payment is stuck', 'at' => '2026-10-07T10:00:00+03:00', 'cards' => [], 'guard' => null],
            ['role' => 'assistant', 'text' => 'It needs a person.', 'at' => '2026-10-07T10:00:05+03:00', 'cards' => [['type' => 'payment', 'reference' => 'MHX1']], 'guard' => 'handoff'],
        ],
    ], 200)]);
}

it('is not given to the Support role, only to Super admin', function () {
    StaffAccess::sync();

    expect(Role::findByName('Support', 'staff')->hasPermissionTo('read support transcripts'))->toBeFalse()
        ->and(Role::findByName('Super admin', 'staff')->hasPermissionTo('read support transcripts'))->toBeTrue();
});

it('refuses staff without the permission, and anyone who is not staff, without calling the assistant or logging anything', function () {
    fakeTranscript();
    $support = staffWith('Support');

    $this->get(route('admin.support.tickets.transcript', $this->ticket))->assertRedirect();
    $this->actingAs($this->member)->get(route('admin.support.tickets.transcript', $this->ticket))->assertRedirect();
    $this->actingAs($support, 'staff')->get(route('admin.support.tickets.transcript', $this->ticket))->assertForbidden();

    Http::assertNothingSent();
    expect(StaffActivity::where('action', 'support.transcript.read')->count())->toBe(0);
});

it('needs the right to see tickets as well', function () {
    StaffAccess::sync();
    $role = Role::create(['name' => 'Only transcripts '.uniqid(), 'guard_name' => 'staff']);
    $role->givePermissionTo('read support transcripts');
    $staff = Staff::factory()->create();
    $staff->assignRole($role);
    fakeTranscript();

    $this->actingAs($staff, 'staff')->get(route('admin.support.tickets.transcript', $this->ticket))->assertForbidden();

    Http::assertNothingSent();
});

it('shows the chat with the member\'s words marked as theirs, and the cards the assistant showed', function () {
    fakeTranscript();

    $this->actingAs(transcriptReader(), 'staff')->get(route('admin.support.tickets.transcript', $this->ticket))
        ->assertOk()->assertSee('The member wrote')->assertSee('my payment is stuck')->assertSee('The assistant said')
        ->assertSee('It needs a person.')->assertSee('MHX1')->assertSee('Wanjiru Member');
});

it('draws everything escaped, so a member cannot put markup or instructions on a staff screen', function () {
    fakeTranscript(['messages' => [['role' => 'member', 'text' => '<script>alert(1)</script> <img src=x onerror=alert(2)>', 'at' => '2026-10-07T10:00:00+03:00', 'cards' => [['type' => '<b>payment</b>', 'reference' => '<i>x</i>']]]]]);

    $html = $this->actingAs(transcriptReader(), 'staff')->get(route('admin.support.tickets.transcript', $this->ticket))->assertOk()->getContent();

    expect($html)->not->toContain('<script>alert(1)')->not->toContain('<img src=x')->not->toContain('<b>payment</b>')->not->toContain('<i>x</i>')
        ->and($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
});

it('records every opening, before the chat is fetched, with who and which ticket but not what was said', function () {
    fakeTranscript();
    $staff = transcriptReader();

    $this->actingAs($staff, 'staff')->get(route('admin.support.tickets.transcript', $this->ticket))->assertOk();
    $this->get(route('admin.support.tickets.transcript', $this->ticket))->assertOk();

    $logged = StaffActivity::where('action', 'support.transcript.read')->get();
    expect($logged)->toHaveCount(2)
        ->and($logged[0]->staff_id)->toBe($staff->id)
        ->and($logged[0]->subject_id)->toBe($this->ticket->id)
        ->and(json_encode($logged[0]->toArray()))->not->toContain('my payment is stuck');
});

it('leaves the trace even when the assistant cannot be reached, and says so kindly', function () {
    Http::fake(['agent.test/*' => fn () => throw new ConnectionException('down')]);

    $this->actingAs(transcriptReader(), 'staff')->get(route('admin.support.tickets.transcript', $this->ticket))
        ->assertOk()->assertSee('could not be reached');

    expect(StaffActivity::where('action', 'support.transcript.read')->count())->toBe(1);
});

it('says when the assistant no longer has the chat, and when it answers with an error', function () {
    fakeTranscript(404);
    $this->actingAs(transcriptReader(), 'staff')->get(route('admin.support.tickets.transcript', $this->ticket))->assertOk()->assertSee('no longer has this chat');

    fakeTranscript(500);
    $this->get(route('admin.support.tickets.transcript', $this->ticket))->assertOk()->assertSee('could not be reached');
});

it('has nothing to open for a ticket that did not come from a chat, or whose member is gone', function () {
    fakeTranscript();
    $plain = app(TicketService::class)->open(User::factory()->create(), SupportTicketCategory::Other, 'No chat');
    $staff = transcriptReader();

    $this->actingAs($staff, 'staff')->get(route('admin.support.tickets.transcript', $plain))->assertNotFound();

    $this->ticket->forceFill(['requester_id' => null])->save();
    $this->get(route('admin.support.tickets.transcript', $this->ticket))->assertNotFound();

    Http::assertNothingSent();
    expect(StaffActivity::where('action', 'support.transcript.read')->count())->toBe(0);
});

it('asks the assistant with a signed request and a claim for this staff member, this chat and this member, and nothing else', function () {
    fakeTranscript();
    $staff = transcriptReader();

    $this->actingAs($staff, 'staff')->get(route('admin.support.tickets.transcript', $this->ticket))->assertOk();

    Http::assertSent(function (Request $request) use ($staff) {
        $h = fn (string $name) => $request->header($name)[0] ?? null;
        [$head, $payload, $sig] = explode('.', $h('X-Support-Staff-Claim'));
        $claims = json_decode(transcriptB64Decode($payload), true);

        $canonical = implode("\n", ['GET', '/v1/transcripts/'.TRANSCRIPT_CONVERSATION, $h('X-Support-Timestamp'), $h('X-Support-Nonce'), hash('sha256', '')]);

        return $request->method() === 'GET'
            && $request->url() === 'http://agent.test/v1/transcripts/'.TRANSCRIPT_CONVERSATION
            && hash_equals(hash_hmac('sha256', $canonical, 's'), $h('X-Support-Signature'))
            && sodium_crypto_sign_verify_detached(transcriptB64Decode($sig), "$head.$payload", $this->publicKey)
            && $claims['aud'] === 'support-staff' && $claims['scope'] === 'support:transcript:read'
            && $claims['sub'] === (string) $staff->id && $claims['conv'] === TRANSCRIPT_CONVERSATION && $claims['member'] === (string) $this->member->id
            && $claims['exp'] - $claims['iat'] === 60
            && ! $request->hasHeader('X-Support-User-Context') && ! $request->hasHeader('X-Support-Read-Claim');
    });
});

it('takes the chat from the ticket, never from the address or the request', function () {
    fakeTranscript();
    $other = app(TicketService::class)->open(User::factory()->create(), SupportTicketCategory::Other, 'Other', conversationId: '9d0f5b1e-8d1a-4d6b-9a52-3c1e5b9d2f99');

    $this->actingAs(transcriptReader(), 'staff')->get(route('admin.support.tickets.transcript', $this->ticket).'?conversation=9d0f5b1e-8d1a-4d6b-9a52-3c1e5b9d2f99&member='.$other->requester_id)->assertOk();

    Http::assertSent(fn (Request $r) => str_contains($r->url(), TRANSCRIPT_CONVERSATION) && ! str_contains($r->url(), '9d0f5b1e'));
});

it('puts the link on the ticket screen only for staff who may read the chat, and only when there is one', function () {
    fakeTranscript();
    $url = route('admin.support.tickets.transcript', $this->ticket);

    $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.support.tickets.show', $this->ticket))->assertOk()->assertDontSee($url);
    $this->actingAs(transcriptReader(), 'staff')->get(route('admin.support.tickets.show', $this->ticket))->assertOk()->assertSee($url);

    $plain = app(TicketService::class)->open(User::factory()->create(), SupportTicketCategory::Other, 'No chat');
    $this->get(route('admin.support.tickets.show', $plain))->assertOk()->assertDontSee('Read the chat');
});

it('cannot be used to read the chat by a member through any member route', function () {
    fakeTranscript();

    $this->actingAs($this->member)->get('/admin/support/'.$this->ticket->getRouteKey().'/transcript')->assertRedirect();

    Http::assertNothingSent();
});
