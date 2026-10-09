<?php

use App\Enums\SupportResolutionTag;
use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketSeverity;
use App\Enums\SupportTicketStatus;
use App\Models\Staff;
use App\Models\StaffActivity;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Admin\StaffDashboardService;
use App\Services\Support\Tickets\TicketService;
use App\Support\Staff\BulkActions;
use App\Support\Staff\StaffAccess;
use Carbon\CarbonImmutable;
use Spatie\Permission\Models\Role;

/*
 * The staff tools around the queue: taking several requests on at once, the dashboard's support block, and the service-levels page. They show what the
 * clocks and the queue really hold, never a copy, and never the words a member wrote.
 */

function toolsTicket(string $summary = 'I paid but I have no licence', ?User $member = null): SupportTicket
{
    return app(TicketService::class)->open($member ?? User::factory()->create(), SupportTicketCategory::PaymentIssue, $summary);
}

function toolsViewer(): Staff
{
    StaffAccess::sync();
    $role = Role::create(['name' => 'Ticket viewer '.uniqid(), 'guard_name' => 'staff']);
    $role->givePermissionTo('view support tickets');
    $staff = Staff::factory()->create();
    $staff->assignRole($role);

    return $staff;
}

function bulkAssign(object $test, array $tickets)
{
    return $test->post(route('admin.bulk', 'support.assign'), ['ids' => collect($tickets)->pluck('id')->all()]);
}

// ---- the scopes -----------------------------------------------------------------------------------------------------------

it('knows which requests are overdue and which are due soon, by the first reply and then the resolution', function () {
    $late = toolsTicket();
    $late->update(['first_response_due_at' => now()->subMinute()]);
    $soon = toolsTicket();
    $soon->update(['first_response_due_at' => now()->addMinutes(30)]);
    $later = toolsTicket();
    $later->update(['first_response_due_at' => now()->addHours(5)]);
    $answeredButSlow = toolsTicket();
    $answeredButSlow->update(['status' => SupportTicketStatus::PendingStaff, 'first_responded_at' => now()->subHour(), 'first_response_due_at' => now()->subDay(), 'resolution_due_at' => now()->subMinute()]);
    $answeredInTime = toolsTicket();
    $answeredInTime->update(['status' => SupportTicketStatus::PendingStaff, 'first_responded_at' => now()->subHour(), 'first_response_due_at' => now()->subDay(), 'resolution_due_at' => now()->addDay()]);
    $waitingOnMember = toolsTicket();
    $waitingOnMember->update(['status' => SupportTicketStatus::PendingMember, 'first_response_due_at' => now()->subDay()]);

    expect(SupportTicket::overdue()->pluck('id')->sort()->values()->all())->toBe(collect([$late, $answeredButSlow])->pluck('id')->sort()->values()->all())
        ->and(SupportTicket::dueSoon(120)->pluck('id')->all())->toBe([$soon->id])
        ->and(SupportTicket::dueSoon(600)->pluck('id')->sort()->values()->all())->toBe(collect([$soon, $later])->pluck('id')->sort()->values()->all());
});

// ---- bulk assign ------------------------------------------------------------------------------------------------------------

it('is offered on the queue only to people who can answer requests, and only beside requests nobody has', function () {
    $free = toolsTicket();
    $held = toolsTicket();
    $held->update(['assignee_id' => Staff::factory()->create()->id]);

    expect(BulkActions::PAGES['tickets'])->toBe(['support.assign']);

    $html = $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.support.tickets.index'))->assertOk()->getContent();
    expect($html)->toContain('Select request '.$free->reference)->not->toContain('Select request '.$held->reference);

    $this->actingAs(toolsViewer(), 'staff')->get(route('admin.support.tickets.index'))->assertOk()->assertDontSee('Select request');
});

it('takes the ticked requests on for the person, with the same log entry as doing it one at a time', function () {
    $staff = staffWith('Support');
    $tickets = [toolsTicket(), toolsTicket(), toolsTicket()];
    $this->actingAs($staff, 'staff');

    bulkAssign($this, $tickets)->assertRedirect();

    foreach ($tickets as $ticket) {
        expect($ticket->fresh()->assignee_id)->toBe($staff->id);
    }
    expect(StaffActivity::where('action', 'support.ticket.assigned')->where('staff_id', $staff->id)->count())->toBe(3)
        ->and(StaffActivity::where('action', 'bulk.support.assign')->count())->toBe(1);
});

it('leaves alone anything a colleague has, anything finished, and anything already yours, and says why', function () {
    $staff = staffWith('Support');
    $colleague = Staff::factory()->create(['name' => 'Joseph Mwangi']);
    $free = toolsTicket();
    $theirs = toolsTicket();
    $theirs->update(['assignee_id' => $colleague->id]);
    $done = toolsTicket();
    $done->update(['status' => SupportTicketStatus::Resolved]);
    $mine = toolsTicket();
    $mine->update(['assignee_id' => $staff->id]);
    $this->actingAs($staff, 'staff');

    $response = bulkAssign($this, [$free, $theirs, $done, $mine])->assertRedirect();

    $why = collect(session('bulk_result')['skipped'])->pluck('why', 'name');
    expect($free->fresh()->assignee_id)->toBe($staff->id)
        ->and($theirs->fresh()->assignee_id)->toBe($colleague->id)
        ->and($done->fresh()->assignee_id)->toBeNull()
        ->and($why[$theirs->reference])->toBe('Joseph Mwangi is already handling it.')
        ->and($why[$done->reference])->toBe('It is already finished.')
        ->and($why[$mine->reference])->toBe('You are already handling it.')
        ->and(session('bulk_result')['done'])->toBe([$free->reference]);
});

it('refuses people who cannot answer requests, and assigns nothing', function () {
    $ticket = toolsTicket();

    $this->actingAs(toolsViewer(), 'staff');
    bulkAssign($this, [$ticket])->assertForbidden();
    $this->actingAs(staffWith('Auditor'), 'staff');
    bulkAssign($this, [$ticket])->assertForbidden();

    expect($ticket->fresh()->assignee_id)->toBeNull();
});

// ---- the dashboard ------------------------------------------------------------------------------------------------------------

it('puts the support queue on the dashboard of people who can see it, counted from the real queue', function () {
    $queue = fn (Staff $staff) => collect(app(StaffDashboardService::class)->for($staff)['attention']['queues'])->firstWhere('key', 'tickets');
    toolsTicket();
    toolsTicket();
    toolsTicket()->update(['status' => SupportTicketStatus::PendingMember]);
    toolsTicket()->update(['status' => SupportTicketStatus::Resolved]);

    expect($queue(staffWith('Support'))['count'])->toBe(2)
        ->and($queue(toolsViewer())['count'])->toBe(2)
        ->and($queue(staffWith('Marketplace moderator')))->toBeNull()
        ->and($queue(staffWith('Auditor')))->toBeNull();
});

it('lists the oldest requests that need staff, saying who and how urgent but never the member\'s words, and marks the late and the unclaimed', function () {
    $staff = staffWith('Support');
    $old = toolsTicket('SECRET-WORDS-FROM-THE-MEMBER', User::factory()->create(['name' => 'Wanjiru Member']));
    $old->forceFill(['created_at' => now()->subDays(8), 'first_response_due_at' => now()->subDays(7)])->save();
    $claimed = toolsTicket();
    $claimed->forceFill(['created_at' => now()->subDays(2), 'assignee_id' => $staff->id, 'first_response_due_at' => now()->addHour()])->save();

    $html = $this->actingAs($staff, 'staff')->get(route('admin.dashboard'))->assertOk()->getContent();
    $items = collect(app(StaffDashboardService::class)->for($staff)['attention']['items'])->where('kind', 'Support request')->values();

    expect($items)->toHaveCount(2)
        ->and($items[0]['title'])->toContain($old->reference)->and($items[0]['tag'])->toBe('Overdue')->and($items[0]['tone'])->toBe('red')
        ->and($items[0]['detail'])->toContain('Wanjiru Member')->toContain('Nobody has it yet')
        ->and($items[1]['detail'])->toContain('You have it')->and($items[1]['tag'])->toBeNull()
        ->and($items[0]['url'])->toBe(route('admin.support.tickets.show', $old));
    expect($html)->not->toContain('SECRET-WORDS-FROM-THE-MEMBER');
});

it('shows a person the requests they have, most urgent first, and not anyone else\'s or the finished ones', function () {
    $staff = staffWith('Support');
    $normal = toolsTicket();
    $normal->update(['assignee_id' => $staff->id, 'severity' => SupportTicketSeverity::Normal]);
    $high = toolsTicket();
    $high->update(['assignee_id' => $staff->id, 'severity' => SupportTicketSeverity::High]);
    $finished = toolsTicket();
    $finished->update(['assignee_id' => $staff->id, 'status' => SupportTicketStatus::Resolved]);
    $someoneElses = toolsTicket();
    $someoneElses->update(['assignee_id' => Staff::factory()->create()->id]);

    $mine = app(StaffDashboardService::class)->for($staff)['work']['tickets'];

    expect($mine->pluck('id')->all())->toBe([$high->id, $normal->id]);
    $this->actingAs($staff, 'staff')->get(route('admin.dashboard'))->assertOk()->assertSee('Support requests you have')->assertSee($high->reference);
    expect(app(StaffDashboardService::class)->for(staffWith('Marketplace moderator'))['work']['tickets'])->toBeEmpty();
});

// ---- the service levels page ------------------------------------------------------------------------------------------------

it('is open to people who can see requests and nobody else', function () {
    $this->get(route('admin.support.levels'))->assertRedirect();
    $this->actingAs(User::factory()->create())->get(route('admin.support.levels'))->assertRedirect();
    $this->actingAs(staffWith('Auditor'), 'staff')->get(route('admin.support.levels'))->assertForbidden();

    $this->actingAs(toolsViewer(), 'staff')->get(route('admin.support.levels'))->assertOk()->assertSee('Service levels');
    $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.support.levels'))->assertOk();
});

it('says what the clocks use: the targets and hours from the config, and changes when they change', function () {
    $page = fn () => $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.support.levels'))->assertOk();
    $table = fn ($levels) => $levels->mapWithKeys(fn ($l) => [$l['severity']->value => [$l['first'], $l['resolution']]])->all();

    $page()->assertSee('weekdays, 8am to 6pm EAT')->assertSee('Best effort')
        ->assertViewHas('levels', fn ($levels) => $table($levels) === [
            'urgent' => ['1 business hour', '1 business day'],
            'high' => ['4 business hours', '1 business day'],
            'normal' => ['1 business day', '3 business days'],
            'low' => ['2 business days', 'Best effort'],
        ]);

    // one target changed: that row of the table changes, and the sentence members read with it (the table's own values are checked, because the
    // same words also appear in those sentences)
    config(['support.tickets.targets.high.first_response' => 120]);
    $page()->assertSee('within 2 business hours')->assertViewHas('levels', fn ($levels) => $table($levels)['high'] === ['2 business hours', '1 business day'] && $table($levels)['urgent'][0] === '1 business hour');

    // the hours changed: the page says so
    config(['support.tickets.business_hours.end' => '17:00']);
    $page()->assertSee('weekdays, 8am to 5pm EAT')->assertDontSee('weekdays, 8am to 6pm EAT');
});

it('says honestly how urgency is decided: only a person sets urgent, and the page is marked provisional', function () {
    $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.support.levels'))->assertOk()
        ->assertSee('Only a person sets this')->assertSee('provisional')->assertSee('Public holidays are not excluded yet');
});

it('shows where the team stands right now', function () {
    $grace = staffWith('Support');
    $grace->update(['name' => 'Grace Wambui']);
    $overdue = toolsTicket();
    $overdue->update(['first_response_due_at' => now()->subHour(), 'assignee_id' => $grace->id]);
    toolsTicket()->update(['first_response_due_at' => now()->addMinutes(20)]);
    toolsTicket()->update(['first_response_due_at' => now()->addDays(2)]);

    $this->actingAs($grace, 'staff')->get(route('admin.support.levels'))->assertOk()
        ->assertSeeInOrder(['Waiting for staff', '3', 'Overdue', '1', 'Due in the next 2 hours', '1', 'Nobody has it yet', '2'])
        ->assertSee('Grace Wambui');
});

it('never shows a member\'s words on the service levels page or the dashboard\'s support list', function () {
    toolsTicket('<script>alert("x")</script> MEMBER-TEXT');

    foreach ([route('admin.support.levels'), route('admin.dashboard')] as $url) {
        $this->actingAs(staffWith('Support'), 'staff')->get($url)->assertOk()->assertDontSee('MEMBER-TEXT')->assertDontSee('<script>alert("x")');
    }
});

it('resolves tickets the usual way so the resolved ones leave every count', function () {
    $staff = staffWith('Support');
    $ticket = toolsTicket();
    app(TicketService::class)->resolve($ticket, $staff, SupportResolutionTag::Other);

    expect(SupportTicket::needingStaff()->count())->toBe(0)->and(SupportTicket::overdue()->count())->toBe(0);
});

// ---- the service levels page leads to the work ----------------------------------------------------------------------

it('links each of the four figures to the queue it counts', function () {
    $html = $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.support.levels'))->assertOk()->getContent();

    foreach (['queue', 'overdue', 'soon', 'unassigned'] as $tab) {
        expect($html)->toContain(e(route('admin.support.tickets.index', ['tab' => $tab])));
    }
});

it('lists exactly what each figure counted when you follow it', function () {
    $mine = staffWith('Support');
    $late = toolsTicket();
    $late->update(['first_response_due_at' => now()->subHour(), 'assignee_id' => $mine->id]);
    $soon = toolsTicket();
    $soon->update(['first_response_due_at' => now()->addMinutes(20)]);
    $later = toolsTicket();
    $later->update(['first_response_due_at' => now()->addDays(2), 'assignee_id' => $mine->id]);

    $refs = fn (string $tab) => collect($this->actingAs($mine, 'staff')->get(route('admin.support.tickets.index', ['tab' => $tab]))->assertOk()->viewData('tickets')->items())->pluck('reference')->sort()->values()->all();

    expect($refs('overdue'))->toBe([$late->reference])
        ->and($refs('soon'))->toBe([$soon->reference])
        ->and($refs('unassigned'))->toBe(collect([$soon->reference])->all())
        ->and($refs('queue'))->toHaveCount(3);
});

it('says whether support is open right now, and when it opens again', function () {
    // each look is a fresh sign-in: the days jumped between them would otherwise time the first session out
    $page = fn () => tap($this->flushSession())->actingAs(staffWith('Support'), 'staff')->get(route('admin.support.levels'))->assertOk();

    $this->travelTo(CarbonImmutable::parse('2026-10-07 11:00', 'Africa/Nairobi')); // a Wednesday morning
    $page()->assertSee('Open now')->assertDontSee('Closed now')->assertDontSee('Opens again');

    $this->travelTo(CarbonImmutable::parse('2026-10-10 11:00', 'Africa/Nairobi')); // a Saturday
    $page()->assertSee('Closed now')->assertSee('Opens again Monday at 8:00 AM');
});

it('draws the week from the configured days', function () {
    config(['support.tickets.business_days' => [1, 2, 3, 4, 5, 6]]);
    $this->travelTo(CarbonImmutable::parse('2026-10-07 11:00', 'Africa/Nairobi'));

    $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.support.levels'))->assertOk()
        ->assertViewHas('week', fn ($week) => collect($week)->where('open', true)->count() === 6 && collect($week)->firstWhere('name', 'Sun')['open'] === false && collect($week)->firstWhere('name', 'Wed')['today'] === true);
});
