<?php

use App\Enums\EngagementStatus;
use App\Models\JobApplication;
use App\Models\JobEngagement;
use App\Models\JobReview;
use App\Models\ModelJob;
use App\Models\Skill;
use App\Models\SocialNetwork;
use App\Models\Software;
use App\Models\User;
use App\Models\UserSocialLink;
use App\Support\Profile\ProfileCompleteness;
use Livewire\Volt\Volt;

/*
 * The profile page is a tabbed shell: a read-only overview plus the edit forms, one tab each.
 * Lazy loading is blocked outside production, so these requests double as N+1 guards.
 */

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Amina Otieno']);
    $this->actingAs($this->user);
});

function profileNetwork(string $name, bool $active = true): SocialNetwork
{
    return SocialNetwork::create([
        'name' => $name,
        'slug' => strtolower($name),
        'icon' => 'fab fa-'.strtolower($name),
        'color' => '#0A66C2',
        'base_url' => 'https://'.strtolower($name).'.com/',
        'is_active' => $active,
        'sort_order' => 1,
    ]);
}

it('renders the overview plus one panel per settings tab', function () {
    $this->get(route('profile'))
        ->assertOk()
        ->assertSeeVolt('profile.overview')
        ->assertSeeVolt('profile.update-profile-information-form')
        ->assertSeeVolt('profile.user-profile-form')
        ->assertSeeVolt('profile.social-links-form')
        ->assertSeeVolt('profile.update-password-form')
        ->assertSeeVolt('profile.two-factor-form')
        ->assertSeeVolt('profile.delete-user-form')
        ->assertSee('role="tablist"', false)
        ->assertSee('panel-overview', false)
        ->assertSee('panel-profile', false)
        ->assertSee('panel-security', false)
        ->assertSee('panel-account', false)
        ->assertDontSee('panel-links', false); // social accounts now live on the Edit profile tab
});

it('shows friendly empty states for a brand-new profile', function () {
    Volt::test('profile.overview')
        ->assertSee('Amina Otieno')
        ->assertSee('No reviews yet')
        ->assertSee("You haven't described your professional background yet.")
        ->assertSee('No skills added yet.')
        ->assertSee('No software added yet.')
        ->assertSee('Add your links')
        ->assertSee('No phone number added')
        ->assertSee('0%');
});

it('summarises everything the user has shared', function () {
    $this->user->getOrCreateProfile()->update([
        'professional_info' => 'Visualisation artist focused on hospitality projects.',
        'location' => 'Nairobi, Kenya',
        'telephone_number' => '+254 712 345 678',
    ]);
    $this->user->skills()->attach(Skill::create(['name' => 'Hard-Surface Modelling', 'is_active' => true]));
    $this->user->software()->attach(Software::create(['name' => 'Blender', 'is_active' => true]));
    UserSocialLink::create([
        'user_id' => $this->user->id,
        'social_network_id' => profileNetwork('LinkedIn')->id,
        'username' => 'amina',
        'url' => 'https://linkedin.com/in/amina',
        'is_public' => true,
    ]);

    Volt::test('profile.overview')
        ->assertSee('Visualisation artist focused on hospitality projects.')
        ->assertSee('Nairobi, Kenya')
        ->assertSee('+254 712 345 678')
        ->assertSee('Hard-Surface Modelling')
        ->assertSee('Blender')
        ->assertSee('LinkedIn')
        ->assertSee('https://linkedin.com/in/amina', false)
        ->assertSee('83%');
});

it('hides private links and links on inactive networks', function () {
    UserSocialLink::create([
        'user_id' => $this->user->id, 'social_network_id' => profileNetwork('Twitter')->id,
        'username' => 'private-one', 'url' => 'https://twitter.com/private', 'is_public' => false,
    ]);
    UserSocialLink::create([
        'user_id' => $this->user->id, 'social_network_id' => profileNetwork('Myspace', active: false)->id,
        'username' => 'retired-one', 'url' => 'https://myspace.com/retired', 'is_public' => true,
    ]);

    Volt::test('profile.overview')
        ->assertDontSee('private-one')
        ->assertDontSee('retired-one')
        ->assertSee('Add your links');
});

it('shows rating, breakdown, reviews and completed projects', function () {
    foreach ([[5, 'Delivered ahead of schedule'], [4, 'Great renders']] as [$rating, $text]) {
        $client = User::factory()->create(['name' => 'Kevin Mwangi']);
        $application = JobApplication::factory()->hired()->create([
            'job_id' => ModelJob::factory()->create(['user_id' => $client->id, 'title' => 'Lobby model']),
            'applicant_id' => $this->user->id,
            'poster_id' => $client->id,
        ]);
        $engagement = JobEngagement::create([
            'application_id' => $application->id,
            'status' => EngagementStatus::Completed,
            'agreed_amount' => 1000, 'service_fee' => 100, 'net_amount' => 900,
        ]);
        JobReview::create([
            'engagement_id' => $engagement->id, 'reviewer_id' => $client->id, 'reviewee_id' => $this->user->id,
            'rating' => $rating, 'review' => $text, 'is_public' => true,
        ]);
    }

    Volt::test('profile.overview')
        ->assertSee('4.5')
        ->assertSee('2 reviews')
        ->assertSee('Delivered ahead of schedule')
        ->assertSee('Kevin Mwangi')
        ->assertSee('Lobby model')
        ->assertSee('Projects completed');
});

it('refreshes itself when a form saves', function () {
    $component = Volt::test('profile.overview')->assertDontSee('Mombasa, Kenya');

    $this->user->getOrCreateProfile()->update(['location' => 'Mombasa, Kenya']);

    $component->dispatch('profile-details-updated')->assertSee('Mombasa, Kenya');
});

it("never shows another user's profile data", function () {
    $other = User::factory()->create(['name' => 'Someone Else']);
    $other->getOrCreateProfile()->update(['professional_info' => 'Secret bio', 'location' => 'Elsewhere']);
    $other->skills()->attach(Skill::create(['name' => 'Rigging', 'is_active' => true]));

    Volt::test('profile.overview')
        ->assertDontSee('Secret bio')
        ->assertDontSee('Elsewhere')
        ->assertDontSee('Rigging')
        ->assertDontSee('Someone Else');
});

it('computes completeness from photo, bio, location, phone, skills and software', function () {
    expect(ProfileCompleteness::for($this->user))->toMatchArray(['percent' => 0, 'next_step' => 'Add a profile photo']);

    $this->user->getOrCreateProfile()->update([
        'avatar' => 'avatars/me.png', 'professional_info' => 'Bio', 'location' => 'Nairobi', 'telephone_number' => '0712',
    ]);
    $this->user->skills()->attach(Skill::create(['name' => 'Modelling', 'is_active' => true]));
    $this->user->software()->attach(Software::create(['name' => 'Blender', 'is_active' => true]));

    $complete = ProfileCompleteness::for($this->user->fresh());

    expect($complete['percent'])->toBe(100)->and($complete['next_step'])->toBeNull();
});

it('lists completed projects as work history with the client, amount and rating', function () {
    $client = User::factory()->create(['name' => 'Kevin Mwangi']);
    $application = JobApplication::factory()->hired()->create([
        'job_id' => ModelJob::factory()->create(['user_id' => $client->id, 'title' => 'Hospital lobby model']),
        'applicant_id' => $this->user->id,
        'poster_id' => $client->id,
    ]);
    $engagement = JobEngagement::create([
        'application_id' => $application->id,
        'status' => EngagementStatus::Completed,
        'agreed_amount' => 1500, 'service_fee' => 150, 'net_amount' => 1350,
        'completed_at' => now()->subMonth(),
    ]);
    JobReview::create([
        'engagement_id' => $engagement->id, 'reviewer_id' => $client->id, 'reviewee_id' => $this->user->id,
        'rating' => 5, 'review' => 'Superb', 'is_public' => true,
    ]);

    Volt::test('profile.overview')
        ->assertSee('Work history')
        ->assertSee('Hospital lobby model')
        ->assertSee('for Kevin Mwangi')
        ->assertSee(config('app.currency_symbol').'1,350')
        ->assertSee('Total earned');
});

it('does not count active or cancelled engagements as completed work or earnings', function () {
    foreach ([EngagementStatus::Active, EngagementStatus::Cancelled] as $status) {
        $application = JobApplication::factory()->hired()->create(['applicant_id' => $this->user->id]);
        JobEngagement::create([
            'application_id' => $application->id, 'status' => $status,
            'agreed_amount' => 9000, 'service_fee' => 0, 'net_amount' => 9000,
        ]);
    }

    Volt::test('profile.overview')
        ->assertSee('1 in progress')
        ->assertSee(config('app.currency_symbol').'0')
        ->assertDontSee(config('app.currency_symbol').'9,000')
        ->assertSee('Completed projects will show up here');
});

it('hands the edit form every skill and software option for the tag pickers', function () {
    Skill::create(['name' => 'Photogrammetry', 'is_active' => true]);
    Software::create(['name' => 'Houdini', 'is_active' => true]);

    $this->get(route('profile'))
        ->assertOk()
        ->assertSee('Photogrammetry')
        ->assertSee('Houdini')
        ->assertSee('Area of expertise')
        ->assertSee('Preferred software');
});
