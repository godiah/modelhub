<?php

use App\Enums\SupportTicketCategory;
use App\Models\Staff;
use App\Models\StaffActivity;
use App\Models\SupportSavedReply;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Support\Tickets\SavedReplies;
use App\Services\Support\Tickets\TicketService;
use App\Services\Support\Tickets\TicketTargets;
use App\Support\Money;
use App\Support\Settings\FeePolicy;
use App\Support\Staff\StaffAccess;
use Spatie\Permission\Models\Role;

/*
 * Saved replies: the team's reusable answers. Shared ones are for everyone who answers tickets, personal ones for their owner alone (someone else's does
 * not exist as far as they can tell), placeholders are checked on save and filled from the ticket on use, and every change is in the activity log.
 */

function replyTicket(?User $member = null): SupportTicket
{
    return app(TicketService::class)->open($member ?? User::factory()->create(['name' => 'Wanjiru Member']), SupportTicketCategory::PaymentIssue, 'I paid but I have no licence');
}

function savedReply(array $over = []): SupportSavedReply
{
    return SupportSavedReply::create($over + ['title' => 'Payment needs review', 'topic' => 'Payments', 'body' => "Hi {member_name},\n\nWe are looking at {reference}.\n\n{staff_name}"]);
}

function replyWriter(string $name = 'Grace Wambui'): Staff
{
    $staff = staffWith('Support');
    $staff->update(['name' => $name]);

    return $staff;
}

function replyViewer(): Staff
{
    StaffAccess::sync();
    $role = Role::create(['name' => 'Ticket viewer '.uniqid(), 'guard_name' => 'staff']);
    $role->givePermissionTo('view support tickets');
    $staff = Staff::factory()->create();
    $staff->assignRole($role);

    return $staff;
}

it('is for the people who answer requests, and nobody else', function () {
    $reply = savedReply();

    $this->get(route('admin.support.replies.index'))->assertRedirect();
    $this->actingAs(User::factory()->create())->get(route('admin.support.replies.index'))->assertRedirect();

    foreach ([replyViewer(), staffWith('Auditor')] as $outsider) {
        $this->actingAs($outsider, 'staff');
        $this->get(route('admin.support.replies.index'))->assertForbidden();
        $this->post(route('admin.support.replies.store'), ['title' => 'x y z', 'topic' => 'General', 'body' => 'Hello there, friend', 'scope' => 'team'])->assertForbidden();
        $this->put(route('admin.support.replies.update', $reply->id), ['title' => 'Changed', 'topic' => 'General', 'body' => 'Hello there, friend'])->assertForbidden();
        $this->delete(route('admin.support.replies.destroy', $reply->id))->assertForbidden();
    }

    expect($reply->fresh()->title)->toBe('Payment needs review')->and(SupportSavedReply::count())->toBe(1);
});

it('lists the replies by topic and starts on the editor when there are none', function () {
    $this->actingAs(replyWriter(), 'staff')->get(route('admin.support.replies.index'))->assertOk()->assertSee('No saved replies yet')->assertSee('New reply');

    savedReply(['title' => 'Refund: how it is paid back', 'topic' => 'Refunds']);
    savedReply();

    $this->get(route('admin.support.replies.index'))->assertOk()->assertSee('Refund: how it is paid back')->assertSee('Payment needs review')->assertSee('Pick a reply on the left');
});

it('creates a shared reply and a personal one, and logs both', function () {
    $writer = replyWriter();
    $this->actingAs($writer, 'staff');
    $fields = ['topic' => 'Payments', 'body' => "Hi {member_name},\n\nThanks for writing. {staff_name}"];

    $this->post(route('admin.support.replies.store'), $fields + ['title' => 'Team reply', 'scope' => 'team'])->assertRedirect();
    $this->post(route('admin.support.replies.store'), $fields + ['title' => 'My own reply', 'scope' => 'me'])->assertRedirect();

    $team = SupportSavedReply::where('title', 'Team reply')->first();
    $mine = SupportSavedReply::where('title', 'My own reply')->first();
    expect($team->owner_id)->toBeNull()->and($team->updated_by)->toBe($writer->id)
        ->and($mine->owner_id)->toBe($writer->id)
        ->and(StaffActivity::where('action', 'support.reply.created')->count())->toBe(2);
});

it('refuses a placeholder that does not exist, and a reply that is too short, with nothing saved', function () {
    $this->actingAs(replyWriter(), 'staff');
    $good = ['title' => 'A reply', 'topic' => 'General', 'body' => 'Hello {member_name}, thanks.', 'scope' => 'team'];

    $this->post(route('admin.support.replies.store'), ['body' => 'Hello {member_nmae}, your {balance} is fine'] + $good)->assertSessionHasErrors('body');
    $this->post(route('admin.support.replies.store'), ['body' => 'Hi'] + $good)->assertSessionHasErrors('body');
    $this->post(route('admin.support.replies.store'), ['topic' => 'Secrets'] + $good)->assertSessionHasErrors('topic');
    $this->post(route('admin.support.replies.store'), ['scope' => null] + $good)->assertSessionHasErrors('scope');
    $this->post(route('admin.support.replies.store'), ['title' => 'x'] + $good)->assertSessionHasErrors('title');
    $this->post(route('admin.support.replies.store'), ['body' => str_repeat('a', 4001)] + $good)->assertSessionHasErrors('body');

    expect(SupportSavedReply::count())->toBe(0);
});

it('lets the team improve a shared reply, logs it, and does not let anyone change who can see it', function () {
    $reply = savedReply();
    $other = replyWriter('Joseph Mwangi');

    $this->actingAs($other, 'staff')->put(route('admin.support.replies.update', $reply->id), ['title' => 'Better title', 'topic' => 'Refunds', 'body' => 'Hi {member_name}, a better answer.'])->assertRedirect();

    $reply->refresh();
    expect($reply->title)->toBe('Better title')->and($reply->topic)->toBe('Refunds')->and($reply->updated_by)->toBe($other->id)->and($reply->owner_id)->toBeNull()
        ->and(StaffActivity::where('action', 'support.reply.updated')->count())->toBe(1);

    // choosing "only me" later is refused: it is decided once, when the reply is made
    $this->put(route('admin.support.replies.update', $reply->id), ['title' => 'Better title', 'topic' => 'Refunds', 'body' => 'Hi {member_name}, a better answer.', 'scope' => 'me'])->assertSessionHasErrors('scope');
    expect($reply->fresh()->owner_id)->toBeNull();
});

it('keeps a personal reply to its owner: for anyone else it does not exist', function () {
    $owner = replyWriter('Grace Wambui');
    $mine = savedReply(['title' => 'Grace private sign-off', 'owner_id' => $owner->id]);
    $stranger = replyWriter('Joseph Mwangi');

    $this->actingAs($stranger, 'staff');
    $this->get(route('admin.support.replies.index'))->assertOk()->assertDontSee('Grace private sign-off');
    $this->get(route('admin.support.replies.index', ['reply' => $mine->id]))->assertOk()->assertDontSee('Grace private sign-off');

    $missing = $this->put(route('admin.support.replies.update', 999999), ['title' => 'Changed', 'topic' => 'General', 'body' => 'Hello there, friend']);
    $theirs = $this->put(route('admin.support.replies.update', $mine->id), ['title' => 'Changed', 'topic' => 'General', 'body' => 'Hello there, friend']);
    expect($theirs->status())->toBe($missing->status())->toBe(404);
    $this->delete(route('admin.support.replies.destroy', $mine->id))->assertNotFound();

    expect($mine->fresh()->title)->toBe('Grace private sign-off');

    $this->actingAs($owner, 'staff')->get(route('admin.support.replies.index', ['reply' => $mine->id]))->assertOk()->assertSee('Grace private sign-off');
});

it('deletes a reply and logs it', function () {
    $reply = savedReply();

    $this->actingAs(replyWriter(), 'staff')->delete(route('admin.support.replies.destroy', $reply->id))->assertRedirect(route('admin.support.replies.index'));

    expect(SupportSavedReply::count())->toBe(0)->and(StaffActivity::where('action', 'support.reply.deleted')->count())->toBe(1);
});

it('draws a reply escaped, so markup in one cannot run in the editor', function () {
    $reply = savedReply(['title' => '<b>bold</b> title', 'body' => 'Hi {member_name} <script>alert(1)</script> <img src=x onerror=alert(2)>']);

    $html = $this->actingAs(replyWriter(), 'staff')->get(route('admin.support.replies.index', ['reply' => $reply->id]))->assertOk()->getContent();

    expect($html)->not->toContain('<script>alert(1)')->not->toContain('<img src=x')->not->toContain('<b>bold</b>');
});

/** The replies the ticket screen hands the picker, as the browser receives them (already filled in for that ticket). */
function pickerReplies(string $html): array
{
    $html = html_entity_decode($html, ENT_QUOTES);
    preg_match("/replies: JSON\.parse\('(.*?)'\), chosen/s", $html, $found);

    // what the browser does: the attribute is a JS string holding JSON, so read the string first, then the JSON in it
    return json_decode((string) json_decode('"'.($found[1] ?? '[]').'"'), true) ?? [];
}

it('fills a reply in for the ticket: their first name, the reference, your first name, the live reply time and minimum withdrawal', function () {
    $ticket = replyTicket();
    savedReply(['body' => 'Hi {member_name}, {reference}. We aim to reply within {first_reply_time}. Minimum {min_withdrawal}. {staff_name}']);

    $html = $this->actingAs(replyWriter('Grace Wambui'), 'staff')->get(route('admin.support.tickets.show', $ticket))->assertOk()->getContent();

    expect(pickerReplies($html))->toHaveCount(1)
        ->and(pickerReplies($html)[0]['body'])->toBe('Hi Wanjiru, '.$ticket->reference.'. We aim to reply within '.TicketTargets::firstReplyAmount($ticket->severity).'. Minimum '.Money::formatMinor(FeePolicy::minPayoutMinor(), 0).'. Grace')
        ->and($html)->toContain('Insert a saved reply');
});

it('follows the service levels: change the target and the reply says the new one', function () {
    $ticket = replyTicket();
    savedReply(['body' => 'We aim to reply within {first_reply_time}.']);
    $severity = $ticket->severity->value;
    config(["support.tickets.targets.{$severity}.first_response" => 180]);

    $html = $this->actingAs(replyWriter(), 'staff')->get(route('admin.support.tickets.show', $ticket))->assertOk()->getContent();

    expect(pickerReplies($html)[0]['body'])->toBe('We aim to reply within 3 business hours.');
});

it('leaves a placeholder it does not know as written, and says "there" when the member has gone', function () {
    $ticket = replyTicket();
    $ticket->forceFill(['requester_id' => null])->save();
    $ticket->refresh();

    $text = app(SavedReplies::class)->render('Hi {member_name}, {made_up} and {reference}', $ticket, replyWriter('Grace Wambui'));

    expect($text)->toBe('Hi there, {made_up} and '.$ticket->reference);
    expect(SavedReplies::unknown('Hi {member_name} {made_up} {made_up} {other}'))->toBe(['made_up', 'other']);
});

it('offers a person the team\'s replies and their own, never someone else\'s, and nothing to someone who cannot reply', function () {
    $ticket = replyTicket();
    $me = replyWriter('Grace Wambui');
    savedReply(['title' => 'Shared one']);
    savedReply(['title' => 'Grace only', 'owner_id' => $me->id]);
    savedReply(['title' => 'Joseph only', 'owner_id' => replyWriter('Joseph Mwangi')->id]);

    $this->actingAs($me, 'staff')->get(route('admin.support.tickets.show', $ticket))->assertOk()->assertSee('Shared one')->assertSee('Grace only')->assertDontSee('Joseph only');
    $this->actingAs(replyViewer(), 'staff')->get(route('admin.support.tickets.show', $ticket))->assertOk()->assertDontSee('Shared one')->assertDontSee('Insert a saved reply');
});

it('counts a saved reply as used only when a reply that began from it is really sent', function () {
    $ticket = replyTicket();
    $writer = replyWriter();
    $shared = savedReply();
    $theirs = savedReply(['title' => 'Joseph only', 'owner_id' => replyWriter('Joseph Mwangi')->id]);
    $this->actingAs($writer, 'staff');

    // nothing to send: not used
    $this->post(route('admin.support.tickets.reply', $ticket), ['body' => '', 'saved_reply' => $shared->id]);
    expect($shared->fresh()->uses)->toBe(0);

    // sent: used once; and a reply the person cannot see is never counted, however the id got there
    $this->post(route('admin.support.tickets.reply', $ticket), ['body' => 'Hi Wanjiru, we are looking.', 'saved_reply' => $shared->id])->assertRedirect();
    $this->post(route('admin.support.tickets.reply', $ticket), ['body' => 'Another message to them.', 'saved_reply' => $theirs->id])->assertRedirect();

    expect($shared->fresh()->uses)->toBe(1)->and($theirs->fresh()->uses)->toBe(0)->and($ticket->messages()->where('sender', 'staff')->count())->toBe(2);
});
