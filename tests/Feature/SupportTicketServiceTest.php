<?php

use App\Enums\LicenceTier;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Enums\SupportResolutionTag;
use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketSeverity;
use App\Enums\SupportTicketStatus;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\StaffActivity;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Services\Support\Tickets\BusinessHours;
use App\Services\Support\Tickets\TicketService;
use App\Support\Staff\StaffAccess;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Role;

/*
 * The core of support tickets: what a member can start, how urgent it is (worked out from their live records, never from what anyone claims), when
 * a reply is due in business hours, how the thread moves between member and staff, and that staff's notes stay staff's.
 */

beforeEach(function () {
    $this->member = User::factory()->create();
    $this->tickets = app(TicketService::class);
    $this->staff = staffWith('Support');
    RateLimiter::clear('support-ticket-reply:'.$this->member->id);
});

function open(object $test, string $summary = 'I paid but I have no licence', SupportTicketCategory $category = SupportTicketCategory::PaymentIssue, array $refs = [], ?User $member = null): SupportTicket|string
{
    return $test->tickets->open($member ?? $test->member, $category, $summary, $refs);
}

function aPayment(User $buyer, PaymentStatus $status, array $over = []): Payment
{
    $seller = User::factory()->create();
    SellerProfile::factory()->approved()->create(['user_id' => $seller->id]);
    $product = Product::factory()->published()->create(['user_id' => $seller->id, 'price_minor' => 120000]);

    return Payment::create($over + [
        'reference' => Payment::newReference(), 'purpose' => Payment::PURPOSE_SALE, 'user_id' => $buyer->id, 'seller_id' => $seller->id, 'product_id' => $product->id,
        'tier' => LicenceTier::Standard, 'amount_minor' => 120000, 'currency' => 'KES', 'msisdn' => '254712345675', 'status' => $status, 'gateway' => 'gw',
        'gateway_reference' => uniqid('gw'), 'commission_rate' => 0.15, 'commission_minor' => 18000, 'seller_share_minor' => 102000, 'hold_days' => 7, 'expires_at' => now(),
    ]);
}

// ---- business hours ---------------------------------------------------------------------------------------------------

it('counts response time in business hours, in Nairobi time', function (string $from, int $minutes, string $expected) {
    $hours = BusinessHours::fromConfig();
    $start = CarbonImmutable::parse($from, 'Africa/Nairobi');

    expect($hours->addMinutes($start, $minutes)->setTimezone('Africa/Nairobi')->format('D Y-m-d H:i'))->toBe($expected);
})->with([
    'within a day' => ['2026-10-05 09:00', 60, 'Mon 2026-10-05 10:00'],
    'before opening waits for opening' => ['2026-10-05 06:00', 60, 'Mon 2026-10-05 09:00'],
    'after closing waits for the next morning' => ['2026-10-05 19:30', 30, 'Tue 2026-10-06 08:30'],
    'friday evening runs into monday' => ['2026-10-09 17:30', 60, 'Mon 2026-10-12 08:30'],
    'saturday waits for monday' => ['2026-10-10 12:00', 30, 'Mon 2026-10-12 08:30'],
    'spans two days' => ['2026-10-05 17:00', 600, 'Tue 2026-10-06 17:00'],
    'a full business day' => ['2026-10-05 08:00', 600, 'Mon 2026-10-05 18:00'],
]);

it('knows when the team is in', function () {
    $hours = BusinessHours::fromConfig();

    expect($hours->isOpenAt(CarbonImmutable::parse('2026-10-05 12:00', 'Africa/Nairobi')))->toBeTrue()
        ->and($hours->isOpenAt(CarbonImmutable::parse('2026-10-05 18:00', 'Africa/Nairobi')))->toBeFalse()
        ->and($hours->isOpenAt(CarbonImmutable::parse('2026-10-10 12:00', 'Africa/Nairobi')))->toBeFalse();
});

// ---- opening a ticket -------------------------------------------------------------------------------------------------

it('opens a ticket with a reference, the member\'s words as the first message, and the clocks for its severity', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 09:00', 'Africa/Nairobi'));

    $ticket = open($this, 'I paid but I have no licence', SupportTicketCategory::Other);

    expect($ticket)->toBeInstanceOf(SupportTicket::class)
        ->and($ticket->reference)->toBe('SUP-'.(1000 + $ticket->id))
        ->and($ticket->status)->toBe(SupportTicketStatus::Open)
        ->and($ticket->severity)->toBe(SupportTicketSeverity::Low)
        ->and($ticket->requester_id)->toBe($this->member->id)
        ->and($ticket->messages)->toHaveCount(1)
        ->and($ticket->messages->first()->sender)->toBe(SupportTicketMessage::MEMBER)
        ->and($ticket->messages->first()->body)->toBe('I paid but I have no licence')
        // low: first reply within 1200 business minutes (two business days of 600) of Monday 09:00 is Wednesday 09:00; no resolution target
        ->and($ticket->first_response_due_at->setTimezone('Africa/Nairobi')->format('Y-m-d H:i'))->toBe('2026-10-07 09:00')
        ->and($ticket->resolution_due_at)->toBeNull();
});

it('refuses a request that says too little or too much, in words the member can act on', function (string $summary) {
    expect(open($this, $summary))->toBeString();
    expect(SupportTicket::count())->toBe(0);
})->with(['', '  hi ', 'help']);

it('refuses a request that is far too long', function () {
    expect(open($this, str_repeat('a', 4001)))->toContain('too long')->and(SupportTicket::count())->toBe(0);
});

it('limits how many open requests, and how many a day, a member can have', function () {
    config(['support.tickets.max_open' => 2, 'support.tickets.max_per_day' => 10]);
    open($this, 'First problem here');
    open($this, 'Second problem here');

    expect(open($this, 'Third problem here'))->toContain('several open requests');

    config(['support.tickets.max_open' => 10, 'support.tickets.max_per_day' => 2]);

    expect(open($this, 'Third problem here'))->toContain('lot of requests today');
});

it('lets a different member open one when someone else is at their limit', function () {
    config(['support.tickets.max_open' => 1]);
    open($this, 'First problem here');

    expect(open($this, 'My own problem'))->toBeString()
        ->and(open($this, 'Their own problem', member: User::factory()->create()))->toBeInstanceOf(SupportTicket::class);
});

// ---- the records a ticket points at ------------------------------------------------------------------------------------

it('keeps only the records the member owns, and drops anything else however it is claimed', function () {
    $mine = aPayment($this->member, PaymentStatus::Succeeded);
    $theirs = aPayment(User::factory()->create(), PaymentStatus::Review);

    $ticket = open($this, refs: [
        ['type' => 'payment', 'reference' => $mine->reference],
        ['type' => 'payment', 'reference' => $theirs->reference],
        ['type' => 'payment', 'reference' => 'MHNOTAREAL00'],
        ['type' => 'unknown', 'reference' => 'x'],
        ['type' => ['payment'], 'reference' => $mine->reference],
        ['reference' => $mine->reference],
    ]);

    expect($ticket->entity_refs)->toBe(['payment:'.$mine->reference]);
});

it('does not take more than five records, nor the same one twice', function () {
    $payment = aPayment($this->member, PaymentStatus::Succeeded);
    $refs = array_fill(0, 12, ['type' => 'payment', 'reference' => $payment->reference]);

    expect(open($this, refs: $refs)->entity_refs)->toBe(['payment:'.$payment->reference]);
});

// ---- severity: from live records and fixed rules, never from claims ----------------------------------------------------

it('works out severity from the records, not from how the request is worded', function (callable $make, SupportTicketSeverity $expected) {
    $ref = $make($this->member);

    $ticket = open($this, 'just checking in about this', SupportTicketCategory::PaymentIssue, [$ref]);

    expect($ticket->severity)->toBe($expected);
})->with([
    'a payment under review' => [fn (User $m) => ['type' => 'payment', 'reference' => aPayment($m, PaymentStatus::Review)->reference], SupportTicketSeverity::High],
    'paid with no licence' => [fn (User $m) => ['type' => 'payment', 'reference' => aPayment($m, PaymentStatus::Succeeded)->reference], SupportTicketSeverity::High],
    'a payment that failed' => [fn (User $m) => ['type' => 'payment', 'reference' => aPayment($m, PaymentStatus::Failed)->reference], SupportTicketSeverity::Normal],
    'a withdrawal stuck for hours' => [function (User $m) {
        $p = Payout::create(['reference' => Payout::newReference(), 'user_id' => $m->id, 'amount_minor' => 100000, 'fee_minor' => 3000, 'net_minor' => 97000, 'currency' => 'KES', 'msisdn' => '254712345675', 'status' => PayoutStatus::Processing, 'approved_at' => now()->subHours(9)]);

        return ['type' => 'withdrawal', 'reference' => $p->reference];
    }, SupportTicketSeverity::High],
    'a withdrawal just sent' => [function (User $m) {
        $p = Payout::create(['reference' => Payout::newReference(), 'user_id' => $m->id, 'amount_minor' => 100000, 'fee_minor' => 3000, 'net_minor' => 97000, 'currency' => 'KES', 'msisdn' => '254712345675', 'status' => PayoutStatus::Processing, 'approved_at' => now()->subHour()]);

        return ['type' => 'withdrawal', 'reference' => $p->reference];
    }, SupportTicketSeverity::Normal],
]);

it('does not trust a claim that something is urgent unless the records or the words say so', function () {
    $fine = aPayment($this->member, PaymentStatus::Failed);

    $calm = open($this, 'URGENT URGENT please help immediately', SupportTicketCategory::PaymentIssue, [['type' => 'payment', 'reference' => $fine->reference]]);

    expect($calm->severity)->toBe(SupportTicketSeverity::Normal);
});

it('raises severity for the words that mean harm, and for writing in again about the same thing within a week', function () {
    expect(open($this, 'I think I was scammed by a seller', SupportTicketCategory::Other)->severity)->toBe(SupportTicketSeverity::High);

    $payment = aPayment($this->member, PaymentStatus::Failed);
    $ref = [['type' => 'payment', 'reference' => $payment->reference]];
    $first = open($this, 'My payment failed twice', SupportTicketCategory::PaymentIssue, $ref);
    $again = open($this, 'Still no answer on my payment', SupportTicketCategory::PaymentIssue, $ref);

    expect($first->severity)->toBe(SupportTicketSeverity::Normal)->and($again->severity)->toBe(SupportTicketSeverity::High);
});

it('starts an account-access ticket high and an "other" one low', function () {
    expect(open($this, 'I cannot sign in at all', SupportTicketCategory::AccountAccess)->severity)->toBe(SupportTicketSeverity::High)
        ->and(open($this, 'A general question for you', SupportTicketCategory::Other)->severity)->toBe(SupportTicketSeverity::Low);
});

// ---- the thread ------------------------------------------------------------------------------------------------------

it('moves between staff and member: a staff reply waits for the member, the member\'s reply waits for staff', function () {
    $ticket = open($this);

    $this->tickets->staffReply($ticket, $this->staff, 'Thanks, we are looking at your payment.');
    $ticket->refresh();
    expect($ticket->status)->toBe(SupportTicketStatus::PendingMember)->and($ticket->first_responded_at)->not->toBeNull();

    $this->tickets->memberReply($ticket, $this->member, 'Any news?');
    expect($ticket->refresh()->status)->toBe(SupportTicketStatus::PendingStaff);
});

it('keeps staff notes out of what the member can read, and changes nothing else', function () {
    $ticket = open($this);

    $this->tickets->note($ticket, $this->staff, 'Receipt matches the gateway; checking for a duplicate licence.');

    expect($ticket->refresh()->status)->toBe(SupportTicketStatus::Open)->and($ticket->first_responded_at)->toBeNull()
        ->and($ticket->messages)->toHaveCount(2)->and($ticket->memberMessages)->toHaveCount(1)
        ->and($ticket->memberMessages->pluck('sender')->all())->not->toContain('note');
});

it('lets a member reply only to their own ticket', function () {
    $ticket = open($this);

    expect($this->tickets->memberReply($ticket, User::factory()->create(), 'Hello'))->toBe('This is not your request.')
        ->and($ticket->messages()->count())->toBe(1);
});

it('refuses an empty reply, a huge one, and a reply flood', function () {
    $ticket = open($this);

    expect($this->tickets->memberReply($ticket, $this->member, '   '))->toBeString()
        ->and($this->tickets->memberReply($ticket, $this->member, str_repeat('a', 4001)))->toBeString()
        ->and($this->tickets->staffReply($ticket, $this->staff, ''))->toBeString();

    config(['support.tickets.max_replies_per_hour' => 2]);
    $this->tickets->memberReply($ticket, $this->member, 'one');
    $this->tickets->memberReply($ticket, $this->member, 'two');

    expect($this->tickets->memberReply($ticket, $this->member, 'three'))->toContain('wait');
});

it('resolves with a tag and an optional last reply, and a member reply within the window reopens it', function () {
    $ticket = open($this);

    $this->tickets->resolve($ticket, $this->staff, SupportResolutionTag::DocMissing, 'We found it: the licence is now in My licences.');
    $ticket->refresh();

    expect($ticket->status)->toBe(SupportTicketStatus::Resolved)->and($ticket->resolution_tag)->toBe(SupportResolutionTag::DocMissing)
        ->and($ticket->resolved_at)->not->toBeNull()->and($ticket->first_responded_at)->not->toBeNull()
        ->and($ticket->messages->last()->body)->toContain('now in My licences');

    $this->tickets->memberReply($ticket, $this->member, 'It is still missing for me.');
    $ticket->refresh();

    expect($ticket->status)->toBe(SupportTicketStatus::PendingStaff)->and($ticket->resolved_at)->toBeNull()->and($ticket->resolution_tag)->toBeNull();
});

it('does not reopen a ticket resolved long ago, nor a closed one', function () {
    $ticket = open($this);
    $this->tickets->resolve($ticket, $this->staff, SupportResolutionTag::Other);
    $this->travel(15)->days();

    expect($this->tickets->memberReply($ticket->refresh(), $this->member, 'Hello again'))->toContain('start a new one');

    $closed = open($this, 'Another thing entirely');
    $this->tickets->close($closed, $this->staff);

    expect($this->tickets->memberReply($closed->refresh(), $this->member, 'Hello'))->toContain('closed')
        ->and($this->tickets->staffReply($closed, $this->staff, 'Hello'))->toContain('closed');
});

it('does not resolve a ticket twice', function () {
    $ticket = open($this);
    $this->tickets->resolve($ticket, $this->staff, SupportResolutionTag::Other);

    expect($this->tickets->resolve($ticket->refresh(), $this->staff, SupportResolutionTag::Other))->toBeString();
});

it('assigns, unassigns and changes urgency with the clocks worked out again from when it was filed', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 09:00', 'Africa/Nairobi'));
    $ticket = open($this, 'A general question for you', SupportTicketCategory::Other);
    $other = staffWith('Support');

    $this->tickets->assign($ticket, $this->staff, $other);
    expect($ticket->refresh()->assignee_id)->toBe($other->id);
    $this->tickets->assign($ticket, $this->staff, null);
    expect($ticket->refresh()->assignee_id)->toBeNull();

    $this->tickets->setSeverity($ticket, $this->staff, SupportTicketSeverity::Urgent);
    $ticket->refresh();

    expect($ticket->severity)->toBe(SupportTicketSeverity::Urgent)
        ->and($ticket->first_response_due_at->setTimezone('Africa/Nairobi')->format('H:i'))->toBe('10:00')
        ->and($ticket->resolution_due_at->setTimezone('Africa/Nairobi')->format('Y-m-d H:i'))->toBe('2026-10-06 09:00'); // 600 business minutes from Monday 09:00: 540 left that day, 60 into Tuesday
});

it('records every staff action in the activity log, with who did it', function () {
    $ticket = open($this);

    $this->tickets->staffReply($ticket, $this->staff, 'On it.');
    $this->tickets->note($ticket, $this->staff, 'Checking.');
    $this->tickets->assign($ticket, $this->staff, $this->staff);
    $this->tickets->setSeverity($ticket, $this->staff, SupportTicketSeverity::High);
    $this->tickets->resolve($ticket, $this->staff, SupportResolutionTag::Bug);
    $this->tickets->close($ticket, $this->staff);

    $log = StaffActivity::where('subject_type', 'SupportTicket')->where('subject_id', $ticket->id)->orderBy('id')->get();

    expect($log->pluck('action')->all())->toBe(['support.ticket.replied', 'support.ticket.noted', 'support.ticket.assigned', 'support.ticket.severity', 'support.ticket.resolved', 'support.ticket.closed'])
        ->and($log->pluck('staff_id')->unique()->all())->toBe([$this->staff->id]);
});

// ---- who can see what ----------------------------------------------------------------------------------------------

it('scopes a member to their own tickets, so someone else\'s reference is the same as a missing one', function () {
    $mine = open($this);
    $theirs = open($this, 'Their problem here', member: User::factory()->create());

    expect(SupportTicket::ownedBy($this->member)->pluck('id')->all())->toBe([$mine->id])
        ->and(SupportTicket::ownedBy($this->member)->where('reference', $theirs->reference)->first())->toBeNull();
});

it('puts tickets waiting on staff in the queue, and knows when one is overdue', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 09:00', 'Africa/Nairobi'));
    $ticket = open($this, 'My payment is under review', SupportTicketCategory::PaymentIssue, [['type' => 'payment', 'reference' => aPayment($this->member, PaymentStatus::Review)->reference]]);

    expect(SupportTicket::needingStaff()->count())->toBe(1)->and($ticket->isOverdue())->toBeFalse();

    $this->travelTo(CarbonImmutable::parse('2026-10-05 14:00', 'Africa/Nairobi')); // high: first reply due 13:00

    expect($ticket->refresh()->isOverdue())->toBeTrue();

    $this->tickets->staffReply($ticket, $this->staff, 'Looking.');

    expect($ticket->refresh()->isOverdue())->toBeFalse()->and(SupportTicket::needingStaff()->count())->toBe(0);
});

it('has the ticket permissions: the Support role can see and answer tickets, and Super admin holds both', function () {
    $permissions = StaffAccess::permissions();
    $support = Role::findByName('Support', 'staff');
    $super = Role::findByName(StaffAccess::SUPER_ADMIN, 'staff');

    expect($permissions)->toContain('view support tickets', 'manage support tickets')
        ->and($support->hasPermissionTo('view support tickets'))->toBeTrue()
        ->and($support->hasPermissionTo('manage support tickets'))->toBeTrue()
        ->and($super->hasPermissionTo('manage support tickets'))->toBeTrue();
});
