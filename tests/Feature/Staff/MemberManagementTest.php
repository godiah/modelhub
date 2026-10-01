<?php

use App\Models\JobApplication;
use App\Models\ModelJob;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\StaffActivity;
use App\Models\User;
use App\Notifications\AccountReinstatedNotification;
use App\Notifications\AccountSuspendedNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

/*
 * The member directory and a member's page: who sees what (contact details are masked unless the role allows them), what staff
 * can do (suspend, notes, password and verification help), and that a suspension is actually enforced across the member app.
 */

function memberNamed(string $name, array $overrides = []): User
{
    return User::factory()->create(['name' => $name] + $overrides);
}

/** ---------------------------------------------------------------- access */
it('keeps the member pages to staff who may view members, and the actions to those who may manage them', function () {
    $member = memberNamed('Wanjiru Kamau');

    $this->actingAs(staffWith(), 'staff')->get(route('admin.members.index'))->assertForbidden();
    $this->actingAs(staffWith('Marketplace moderator'), 'staff')->get(route('admin.members.show', $member))->assertForbidden();
    $this->actingAs(staffWith('Auditor'), 'staff')->get(route('admin.members.index'))->assertOk();
    $this->actingAs(staffWith('Auditor'), 'staff')->get(route('admin.members.show', $member))->assertOk();

    $auditor = staffWith('Auditor');
    foreach (['suspend' => ['reason' => 'Spamming the board.'], 'reinstate' => [], 'notes.store' => ['body' => 'A note.'], 'password-reset' => [], 'verification' => []] as $route => $data) {
        $name = str_contains($route, '.') ? "admin.members.{$route}" : "admin.members.{$route}";
        $this->actingAs($auditor, 'staff')->post(route($name, $member), $data)->assertForbidden();
    }
    expect($member->fresh()->isSuspended())->toBeFalse();

    // Members have no way in
    Auth::guard('staff')->forgetUser();
    $this->actingAs($member)->get(route('admin.members.index'))->assertRedirect(route('admin.login'));
});

/** ---------------------------------------------------------------- the directory */
it('lists members with their status, counts and filters, newest first', function () {
    $active = memberNamed('Active Person');
    $suspended = memberNamed('Suspended Person', ['suspended_at' => now(), 'suspended_reason' => 'Spam.']);
    $unverified = memberNamed('Unverified Person', ['email_verified_at' => null]);
    SellerProfile::factory()->approved()->create(['user_id' => $active->id]);
    ModelJob::factory()->create(['user_id' => $active->id]);

    $staff = staffWith('Platform manager');
    $this->actingAs($staff, 'staff')->get(route('admin.members.index'))->assertOk()
        ->assertSee('Active Person')->assertSee('Suspended Person')->assertSee('Unverified Person')->assertSee('1 projects');

    $this->get(route('admin.members.index', ['status' => 'suspended']))->assertSee('Suspended Person')->assertDontSee('Active Person');
    $this->get(route('admin.members.index', ['status' => 'unverified']))->assertSee('Unverified Person')->assertDontSee('Suspended Person');
    $this->get(route('admin.members.index', ['status' => 'active']))->assertSee('Active Person')->assertDontSee('Suspended Person');
    $this->get(route('admin.members.index', ['activity' => 'sellers']))->assertSee('Active Person')->assertDontSee('Unverified Person');
    $this->get(route('admin.members.index', ['activity' => 'hirers']))->assertSee('Active Person')->assertDontSee('Unverified Person');
    $this->get(route('admin.members.index', ['q' => 'Unverified']))->assertSee('Unverified Person')->assertDontSee('Active Person');
    $this->get(route('admin.members.index', ['status' => 'bogus', 'activity' => 'bogus']))->assertOk()->assertSee('Active Person');
});

it('masks email addresses and phone numbers for roles without contact details, and never searches by email for them', function () {
    $member = memberNamed('Wanjiru Kamau', ['email' => 'wanjiru.kamau@example.test']);
    $member->getOrCreateProfile()->update(['telephone_number' => '+254 712 345 678']);

    $auditor = staffWith('Auditor');
    $this->actingAs($auditor, 'staff')->get(route('admin.members.index'))->assertSee('w••••••@example.test', false)->assertDontSee('wanjiru.kamau@example.test');
    $this->get(route('admin.members.show', $member))->assertSee('Full contact details are hidden for your role.')->assertSee('•••• 678', false)->assertDontSee('wanjiru.kamau@example.test')->assertDontSee('712 345');
    // An email search would unmask addresses a letter at a time, so it is ignored without the permission
    $this->get(route('admin.members.index', ['q' => 'wanjiru.kamau@']))->assertSee('Nobody here');

    $support = staffWith('Support');
    $this->actingAs($support, 'staff')->get(route('admin.members.index'))->assertSee('wanjiru.kamau@example.test');
    $this->get(route('admin.members.show', $member))->assertSee('wanjiru.kamau@example.test')->assertSee('+254 712 345 678')->assertDontSee('hidden for your role');
    $this->get(route('admin.members.index', ['q' => 'wanjiru.kamau@']))->assertSee('Wanjiru Kamau');
});

it('records when staff look at real contact details, at most once an hour per member, and not for masked views', function () {
    $member = memberNamed('Wanjiru Kamau');
    $support = staffWith('Support');

    $this->actingAs($support, 'staff')->get(route('admin.members.show', $member))->assertOk();
    $this->get(route('admin.members.show', $member));
    expect(StaffActivity::where('action', 'member.contact-viewed')->where('staff_id', $support->id)->count())->toBe(1);

    $this->actingAs(staffWith('Auditor'), 'staff')->get(route('admin.members.show', $member));
    expect(StaffActivity::where('action', 'member.contact-viewed')->count())->toBe(1);
});

it('shows a member\'s activity: projects, applications, hires, models, store and notes', function () {
    $member = memberNamed('Kevin Mwangi');
    $project = ModelJob::factory()->create(['user_id' => $member->id, 'title' => 'Hospital lobby model']);
    $application = JobApplication::factory()->create(['applicant_id' => $member->id, 'job_id' => ModelJob::factory()->create(['title' => 'Villa render'])->id]);
    SellerProfile::factory()->approved()->create(['user_id' => $member->id, 'display_name' => 'Kevin 3D Studio']);
    Product::factory()->published()->create(['user_id' => $member->id, 'title' => 'Oak armchair model']);

    $this->actingAs(staffWith('Platform manager'), 'staff')->get(route('admin.members.show', $member))->assertOk()
        ->assertSee('Kevin Mwangi')->assertSee('Hospital lobby model')->assertSee('Villa render')->assertSee('Oak armchair model')->assertSee('Kevin 3D Studio')->assertSee('Open the store')
        ->assertSee(route('admin.projects.show', $project), false);
});

/** ---------------------------------------------------------------- suspending */
it('suspends a member with a reason: signed out, emailed, logged, and shown on their page', function () {
    Notification::fake();
    $member = memberNamed('Wanjiru Kamau');
    $manager = staffWith('Platform manager');

    $this->actingAs($manager, 'staff')->post(route('admin.members.suspend', $member), ['reason' => 'Posted scam projects.'])->assertSessionHas('success');

    $member->refresh();
    expect($member->isSuspended())->toBeTrue()->and($member->suspended_reason)->toBe('Posted scam projects.')->and($member->suspended_by)->toBe($manager->id);
    Notification::assertSentTo($member, AccountSuspendedNotification::class, fn ($n) => $n->reason === 'Posted scam projects.');
    expect(StaffActivity::where('action', 'member.suspended')->where('staff_id', $manager->id)->first()->details['reason'])->toBe('Posted scam projects.');

    $this->get(route('admin.members.show', $member))->assertSee('Suspended')->assertSee('Posted scam projects.')->assertSee('Reinstate');
});

it('requires a real reason, and refuses to suspend twice or to reinstate someone who is not suspended', function () {
    $member = memberNamed('Wanjiru Kamau');
    $this->actingAs(staffWith('Platform manager'), 'staff');

    $this->post(route('admin.members.suspend', $member), ['reason' => ''])->assertSessionHasErrors('reason');
    $this->post(route('admin.members.suspend', $member), ['reason' => 'no'])->assertSessionHasErrors('reason');
    expect($member->fresh()->isSuspended())->toBeFalse();

    $this->post(route('admin.members.reinstate', $member))->assertSessionHas('error');
    $this->post(route('admin.members.suspend', $member), ['reason' => 'A proper reason.']);
    $this->post(route('admin.members.suspend', $member), ['reason' => 'Another reason.'])->assertSessionHas('error');
});

it('reinstates a member, who is told and can sign in again', function () {
    Notification::fake();
    $member = memberNamed('Wanjiru Kamau', ['suspended_at' => now(), 'suspended_reason' => 'Spam.']);

    $this->actingAs(staffWith('Platform manager'), 'staff')->post(route('admin.members.reinstate', $member))->assertSessionHas('success');

    expect($member->fresh())->suspended_at->toBeNull()->suspended_reason->toBeNull()->suspended_by->toBeNull();
    Notification::assertSentTo($member, AccountReinstatedNotification::class);
});

it('stops a suspended member signing in, with a clear message only once they prove who they are', function () {
    $member = memberNamed('Wanjiru Kamau', ['email' => 'w@example.test', 'suspended_at' => now(), 'suspended_reason' => 'Spam.']);

    Volt::test('pages.auth.login')->set('form.email', 'w@example.test')->set('form.password', 'password')->call('login')
        ->assertHasErrors(['form.email' => 'This account has been suspended. If you think that is a mistake, contact support.']);
    $this->assertGuest('web');

    // A wrong password gets the ordinary message, so the form does not reveal which accounts are suspended
    $wrong = Volt::test('pages.auth.login')->set('form.email', 'w@example.test')->set('form.password', 'wrong')->call('login')->assertHasErrors(['form.email']);
    expect($wrong->errors()->first('form.email'))->not->toContain('suspended');
});

it('signs a suspended member out on their next request', function () {
    $member = memberNamed('Wanjiru Kamau');
    $this->actingAs($member)->get(route('dashboard'))->assertOk();

    $member->forceFill(['suspended_at' => now(), 'suspended_reason' => 'Spam.'])->save();

    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest('web');
});

it('hides a suspended member\'s models, store, projects and landing-page presence until they are reinstated', function () {
    $seller = memberNamed('Kevin Mwangi');
    $store = SellerProfile::factory()->approved()->create(['user_id' => $seller->id, 'display_name' => 'Kevin 3D Studio', 'rating_avg' => 4.8, 'rating_count' => 9]);
    $model = Product::factory()->published()->create(['user_id' => $seller->id, 'title' => 'Oak armchair model']);
    $project = ModelJob::factory()->create(['user_id' => $seller->id, 'title' => 'Atrium render brief', 'is_active' => true, 'no_deadline' => true]);

    expect(Product::published()->whereKey($model->id)->exists())->toBeTrue()->and(ModelJob::openForApplications()->whereKey($project->id)->exists())->toBeTrue();
    $this->get(route('sellers.show', $store->slug))->assertOk();
    $this->get('/')->assertSee('Kevin 3D Studio');

    $seller->forceFill(['suspended_at' => now()])->save();

    expect(Product::published()->whereKey($model->id)->exists())->toBeFalse()->and(ModelJob::openForApplications()->whereKey($project->id)->exists())->toBeFalse();
    $this->get(route('models.show', $model))->assertNotFound();
    $this->get(route('sellers.show', $store->slug))->assertNotFound();
    $this->get(route('models.index'))->assertDontSee('Oak armchair model');
    $this->get('/')->assertDontSee('Kevin 3D Studio');
    $this->get(route('jobs.browse'))->assertDontSee('Atrium render brief');

    $seller->forceFill(['suspended_at' => null])->save();
    $this->get(route('sellers.show', $store->slug))->assertOk();
    $this->get(route('jobs.browse'))->assertSee('Atrium render brief');
});

/** ---------------------------------------------------------------- notes, help */
it('keeps private staff notes on a member, shown only to staff, and logs adding one', function () {
    $member = memberNamed('Wanjiru Kamau');
    $manager = staffWith('Platform manager');

    $this->actingAs($manager, 'staff')->post(route('admin.members.notes.store', $member), ['body' => "Warned about duplicate listings\non 3 Oct."])->assertSessionHas('success');
    $this->post(route('admin.members.notes.store', $member), ['body' => ''])->assertSessionHasErrors('body');

    expect($member->notes)->toHaveCount(1)->and($member->notes->first()->staff_id)->toBe($manager->id);
    $this->get(route('admin.members.show', $member))->assertSee('Warned about duplicate listings')->assertSee($manager->name);
    expect(StaffActivity::where('action', 'member.note-added')->exists())->toBeTrue();

    // Staff who cannot manage see notes, but cannot add one
    $this->actingAs(staffWith('Auditor'), 'staff')->get(route('admin.members.show', $member))->assertSee('Warned about duplicate listings')->assertDontSee('Add note');

    // The member never sees them
    Auth::guard('staff')->forgetUser();
    $this->actingAs($member)->get(route('profile'))->assertOk()->assertDontSee('Warned about duplicate listings');
});

it('sends a password reset link or a verification email, without ever touching a password', function () {
    Notification::fake();
    $member = memberNamed('Wanjiru Kamau', ['email_verified_at' => null]);
    $verified = memberNamed('Verified Person');
    $manager = staffWith('Platform manager');
    $this->actingAs($manager, 'staff');

    $this->post(route('admin.members.password-reset', $member))->assertSessionHas('success');
    Notification::assertSentTo($member, ResetPassword::class);

    $this->post(route('admin.members.verification', $member))->assertSessionHas('success');
    Notification::assertSentTo($member, VerifyEmail::class);

    $this->post(route('admin.members.verification', $verified))->assertSessionHas('error');
    expect(StaffActivity::whereIn('action', ['member.password-reset-sent', 'member.verification-sent'])->count())->toBe(2);
});

it('records when a member last signed in', function () {
    $member = memberNamed('Wanjiru Kamau', ['email' => 'w@example.test']);
    expect($member->last_login_at)->toBeNull();

    Volt::test('pages.auth.login')->set('form.email', 'w@example.test')->set('form.password', 'password')->call('login')->assertHasNoErrors();

    expect($member->fresh()->last_login_at)->not->toBeNull();
});
