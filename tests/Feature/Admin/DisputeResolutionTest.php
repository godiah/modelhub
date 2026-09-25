<?php

use App\Enums\DisputeStatus;
use App\Enums\EngagementStatus;
use App\Models\JobApplication;
use App\Models\JobCancellation;
use App\Models\JobEngagement;
use App\Models\JobPaymentDispute;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function makeDisputedEngagement(float $netAmount = 1000): array
{
    $application = JobApplication::factory()->hired()->create(['net_amount' => $netAmount]);

    $engagement = JobEngagement::create([
        'application_id' => $application->id,
        'status' => EngagementStatus::Disputed,
        'agreed_amount' => $application->offer_amount,
        'service_fee' => $application->service_fee,
        'net_amount' => $netAmount,
    ]);

    // A real dispute is always preceded by a processed partial payment, which already set
    // partial_payment_amount on the cancellation — mirror that here so resolveDispute()'s
    // null-$finalAmount fallback (partial_payment_amount) has a real value to fall back to.
    $cancellation = JobCancellation::create([
        'engagement_id' => $engagement->id,
        'initiator_id' => $application->applicant_id,
        'cancellation_type' => 'dispute',
        'reason_category' => 'other',
        'reason_details' => 'test reason',
        'partial_payment_amount' => $netAmount * 0.5,
        'is_dispute' => true,
    ]);

    $dispute = JobPaymentDispute::create([
        'cancellation_id' => $cancellation->id,
        'disputed_by' => $application->applicant_id,
        'dispute_reason' => 'incorrect_amount',
        'dispute_details' => 'test details',
        'status' => DisputeStatus::Pending,
    ]);

    return compact('application', 'engagement', 'cancellation', 'dispute');
}

test('admin can reach every admin route, regular user gets 403 everywhere', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $regular = User::factory()->create();
    ['dispute' => $dispute] = makeDisputedEngagement();

    $this->actingAs($admin)->get(route('admin.disputes.index'))->assertOk();
    $this->actingAs($admin)->post(route('admin.disputes.assign', $dispute))->assertRedirect();
    $this->actingAs($admin)->get(route('admin.staff.index'))->assertOk();

    $this->actingAs($regular)->get(route('admin.disputes.index'))->assertForbidden();
    $this->actingAs($regular)->post(route('admin.disputes.assign', $dispute))->assertForbidden();
    $this->actingAs($regular)->get(route('admin.staff.index'))->assertForbidden();
});

test('support role can view disputes but not assign, resolve, or manage staff', function () {
    $support = User::factory()->create();
    $support->assignRole('support');
    ['dispute' => $dispute] = makeDisputedEngagement();

    $this->actingAs($support)->get(route('admin.disputes.index'))->assertOk();
    $this->actingAs($support)->post(route('admin.disputes.assign', $dispute))->assertForbidden();
    $this->actingAs($support)->post(route('admin.disputes.resolve', $dispute), ['resolution_notes' => 'x'])->assertForbidden();

    // support holds 'view users' (read-only staff directory) but not 'manage users'
    $this->actingAs($support)->get(route('admin.staff.index'))->assertOk();
    $this->actingAs($support)
        ->patch(route('admin.staff.update-role', User::factory()->create()), ['role' => 'support'])
        ->assertForbidden();
});

test('dispute_manager role can view, assign, and resolve disputes but not manage staff', function () {
    $manager = User::factory()->create();
    $manager->assignRole('dispute_manager');
    ['dispute' => $dispute] = makeDisputedEngagement();

    $this->actingAs($manager)->get(route('admin.disputes.index'))->assertOk();
    $this->actingAs($manager)->post(route('admin.disputes.assign', $dispute))->assertRedirect();
    $this->actingAs($manager)->post(route('admin.disputes.resolve', $dispute), ['resolution_notes' => 'Resolved fairly.'])
        ->assertRedirect();
    expect($dispute->fresh()->status)->toBe(DisputeStatus::Resolved);

    $this->actingAs($manager)->get(route('admin.staff.index'))->assertForbidden();
});

test('support (dispute_manager, not just admin) can view a disputed engagement they are not party to', function () {
    $manager = User::factory()->create();
    $manager->assignRole('dispute_manager');
    ['engagement' => $engagement] = makeDisputedEngagement();

    $this->actingAs($manager)
        ->get(route('engagements.show-disputed', $engagement->id))
        ->assertOk()
        ->assertSee('Admin Resolution Panel');
});

test('support can view a disputed engagement but not the resolution form', function () {
    $support = User::factory()->create();
    $support->assignRole('support');
    ['engagement' => $engagement] = makeDisputedEngagement();

    $this->actingAs($support)
        ->get(route('engagements.show-disputed', $engagement->id))
        ->assertOk()
        ->assertDontSee('Admin Resolution Panel');
});

test('a bystander with no role cannot view a disputed engagement they are not party to', function () {
    $bystander = User::factory()->create();
    ['engagement' => $engagement] = makeDisputedEngagement();

    $this->actingAs($bystander)
        ->get(route('engagements.show-disputed', $engagement->id))
        ->assertRedirect();
});

test('resolve() redirects back to where the form was submitted from, not always the admin index', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    ['dispute' => $dispute, 'engagement' => $engagement] = makeDisputedEngagement();

    $referer = route('engagements.show-disputed', $engagement->id);

    $this->actingAs($admin)
        ->from($referer)
        ->post(route('admin.disputes.resolve', $dispute), ['resolution_notes' => 'Handled.'])
        ->assertRedirect($referer);
});

test('assign() 404s for a nonexistent dispute id instead of silently failing', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)->post(route('admin.disputes.assign', 999999))->assertNotFound();
});

test('resolution_amount cannot exceed the engagement net_amount', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    ['dispute' => $dispute] = makeDisputedEngagement(netAmount: 500);

    $this->actingAs($admin)
        ->post(route('admin.disputes.resolve', $dispute), [
            'resolution_notes' => 'Trying to overpay.',
            'resolution_amount' => 999,
        ])
        ->assertRedirect();

    expect(session('error'))->toContain('net amount');
    expect($dispute->fresh()->status)->toBe(DisputeStatus::Pending);
});

test('resolution_amount within the net_amount ceiling resolves successfully', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    ['dispute' => $dispute] = makeDisputedEngagement(netAmount: 500);

    $this->actingAs($admin)
        ->post(route('admin.disputes.resolve', $dispute), [
            'resolution_notes' => 'Partial payout.',
            'resolution_amount' => 300,
        ])
        ->assertSessionDoesntHaveErrors();

    expect($dispute->fresh())
        ->status->toBe(DisputeStatus::Resolved)
        ->resolution_amount->toEqual('300.00');
});

test('admin can assign and remove the support/dispute_manager role via the staff screen', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $staffCandidate = User::factory()->create();

    $this->actingAs($admin)
        ->patch(route('admin.staff.update-role', $staffCandidate), ['role' => 'dispute_manager'])
        ->assertRedirect();
    expect($staffCandidate->fresh()->hasRole('dispute_manager'))->toBeTrue();

    $this->actingAs($admin)
        ->patch(route('admin.staff.update-role', $staffCandidate), ['role' => null])
        ->assertRedirect();
    expect($staffCandidate->fresh()->roles)->toBeEmpty();
});

test('the staff screen cannot be used to change an existing admin\'s role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');

    $this->actingAs($admin)
        ->patch(route('admin.staff.update-role', $otherAdmin), ['role' => 'support'])
        ->assertRedirect();

    expect($otherAdmin->fresh()->hasRole('admin'))->toBeTrue()
        ->and($otherAdmin->fresh()->hasRole('support'))->toBeFalse();
});

test('the staff assignable list excludes existing admins', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $otherAdmin = User::factory()->create(['name' => 'Other Admin Person']);
    $otherAdmin->assignRole('admin');
    $regular = User::factory()->create(['name' => 'Regular Person']);

    $this->actingAs($admin)
        ->get(route('admin.staff.index'))
        ->assertOk()
        ->assertSee('Regular Person')
        ->assertDontSee('Other Admin Person');
});

test('the disputes index only shows "Assign to Me" to users who can actually resolve disputes', function () {
    $support = User::factory()->create();
    $support->assignRole('support');
    $manager = User::factory()->create();
    $manager->assignRole('dispute_manager');
    makeDisputedEngagement();

    $this->actingAs($support)->get(route('admin.disputes.index'))->assertDontSee('Assign to Me');
    $this->actingAs($manager)->get(route('admin.disputes.index'))->assertSee('Assign to Me');
});

test('staff nav links only show for users who hold the matching permission', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $support = User::factory()->create();
    $support->assignRole('support');
    $regular = User::factory()->create();

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertSee('Disputed Engagements')->assertSee('Staff Roles');

    $this->actingAs($support)->get(route('dashboard'))
        ->assertSee('Disputed Engagements')->assertDontSee('Staff Roles');

    $this->actingAs($regular)->get(route('dashboard'))
        ->assertDontSee('Disputed Engagements')->assertDontSee('Staff Roles');
});

test('an invalid role value on the staff screen is rejected', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $staffCandidate = User::factory()->create();

    $this->actingAs($admin)
        ->patch(route('admin.staff.update-role', $staffCandidate), ['role' => 'super_admin'])
        ->assertSessionHasErrors('role');
});
