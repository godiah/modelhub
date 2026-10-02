<?php

use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Models\StaffActivity;
use App\Models\User;
use App\Notifications\PayoutNotSentNotification;
use App\Services\Admin\StaffDashboardService;
use App\Services\Ledger\LedgerService;
use App\Services\Payments\PayoutService;
use App\Support\Ledger\LedgerLine;
use App\Support\Navigation\StaffMenu;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

/*
 * The staff withdrawals queue: who may see it and who may approve, what is masked, and approving or turning a withdrawal down.
 */

beforeEach(function () {
    config(['payments.fake.delay_seconds' => 0]);
    Notification::fake();
    $this->member = User::factory()->create(['name' => 'Kevin Mwangi']);
});

/** A withdrawal asked for by the member, waiting for approval (paid out to a number ending in the given digit). */
function waitingPayout(User $member, int $amountMinor = 200000, string $phone = '0712345675'): Payout
{
    $ledger = app(LedgerService::class);
    $ledger->post('release', 'q:balance:'.$member->id.':'.random_int(1, 999999), [
        LedgerLine::debit($ledger->platformAccount('gateway'), $amountMinor), LedgerLine::credit($ledger->userAccount($member, 'available'), $amountMinor),
    ], 'Test balance');

    return app(PayoutService::class)->request($member, $amountMinor, $phone);
}

it('gives Finance, and only Finance and Super admin, the withdrawal permissions', function () {
    $permissions = fn (string $role) => staffWith($role)->getAllPermissions()->pluck('name')->filter(fn ($p) => str_contains($p, 'payouts'))->sort()->values()->all();

    expect($permissions('Finance'))->toBe(['approve payouts', 'view payouts'])->and($permissions('Super admin'))->toBe(['approve payouts', 'view payouts'])
        ->and($permissions('Platform manager'))->toBe([])->and($permissions('Auditor'))->toBe([])->and($permissions('Support'))->toBe([])->and($permissions('Marketplace moderator'))->toBe([]);
});

it('keeps the queue to staff who may view withdrawals', function () {
    foreach (['Platform manager', 'Auditor', 'Support', 'Dispute manager'] as $role) {
        $this->actingAs(staffWith($role), 'staff')->get(route('admin.payouts.index'))->assertForbidden();
    }
    $this->actingAs(staffWith('Finance'), 'staff')->get(route('admin.payouts.index'))->assertOk()->assertSee('Payouts');
    $this->actingAs(staffWith('Super admin'), 'staff')->get(route('admin.payouts.index'))->assertOk();

    auth('staff')->logout();
    $this->get(route('admin.payouts.index'))->assertRedirect(route('admin.login'));
});

it('lists what is waiting, oldest first, with the member, amounts and number', function () {
    $older = waitingPayout($this->member, 200000, '0712345675');
    $older->forceFill(['created_at' => now()->subDays(2)])->save();
    $other = User::factory()->create(['name' => 'Amina Otieno']);
    $newer = waitingPayout($other, 100000, '0733111222');

    $this->actingAs(staffWith('Finance'), 'staff')->get(route('admin.payouts.index'))->assertOk()->assertSeeInOrder(['Kevin Mwangi', 'Amina Otieno'])->assertSee('Ksh2,000')->assertSee('Ksh1,970')->assertSee('fee Ksh30')
        ->assertSee($older->reference)->assertSee('0712 345 675')->assertSee('Approve')->assertSee('Turn down');
});

it('masks the phone number from staff who may view but not approve', function () {
    waitingPayout($this->member, 200000, '0712345675');
    $viewer = staffWith();
    $viewer->givePermissionTo('view payouts');

    $this->actingAs($viewer, 'staff')->get(route('admin.payouts.index'))->assertOk()->assertSee('Kevin Mwangi')->assertDontSee('0712 345 675')->assertDontSee('254712345675')->assertDontSee('Approve')->assertDontSee('Turn down');

    $this->patch(route('admin.payouts.reject', Payout::first()), ['reason' => 'No reason really'])->assertStatus(405);
    $this->post(route('admin.payouts.approve', Payout::first()))->assertForbidden();
    $this->post(route('admin.payouts.reject', Payout::first()), ['reason' => 'No reason really'])->assertForbidden();
    expect(Payout::first()->status)->toBe(PayoutStatus::Requested);
});

it('approves a withdrawal from the queue, which sends it, and the member is paid', function () {
    $payout = waitingPayout($this->member);
    $finance = staffWith('Finance');
    $this->actingAs($finance, 'staff');

    $this->post(route('admin.payouts.approve', $payout))->assertRedirect()->assertSessionHas('success');

    expect($payout->fresh()->status)->toBe(PayoutStatus::Processing)->and($payout->fresh()->approved_by)->toBe($finance->id);
    app(PayoutService::class)->checkProcessing();
    expect($payout->fresh()->status)->toBe(PayoutStatus::Paid);

    $this->get(route('admin.payouts.index', ['status' => 'paid']))->assertSee('Kevin Mwangi')->assertSee('Paid')->assertSee('by '.$finance->name);
    $this->post(route('admin.payouts.approve', $payout))->assertSessionHas('error');
});

it('says so when the transfer could not be started, and returns the money', function () {
    $payout = waitingPayout($this->member, 200000, '0712345670');
    $this->actingAs(staffWith('Finance'), 'staff');

    $this->post(route('admin.payouts.approve', $payout))->assertRedirect()->assertSessionHas('error');

    expect($payout->fresh()->status)->toBe(PayoutStatus::Failed)->and(app(LedgerService::class)->balances($this->member)['available'])->toBe(200000);
    Notification::assertSentTo($this->member, PayoutNotSentNotification::class);
});

it('turns a withdrawal down from the queue, needing a proper reason', function () {
    $payout = waitingPayout($this->member);
    $finance = staffWith('Finance');
    $this->actingAs($finance, 'staff');

    $this->post(route('admin.payouts.reject', $payout), [])->assertSessionHasErrors('reason');
    $this->post(route('admin.payouts.reject', $payout), ['reason' => 'abc'])->assertSessionHasErrors('reason');
    expect($payout->fresh()->status)->toBe(PayoutStatus::Requested);

    $this->post(route('admin.payouts.reject', $payout), ['reason' => 'The number does not match your profile.'])->assertRedirect()->assertSessionHas('success');

    expect($payout->fresh()->status)->toBe(PayoutStatus::Rejected)->and($payout->fresh()->failure_reason)->toBe('The number does not match your profile.')->and(app(LedgerService::class)->balances($this->member)['available'])->toBe(200000);
    Notification::assertSentTo($this->member, PayoutNotSentNotification::class, fn ($n) => $n->payout->failure_reason === 'The number does not match your profile.');
    expect(StaffActivity::where('action', 'payout.rejected')->where('staff_id', $finance->id)->exists())->toBeTrue();

    $this->get(route('admin.payouts.index', ['status' => 'rejected']))->assertSee('The number does not match your profile.')->assertSee('Turned down');
});

it('filters the queue by status and counts each tab', function () {
    $waiting = waitingPayout($this->member);
    $other = User::factory()->create(['name' => 'Amina Otieno']);
    $cancelled = waitingPayout($other, 100000);
    app(PayoutService::class)->cancel($cancelled, $other);
    $this->actingAs(staffWith('Finance'), 'staff');

    $this->get(route('admin.payouts.index'))->assertSee('Kevin Mwangi')->assertDontSee('Amina Otieno');
    $this->get(route('admin.payouts.index', ['status' => 'rejected']))->assertSee('Amina Otieno')->assertDontSee('Kevin Mwangi')->assertSee('Cancelled');
    $this->get(route('admin.payouts.index', ['status' => 'all']))->assertSee('Kevin Mwangi')->assertSee('Amina Otieno');
    $this->get(route('admin.payouts.index', ['status' => 'bogus']))->assertOk()->assertSee('Kevin Mwangi');
    expect($waiting->fresh()->status)->toBe(PayoutStatus::Requested);
});

it('sorts the queue by amount', function () {
    waitingPayout($this->member, 200000);
    $big = User::factory()->create(['name' => 'Big Spender']);
    waitingPayout($big, 900000);
    $this->actingAs(staffWith('Finance'), 'staff');

    $this->get(route('admin.payouts.index', ['sort' => 'amount']))->assertSeeInOrder(['Big Spender', 'Kevin Mwangi']);
    $this->get(route('admin.payouts.index', ['sort' => 'amount', 'dir' => 'asc']))->assertSeeInOrder(['Kevin Mwangi', 'Big Spender']);
    $this->get(route('admin.payouts.index', ['sort' => 'evil', 'dir' => 'up']))->assertOk();
});

it('shows withdrawals to approve in the menu, on the dashboard and in the attention list, only to those who may approve', function () {
    waitingPayout($this->member);
    waitingPayout(User::factory()->create(), 100000);
    $finance = staffWith('Finance');

    expect(StaffMenu::count('payouts'))->toBe(2);
    $page = $this->actingAs($finance, 'staff')->get(route('admin.dashboard'))->assertOk();
    $page->assertSee('Withdrawals to approve')->assertSee('Withdrawal')->assertSee('Kevin Mwangi');

    $queues = collect(app(StaffDashboardService::class)->for($finance)['attention']['queues'])->firstWhere('key', 'payouts');
    expect($queues)->toMatchArray(['count' => 2, 'late' => 0, 'label' => 'Payouts']);

    $this->actingAs(staffWith('Platform manager'), 'staff')->get(route('admin.dashboard'))->assertDontSee('Withdrawals to approve');
    expect(collect(StaffMenu::for($finance))->flatMap->items->firstWhere('label', 'Payouts')['badge'])->toBe(2);
});

it('counts a withdrawal late once it has waited three days', function () {
    $payout = waitingPayout($this->member);
    $payout->forceFill(['created_at' => now()->subDays(5)])->save();

    $queue = collect(app(StaffDashboardService::class)->for(staffWith('Finance'))['attention']['queues'])->firstWhere('key', 'payouts');

    expect($queue['late'])->toBe(1);
});

it('lets a Super admin give a role the withdrawal permissions', function () {
    $role = Role::create(['name' => 'Cashier', 'guard_name' => 'staff']);
    $role->givePermissionTo('view payouts', 'approve payouts');
    $cashier = staffWith();
    $cashier->assignRole($role);

    $this->actingAs($cashier->fresh(), 'staff')->get(route('admin.payouts.index'))->assertOk();
    $this->actingAs(staffWith('Super admin'), 'staff')->get(route('admin.roles.create'))->assertOk()->assertSee('Approve withdrawals')->assertSee('View withdrawals');
});

it('renders the approve and turn-down buttons with real data, not raw template directives', function () {
    $payout = waitingPayout($this->member);

    $html = $this->actingAs(staffWith('Finance'), 'staff')->get(route('admin.payouts.index'))->assertOk()->getContent();

    expect($html)->not->toContain('@js(')->and($html)->toContain('approve-payout')->and($html)->toContain('reject-payout')
        ->and($html)->toContain('payouts\\\\\/'.$payout->reference.'\\\\\/approve');
});
