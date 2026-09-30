<?php

use App\Enums\EngagementStatus;
use App\Models\JobApplication;
use App\Models\JobDeliverable;
use App\Models\JobEngagement;
use App\Models\JobReview;
use App\Models\Message;
use App\Models\ModelJob;
use App\Models\User;
use Illuminate\Support\Str;

/*
 * The dashboard is an action-focused home. Lazy loading is blocked outside production, so every request
 * below also proves the page eager-loads what it reads.
 */

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Amina Otieno']);
    $this->actingAs($this->user);
});

/** An engagement between the signed-in user and a counterpart, in the given role. */
function dashboardEngagement(User $me, EngagementStatus $status, bool $asApplicant, string $title = 'Villa exterior render', array $overrides = []): JobEngagement
{
    $other = User::factory()->create(['name' => 'Kevin Mwangi']);

    $application = JobApplication::factory()->hired()->create([
        'job_id' => ModelJob::factory()->create(['title' => $title, 'user_id' => $asApplicant ? $other->id : $me->id]),
        'applicant_id' => $asApplicant ? $me->id : $other->id,
        'poster_id' => $asApplicant ? $other->id : $me->id,
    ]);

    return JobEngagement::create([
        'application_id' => $application->id,
        'status' => $status,
        'agreed_amount' => $application->offer_amount,
        'service_fee' => $application->service_fee,
        'net_amount' => $overrides['net_amount'] ?? $application->net_amount,
        'started_at' => now(),
    ]);
}

function dashboardDeliverable(JobEngagement $engagement, string $status, ?string $due = null, string $title = 'First draft'): JobDeliverable
{
    return JobDeliverable::create([
        'engagement_id' => $engagement->id,
        'title' => $title,
        'description' => 'Details',
        'due_date' => $due,
        'status' => $status,
    ]);
}

it('greets the user by first name and shows the empty states for a new account', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Welcome back, Amina')
        ->assertSee("You're all caught up")
        ->assertSee('No active engagements yet')
        ->assertSee('No applications yet')
        ->assertSee('No projects posted yet')
        ->assertSee('No notifications yet')
        ->assertSee('No reviews yet')
        ->assertSee('0%')
        ->assertDontSee('Recent reviews');
});

it('surfaces an offer awaiting the freelancer first', function () {
    dashboardEngagement($this->user, EngagementStatus::EmployerAccepted, asApplicant: true, title: 'Lobby model');

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Respond to offer')
        ->assertSee('Lobby model')
        ->assertSee('1 thing needs your attention');
});

it('does not ask the poster to respond to their own pending offer', function () {
    dashboardEngagement($this->user, EngagementStatus::EmployerAccepted, asApplicant: false);

    $this->get(route('dashboard'))->assertOk()->assertDontSee('Respond to offer')->assertSee("You're all caught up");
});

it('asks the client to review a submitted deliverable and the freelancer to revise a rejected one', function () {
    $asClient = dashboardEngagement($this->user, EngagementStatus::Active, asApplicant: false, title: 'Client job');
    dashboardDeliverable($asClient, 'submitted', title: 'Massing model');

    $asFreelancer = dashboardEngagement($this->user, EngagementStatus::Active, asApplicant: true, title: 'Freelance job');
    dashboardDeliverable($asFreelancer, 'rejected', title: 'Lighting pass');

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Review deliverable: Massing model')
        ->assertSee('Revise deliverable: Lighting pass')
        ->assertSee('2 things need your attention');
});

it('flags overdue and due-soon deliverables for the freelancer only', function () {
    $mine = dashboardEngagement($this->user, EngagementStatus::Active, asApplicant: true);
    dashboardDeliverable($mine, 'pending', now()->subDays(2)->toDateString(), 'Late one');
    dashboardDeliverable($mine, 'pending', now()->addDays(2)->toDateString(), 'Soon one');
    dashboardDeliverable($mine, 'pending', now()->addDays(30)->toDateString(), 'Far one');

    $theirs = dashboardEngagement($this->user, EngagementStatus::Active, asApplicant: false);
    dashboardDeliverable($theirs, 'pending', now()->subDays(2)->toDateString(), 'Their late one');

    $this->get(route('dashboard'))->assertOk()
        ->assertSee('Overdue: Late one')
        ->assertSee('Due soon: Soon one')
        ->assertDontSee('Far one')
        ->assertDontSee('Their late one');
});

it('counts unread messages from the other party and new applicants on my projects', function () {
    $engagement = dashboardEngagement($this->user, EngagementStatus::Active, asApplicant: true, title: 'Chat job');
    $senderId = $engagement->application->poster_id;
    Message::create(['engagement_id' => $engagement->id, 'sender_id' => $senderId, 'content' => 'Hello']);
    Message::create(['engagement_id' => $engagement->id, 'sender_id' => $senderId, 'content' => 'Ping']);
    Message::create(['engagement_id' => $engagement->id, 'sender_id' => $this->user->id, 'content' => 'My own message']);

    $job = ModelJob::factory()->create(['user_id' => $this->user->id, 'title' => 'My open project']);
    JobApplication::factory()->count(3)->create(['job_id' => $job->id, 'poster_id' => $this->user->id]);

    $this->get(route('dashboard'))->assertOk()
        ->assertSee('2 unread messages')
        ->assertSee('3 new applications')
        ->assertSee('My open project');
});

it('orders the attention queue by urgency', function () {
    $job = ModelJob::factory()->create(['user_id' => $this->user->id]);
    JobApplication::factory()->create(['job_id' => $job->id, 'poster_id' => $this->user->id]);

    $mine = dashboardEngagement($this->user, EngagementStatus::Active, asApplicant: true);
    dashboardDeliverable($mine, 'pending', now()->subDay()->toDateString(), 'Late one');
    dashboardEngagement($this->user, EngagementStatus::EmployerAccepted, asApplicant: true);

    $this->get(route('dashboard'))->assertOk()->assertSeeInOrder(['Respond to offer', 'Overdue: Late one', '1 new application']);
});

it('shows a card for work in flight with role, counterpart, progress and deadline', function () {
    $engagement = dashboardEngagement($this->user, EngagementStatus::Active, asApplicant: true, title: 'Hospital lobby model');
    dashboardDeliverable($engagement, 'approved', now()->subDays(5)->toDateString(), 'Done one');
    dashboardDeliverable($engagement, 'pending', now()->addDays(10)->toDateString(), 'Next one');

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Hospital lobby model')
        ->assertSee('Freelancer')
        ->assertSee('with Kevin Mwangi')
        ->assertSee('1 of 2 deliverables approved')
        ->assertSee('aria-valuenow="50"', false)
        ->assertSee('Next due');
});

it('totals earnings from completed work as a freelancer only', function () {
    dashboardEngagement($this->user, EngagementStatus::Completed, asApplicant: true, overrides: ['net_amount' => 1500]);
    dashboardEngagement($this->user, EngagementStatus::Completed, asApplicant: true, overrides: ['net_amount' => 500]);
    dashboardEngagement($this->user, EngagementStatus::Completed, asApplicant: false, overrides: ['net_amount' => 9999]);

    $this->get(route('dashboard'))->assertOk()->assertSee(config('app.currency_symbol').'2,000');
});

it('lists recent applications, posted projects and notifications', function () {
    $job = ModelJob::factory()->create(['title' => 'Applied-to project']);
    JobApplication::factory()->create(['job_id' => $job->id, 'applicant_id' => $this->user->id, 'poster_id' => $job->user_id]);
    ModelJob::factory()->create(['user_id' => $this->user->id, 'title' => 'Posted by me', 'applicants_count' => 4]);
    $this->user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\Unknown',
        'data' => ['message' => 'Your offer was accepted'],
    ]);

    $this->get(route('dashboard'))->assertOk()
        ->assertSee('Applied-to project')
        ->assertSee('Submitted')
        ->assertSee('Posted by me')
        ->assertSee('4 applicants')
        ->assertSee('Your offer was accepted');
});

it('shows recent public reviews once there are some, with the rating on the profile card', function () {
    $engagement = dashboardEngagement($this->user, EngagementStatus::Completed, asApplicant: true);
    JobReview::create([
        'engagement_id' => $engagement->id,
        'reviewer_id' => $engagement->application->poster_id,
        'reviewee_id' => $this->user->id,
        'rating' => 5,
        'review' => 'Delivered ahead of schedule',
        'is_public' => true,
    ]);

    $this->get(route('dashboard'))->assertOk()
        ->assertSee('Recent reviews')
        ->assertSee('Delivered ahead of schedule')
        ->assertSee('5.0')
        ->assertSee('(1 review)');
});

it("never shows other people's engagements or notifications", function () {
    $stranger = User::factory()->create();
    dashboardEngagement($stranger, EngagementStatus::EmployerAccepted, asApplicant: true, title: 'Not mine');
    $stranger->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\Unknown',
        'data' => ['message' => 'Private to the stranger'],
    ]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Not mine')
        ->assertDontSee('Private to the stranger')
        ->assertSee("You're all caught up");
});
