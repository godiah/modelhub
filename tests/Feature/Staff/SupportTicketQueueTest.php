<?php

use App\Enums\SupportResolutionTag;
use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketSeverity;
use App\Enums\SupportTicketStatus;
use App\Models\Staff;
use App\Models\StaffActivity;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketMemberRepliedNotification;
use App\Notifications\SupportTicketOpenedNotification;
use App\Services\Admin\StaffSearchService;
use App\Services\Support\Tickets\TicketService;
use App\Support\Navigation\StaffMenu;
use App\Support\Staff\StaffAccess;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

/*
 * The staff side of support tickets: the queue (most urgent and most overdue first), the ticket screen, everything staff can do to a ticket and who
 * is allowed to, and that nothing a member wrote is ever drawn as markup.
 */

function makeTicket(?User $member = null, string $summary = 'I paid but I have no licence', SupportTicketCategory $category = SupportTicketCategory::PaymentIssue): SupportTicket
{
    return app(TicketService::class)->open($member ?? User::factory()->create(), $category, $summary);
}

/** A staff member who may see the queue but not answer. */
function viewOnlyStaff(): Staff
{
    StaffAccess::sync();
    $role = Role::create(['name' => 'Ticket viewer '.uniqid(), 'guard_name' => 'staff']);
    $role->givePermissionTo('view support tickets');
    $staff = Staff::factory()->create();
    $staff->assignRole($role);

    return $staff;
}

it('keeps the queue away from members and from staff without the permission', function () {
    $ticket = makeTicket();

    $this->actingAs(User::factory()->create())->get('/admin/support')->assertRedirect();

    $nobody = staffWith('Auditor');
    $this->actingAs($nobody, 'staff')->get(route('admin.support.tickets.index'))->assertForbidden();
    $this->actingAs($nobody, 'staff')->get(route('admin.support.tickets.show', $ticket))->assertForbidden();
});

it('lets the Support role see the queue and open a ticket', function () {
    $ticket = makeTicket();
    $support = actingAsStaff('Support');

    $this->get(route('admin.support.tickets.index'))->assertOk()->assertSee($ticket->reference)->assertSee('I paid but I have no licence');
    $this->get(route('admin.support.tickets.show', $ticket))->assertOk()->assertSee($ticket->reference)->assertSee('Reply to the member');
});

it('shows a view-only member of staff the ticket, but no way to answer it, and refuses their attempts to', function () {
    $ticket = makeTicket();
    $viewer = viewOnlyStaff();

    $this->actingAs($viewer, 'staff')->get(route('admin.support.tickets.show', $ticket))->assertOk()->assertDontSee('Reply to the member')->assertDontSee('Mark as resolved');

    foreach (['reply' => ['body' => 'hi'], 'note' => ['body' => 'hi'], 'assign' => [], 'severity' => ['severity' => 'urgent'], 'resolve' => ['tag' => 'other'], 'close' => []] as $action => $data) {
        $this->actingAs($viewer, 'staff')->post(route("admin.support.tickets.{$action}", $ticket), $data)->assertForbidden();
    }

    expect($ticket->refresh()->messages)->toHaveCount(1)->and($ticket->status)->toBe(SupportTicketStatus::Open);
});

it('orders the queue by urgency first and then by what is due soonest', function () {
    $low = makeTicket(summary: 'A general question for you', category: SupportTicketCategory::Other);
    $normal = makeTicket(summary: 'A dispute question here', category: SupportTicketCategory::DisputeHelp);
    $high = makeTicket(summary: 'I cannot sign in at all', category: SupportTicketCategory::AccountAccess);
    actingAsStaff('Support');

    $this->get(route('admin.support.tickets.index'))->assertSeeInOrder([$high->reference, $normal->reference, $low->reference]);
});

it('filters the queue: needs staff, mine, waiting for the member, overdue, resolved', function () {
    $service = app(TicketService::class);
    $support = actingAsStaff('Support');
    $fresh = makeTicket();
    $waiting = makeTicket(summary: 'Waiting on the member');
    $service->staffReply($waiting, $support, 'Please send the M-Pesa code.');
    $mine = makeTicket(summary: 'This one is mine');
    $service->assign($mine, $support, $support);
    $done = makeTicket(summary: 'Already finished here');
    $service->resolve($done, $support, SupportResolutionTag::Other);
    $late = makeTicket(summary: 'This one is late');
    $late->update(['first_response_due_at' => now()->subHour()]);

    $see = fn (string $tab) => $this->get(route('admin.support.tickets.index', ['tab' => $tab]));

    $see('queue')->assertSee($fresh->reference)->assertSee($mine->reference)->assertDontSee($waiting->reference)->assertDontSee($done->reference);
    $see('mine')->assertSee($mine->reference)->assertDontSee($fresh->reference);
    $see('waiting')->assertSee($waiting->reference)->assertDontSee($fresh->reference);
    $see('overdue')->assertSee($late->reference)->assertDontSee($fresh->reference);
    $see('resolved')->assertSee($done->reference)->assertDontSee($fresh->reference);
    $see('all')->assertSee($done->reference)->assertSee($waiting->reference);
});

it('searches by reference, member name and words in the request', function () {
    $member = User::factory()->create(['name' => 'Wanjiru Kamau']);
    $one = makeTicket($member, 'My M-Pesa receipt is missing');
    $two = makeTicket(summary: 'Something about a licence');
    actingAsStaff('Support');

    $this->get(route('admin.support.tickets.index', ['tab' => 'all', 'q' => 'Wanjiru']))->assertSee($one->reference)->assertDontSee($two->reference);
    $this->get(route('admin.support.tickets.index', ['tab' => 'all', 'q' => $two->reference]))->assertSee($two->reference)->assertDontSee($one->reference);
    $this->get(route('admin.support.tickets.index', ['tab' => 'all', 'q' => 'receipt']))->assertSee($one->reference)->assertDontSee($two->reference);
});

it('draws what a member wrote as plain text, never as markup', function () {
    $evil = '<script>alert("x")</script><img src=x onerror=alert(1)> **bold** [link](javascript:alert(1))';
    $member = User::factory()->create(['name' => '<b>Mallory</b>']);
    $ticket = app(TicketService::class)->open($member, SupportTicketCategory::Other, $evil, evidence: ['note' => '<script>evidence()</script>']);
    actingAsStaff('Support');

    foreach ([route('admin.support.tickets.index', ['tab' => 'all']), route('admin.support.tickets.show', $ticket)] as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        expect($html)->not->toContain('<script>alert')->not->toContain('<img src=x')->not->toContain('<script>evidence')->not->toContain('<b>Mallory')
            ->toContain('&lt;script&gt;');
    }
});

it('lets staff reply, and tells the member\'s side of the thread it moved to them', function () {
    $ticket = makeTicket();
    actingAsStaff('Support');

    $this->post(route('admin.support.tickets.reply', $ticket), ['body' => 'Thanks, we are checking.'])->assertRedirect();

    $ticket->refresh();
    expect($ticket->status)->toBe(SupportTicketStatus::PendingMember)->and($ticket->messages->last()->body)->toBe('Thanks, we are checking.')
        ->and($ticket->first_responded_at)->not->toBeNull();
});

it('keeps a note staff-only and shows it marked on the ticket screen', function () {
    $ticket = makeTicket();
    actingAsStaff('Support');

    $this->post(route('admin.support.tickets.note', $ticket), ['body' => 'Receipt matches the gateway.'])->assertRedirect();

    $this->get(route('admin.support.tickets.show', $ticket))->assertSee('Receipt matches the gateway.')->assertSee('the member cannot see this');
    expect($ticket->refresh()->memberMessages)->toHaveCount(1);
});

it('refuses an empty or oversized reply and says why', function () {
    $ticket = makeTicket();
    actingAsStaff('Support');

    $this->post(route('admin.support.tickets.reply', $ticket), ['body' => ''])->assertRedirect(); // nothing to send: refused with a message, nothing saved
    $this->post(route('admin.support.tickets.reply', $ticket), ['body' => str_repeat('a', 4001)])->assertSessionHasErrors('body');

    expect($ticket->refresh()->messages)->toHaveCount(1);
});

it('assigns a ticket to someone who answers tickets, and refuses someone who does not', function () {
    $ticket = makeTicket();
    $me = actingAsStaff('Support');
    $outsider = staffWith('Auditor');

    $this->post(route('admin.support.tickets.assign', $ticket), ['assignee_id' => $me->id])->assertRedirect();
    expect($ticket->refresh()->assignee_id)->toBe($me->id);

    $this->post(route('admin.support.tickets.assign', $ticket), ['assignee_id' => $outsider->id])->assertRedirect();
    expect($ticket->refresh()->assignee_id)->toBe($me->id);

    $this->post(route('admin.support.tickets.assign', $ticket), ['assignee_id' => null])->assertRedirect();
    expect($ticket->refresh()->assignee_id)->toBeNull();
});

it('changes urgency, rejecting a level that does not exist', function () {
    $ticket = makeTicket(category: SupportTicketCategory::Other);
    actingAsStaff('Support');

    $this->post(route('admin.support.tickets.severity', $ticket), ['severity' => 'urgent'])->assertRedirect();
    expect($ticket->refresh()->severity)->toBe(SupportTicketSeverity::Urgent);

    $this->post(route('admin.support.tickets.severity', $ticket), ['severity' => 'catastrophic'])->assertSessionHasErrors('severity');
    expect($ticket->refresh()->severity)->toBe(SupportTicketSeverity::Urgent);
});

it('resolves with a tag, and refuses one that is not on the list', function () {
    $ticket = makeTicket();
    actingAsStaff('Support');

    $this->post(route('admin.support.tickets.resolve', $ticket), ['tag' => 'invented'])->assertSessionHasErrors('tag');
    expect($ticket->refresh()->status)->toBe(SupportTicketStatus::Open);

    $this->post(route('admin.support.tickets.resolve', $ticket), ['tag' => 'doc_missing', 'body' => 'It is fixed now.'])->assertRedirect();
    $ticket->refresh();

    expect($ticket->status)->toBe(SupportTicketStatus::Resolved)->and($ticket->resolution_tag)->toBe(SupportResolutionTag::DocMissing)->and($ticket->messages->last()->body)->toBe('It is fixed now.');
});

it('closes a resolved ticket and then shows no way to answer it', function () {
    $ticket = makeTicket();
    actingAsStaff('Support');
    $this->post(route('admin.support.tickets.resolve', $ticket), ['tag' => 'other']);

    $this->post(route('admin.support.tickets.close', $ticket))->assertRedirect();

    expect($ticket->refresh()->status)->toBe(SupportTicketStatus::Closed);
    $this->get(route('admin.support.tickets.show', $ticket))->assertDontSee('Send reply');
});

it('tells everyone who answers tickets when one is filed, and whoever has it when the member writes back', function () {
    Notification::fake();
    $support = staffWith('Support');
    $outsider = staffWith('Auditor');
    $member = User::factory()->create();

    $ticket = app(TicketService::class)->open($member, SupportTicketCategory::PaymentIssue, 'I paid but I have no licence');

    Notification::assertSentTo($support, SupportTicketOpenedNotification::class);
    Notification::assertNotSentTo($outsider, SupportTicketOpenedNotification::class);

    app(TicketService::class)->assign($ticket, $support, $support);
    app(TicketService::class)->staffReply($ticket, $support, 'Looking.');
    $other = staffWith('Support');
    app(TicketService::class)->memberReply($ticket->refresh(), $member, 'Any news?');

    Notification::assertSentTo($support, SupportTicketMemberRepliedNotification::class);
    Notification::assertNotSentTo($other, SupportTicketMemberRepliedNotification::class);
});

it('carries no member-written text in the mail it sends staff', function () {
    $support = staffWith('Support');
    $ticket = makeTicket(summary: 'SECRET-WORDS-FROM-THE-MEMBER');

    $mail = (new SupportTicketOpenedNotification($ticket))->toMail($support);

    expect(json_encode($mail->toArray()).$mail->subject)->not->toContain('SECRET-WORDS-FROM-THE-MEMBER');
});

it('puts the number waiting on the menu badge and finds tickets with the staff search', function () {
    $one = makeTicket(summary: 'Find me by these words');
    makeTicket(summary: 'Another one waiting here');
    $support = staffWith('Support');

    expect(StaffMenu::count('tickets'))->toBe(2);

    $found = collect(app(StaffSearchService::class)->search($support, 'Find me'))->flatMap(fn ($g) => collect($g['items'])->pluck('url'))->all();

    expect($found)->toContain(route('admin.support.tickets.show', $one));
});

it('leaves the audit trail of what staff did', function () {
    $ticket = makeTicket();
    $support = actingAsStaff('Support');

    $this->post(route('admin.support.tickets.reply', $ticket), ['body' => 'On it.']);
    $this->post(route('admin.support.tickets.resolve', $ticket), ['tag' => 'other']);

    $actions = StaffActivity::where('subject_type', 'SupportTicket')->where('subject_id', $ticket->id)->pluck('action')->all();

    expect($actions)->toBe(['support.ticket.replied', 'support.ticket.resolved']);
});
