<?php

use App\Enums\SupportResolutionTag;
use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketSeverity;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketReceivedNotification;
use App\Notifications\SupportTicketRepliedNotification;
use App\Notifications\SupportTicketResolvedNotification;
use App\Services\Support\Tickets\TicketService;
use App\Services\Support\Tickets\TicketTargets;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

/*
 * "My requests": a member sees their own support tickets and the thread (never staff's internal notes), can reply, and is told by email when it is
 * received, answered and resolved: without the staff reply itself in the email.
 */

beforeEach(function () {
    $this->member = User::factory()->create();
    $this->tickets = app(TicketService::class);
    $this->staff = staffWith('Support');
    RateLimiter::clear('support-ticket-reply:'.$this->member->id);
});

function ticketFor(User $member, string $summary = 'I paid but I have no licence', SupportTicketCategory $category = SupportTicketCategory::PaymentIssue): SupportTicket
{
    return app(TicketService::class)->open($member, $category, $summary);
}

it('keeps the pages for signed-in members only', function () {
    $ticket = ticketFor($this->member);

    $this->get(route('support.requests.index'))->assertRedirect();
    $this->get(route('support.requests.show', $ticket))->assertRedirect();
    $this->post(route('support.requests.reply', $ticket), ['body' => 'hi'])->assertRedirect();
});

it('lists the member\'s own requests, open first and resolved on their own tab', function () {
    $open = ticketFor($this->member, 'This one is still open');
    $done = ticketFor($this->member, 'This one is done');
    $this->tickets->resolve($done, $this->staff, SupportResolutionTag::Other);
    $theirs = ticketFor(User::factory()->create(), 'Somebody else\'s private matter');
    $this->actingAs($this->member);

    $this->get(route('support.requests.index'))->assertOk()->assertSee($open->reference)->assertDontSee($done->reference)->assertDontSee($theirs->reference)->assertDontSee('private matter');
    $this->get(route('support.requests.index', ['tab' => 'resolved']))->assertOk()->assertSee($done->reference)->assertDontSee($open->reference);
});

it('shows an empty list kindly', function () {
    $this->actingAs($this->member)->get(route('support.requests.index'))->assertOk()->assertSee('No requests yet');
});

it('shows the thread, with staff replies, but never an internal note', function () {
    $ticket = ticketFor($this->member);
    $this->tickets->staffReply($ticket, $this->staff, 'We are checking your payment.');
    $this->tickets->note($ticket, $this->staff, 'INTERNAL: suspect a duplicate licence');

    $this->actingAs($this->member)->get(route('support.requests.show', $ticket))->assertOk()
        ->assertSee('I paid but I have no licence')->assertSee('We are checking your payment.')->assertDontSee('INTERNAL')->assertDontSee('duplicate licence');
});

it('answers someone else\'s request and a missing one exactly alike', function () {
    $theirs = ticketFor(User::factory()->create());
    $this->actingAs($this->member);

    $other = $this->get(route('support.requests.show', $theirs));
    $missing = $this->get('/support/requests/SUP-999999');

    $other->assertNotFound();
    $missing->assertNotFound();
    expect($other->status())->toBe($missing->status());
    $this->post(route('support.requests.reply', $theirs), ['body' => 'let me in'])->assertNotFound();
    expect($theirs->refresh()->messages)->toHaveCount(1);
});

it('does not take a reference that is not shaped like one', function () {
    $this->actingAs($this->member)->get('/support/requests/anything-else')->assertNotFound();
});

it('draws what was written as plain text', function () {
    $evil = '<script>alert(1)</script><img src=x onerror=alert(1)>';
    $ticket = $this->tickets->open($this->member, SupportTicketCategory::Other, $evil);
    $this->tickets->staffReply($ticket, $this->staff, '<b>bold</b><script>alert(2)</script>');
    $this->actingAs($this->member);

    foreach ([route('support.requests.index'), route('support.requests.show', $ticket)] as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        expect($html)->not->toContain('<script>alert')->not->toContain('<img src=x')->not->toContain('<b>bold</b>')->toContain('&lt;script&gt;');
    }
});

it('lets the member reply, and moves the ticket to staff', function () {
    $ticket = ticketFor($this->member);
    $this->tickets->staffReply($ticket, $this->staff, 'Can you confirm the phone number?');
    $this->actingAs($this->member);

    $this->post(route('support.requests.reply', $ticket), ['body' => 'It ends in 482.'])->assertRedirect(route('support.requests.show', $ticket));

    $ticket->refresh();
    expect($ticket->status)->toBe(SupportTicketStatus::PendingStaff)->and($ticket->messages->last()->body)->toBe('It ends in 482.')->and($ticket->messages->last()->sender)->toBe('member');
});

it('refuses an empty or oversized reply with a message the member can read', function () {
    $ticket = ticketFor($this->member);
    $this->actingAs($this->member);

    $this->post(route('support.requests.reply', $ticket), ['body' => ''])->assertRedirect(); // nothing to send: refused with a message, nothing saved
    $this->post(route('support.requests.reply', $ticket), ['body' => str_repeat('a', 4001)])->assertSessionHasErrors('body');
    expect($ticket->refresh()->messages)->toHaveCount(1);
});

it('reopens a resolved request when the member writes back, and refuses once it is closed or long past', function () {
    $ticket = ticketFor($this->member);
    $this->tickets->resolve($ticket, $this->staff, SupportResolutionTag::DocMissing);
    $this->actingAs($this->member);

    $this->get(route('support.requests.show', $ticket))->assertSee('Reply to reopen');
    $this->post(route('support.requests.reply', $ticket), ['body' => 'Still not working.'])->assertRedirect();
    expect($ticket->refresh()->status)->toBe(SupportTicketStatus::PendingStaff);

    $this->tickets->resolve($ticket, $this->staff, SupportResolutionTag::Other);
    $this->travel(15)->days();
    $this->flushSession()->actingAs($this->member); // a real session would have timed out over 15 days
    $this->get(route('support.requests.show', $ticket))->assertSee('This request is closed')->assertDontSee('Send reply');
    $this->post(route('support.requests.reply', $ticket), ['body' => 'Hello?'])->assertRedirect();
    expect($ticket->refresh()->messages->last()->body)->not->toBe('Hello?');

    $closed = ticketFor($this->member, 'A different thing entirely');
    $this->tickets->close($closed, $this->staff);
    $this->get(route('support.requests.show', $closed))->assertSee('This request is closed');
});

it('says what to expect, as an aim and not a promise, until staff have replied', function () {
    $ticket = $this->tickets->open($this->member, SupportTicketCategory::PaymentIssue, 'My payment is under review please', [], null);
    $this->actingAs($this->member);

    $this->get(route('support.requests.show', $ticket))->assertSee('We aim to reply')->assertSee('We cannot promise a time yet');

    $this->tickets->staffReply($ticket, $this->staff, 'Hello.');

    $this->get(route('support.requests.show', $ticket))->assertDontSee('We aim to reply')->assertSee('Staff are waiting for your reply');
});

it('words the targets from the config the clocks use', function () {
    config(['support.tickets.targets.high.first_response' => 240, 'support.tickets.targets.normal.first_response' => 600, 'support.tickets.targets.low.first_response' => 1200, 'support.tickets.targets.urgent.first_response' => 60]);

    expect(TicketTargets::firstReply(SupportTicketSeverity::High))->toBe('within 4 business hours')
        ->and(TicketTargets::firstReply(SupportTicketSeverity::Urgent))->toBe('within 1 business hour')
        ->and(TicketTargets::firstReply(SupportTicketSeverity::Normal))->toBe('within 1 business day')
        ->and(TicketTargets::firstReply(SupportTicketSeverity::Low))->toBe('within 2 business days')
        ->and(TicketTargets::hours())->toBe('weekdays, 8am to 6pm EAT');
});

it('tells the member by email when it is received, answered and resolved, with the staff words in none of them', function () {
    Notification::fake();
    $ticket = ticketFor($this->member);
    $this->tickets->staffReply($ticket, $this->staff, 'SECRET-STAFF-WORDS about your money');
    $this->tickets->resolve($ticket, $this->staff, SupportResolutionTag::Other, 'SECRET-LAST-WORDS');

    Notification::assertSentTo($this->member, SupportTicketReceivedNotification::class);
    Notification::assertSentTo($this->member, SupportTicketRepliedNotification::class);
    Notification::assertSentTo($this->member, SupportTicketResolvedNotification::class);

    foreach ([new SupportTicketReceivedNotification($ticket), new SupportTicketRepliedNotification($ticket), new SupportTicketResolvedNotification($ticket)] as $notification) {
        $mail = $notification->toMail($this->member);
        $text = json_encode($mail->toArray()).$mail->subject;

        expect($text)->not->toContain('SECRET-')->and($text)->toContain($ticket->reference)->and($mail->actionUrl)->toEndWith('/support/requests/'.$ticket->reference);
    }
});

it('puts the aim for a first reply in the confirmation email', function () {
    $ticket = ticketFor($this->member);

    $lines = collect((new SupportTicketReceivedNotification($ticket))->toMail($this->member)->introLines)->implode(' ');

    expect($lines)->toContain('We aim to reply')->toContain('weekdays, 8am to 6pm EAT')->toContain($ticket->reference);
});

it('does not tell another member, nor staff, about a member\'s request', function () {
    Notification::fake();
    $other = User::factory()->create();

    ticketFor($this->member);

    Notification::assertNotSentTo($other, SupportTicketReceivedNotification::class);
    Notification::assertNotSentTo($this->staff, SupportTicketReceivedNotification::class);
});

it('shows "My requests" in the member menu only when the assistant is on', function () {
    config(['support.enabled' => false]);
    $this->actingAs($this->member)->get(route('dashboard'))->assertDontSee('My requests');

    config(['support.enabled' => true]);
    $this->actingAs($this->member)->get(route('dashboard'))->assertSee('My requests');
});
