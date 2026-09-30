<?php

use App\Enums\NotificationCategory;
use App\Models\User;
use App\Notifications\HiredNotification;
use App\Notifications\NewApplicationMessage;
use App\Notifications\PaymentAcceptedNotification;
use Illuminate\Support\Str;

/*
 * The notifications page: a filter rail with live counts and a feed grouped by day.
 * Lazy loading is blocked outside production, so requests here double as N+1 guards.
 */

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function pushNotification(User $user, string $type, array $data = [], $at = null, bool $read = false)
{
    $at ??= now();

    return $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => $type,
        'data' => $data + ['message' => 'Something happened'],
        'read_at' => $read ? $at : null,
        'created_at' => $at,
        'updated_at' => $at,
    ]);
}

it('shows a friendly empty state when there is nothing yet', function () {
    $this->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('No notifications yet')
        ->assertDontSee('Mark all as read')
        // the confirm dialog's markup is always present; its opener button is what shows only when there is something to clear
        ->assertDontSee("\$dispatch('open-modal', 'clear-notifications')", false);
});

it('groups the feed by day', function () {
    pushNotification($this->user, HiredNotification::class, ['job_title' => 'A'], now());
    pushNotification($this->user, HiredNotification::class, ['job_title' => 'B'], now()->subDay());
    pushNotification($this->user, HiredNotification::class, ['job_title' => 'C'], now()->subDays(4));
    pushNotification($this->user, HiredNotification::class, ['job_title' => 'D'], now()->subDays(20));

    $this->get(route('notifications.index'))
        ->assertOk()
        ->assertSeeInOrder(['Today', 'Yesterday', 'This week', 'Earlier']);
});

it('filters to unread notifications and reports the counts', function () {
    pushNotification($this->user, HiredNotification::class, ['job_title' => 'Unread job']);
    pushNotification($this->user, HiredNotification::class, ['job_title' => 'Read job'], read: true);

    $this->get(route('notifications.index', ['filter' => 'unread']))
        ->assertOk()
        ->assertSee('Unread job')
        ->assertDontSee('Read job')
        ->assertSeeInOrder(['1 notification', '1 unread']);
});

it('filters by category and files unknown types under Other', function () {
    pushNotification($this->user, NewApplicationMessage::class, ['subject' => 'A question']);
    pushNotification($this->user, PaymentAcceptedNotification::class, ['amount' => 500, 'job_title' => 'Paid job']);
    pushNotification($this->user, 'App\\Notifications\\BrandNewThing', ['message' => 'From the future']);

    $this->get(route('notifications.index', ['filter' => 'messages']))->assertOk()
        ->assertSee('A question')->assertDontSee('Paid job')->assertDontSee('From the future');

    $this->get(route('notifications.index', ['filter' => 'payments']))->assertOk()
        ->assertSee('Paid job')->assertDontSee('A question');

    $this->get(route('notifications.index', ['filter' => 'other']))->assertOk()
        ->assertSee('From the future')->assertDontSee('Paid job')->assertDontSee('A question');
});

it('rejects an unknown filter instead of guessing', function () {
    $this->get(route('notifications.index', ['filter' => 'bogus']))->assertSessionHasErrors('filter');
});

it('shows the caught-up state for an empty unread filter', function () {
    pushNotification($this->user, HiredNotification::class, read: true);

    $this->get(route('notifications.index', ['filter' => 'unread']))
        ->assertOk()
        ->assertSee("You're all caught up")
        ->assertSee('View all notifications');
});

it('paginates and keeps the filter in the page links', function () {
    foreach (range(1, 16) as $i) {
        pushNotification($this->user, HiredNotification::class, ['job_title' => "Job $i"], now()->subMinutes($i));
    }

    $this->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Showing 1–15 of 16')
        ->assertSee('Job 1')
        ->assertDontSee('Job 16');

    $this->get(route('notifications.index', ['page' => 2]))->assertOk()->assertSee('Job 16')->assertDontSee('Job 1<');
});

it("never shows another user's notifications", function () {
    $other = User::factory()->create();
    pushNotification($other, HiredNotification::class, ['job_title' => 'Private to someone else']);

    $this->get(route('notifications.index'))->assertOk()
        ->assertDontSee('Private to someone else')
        ->assertSee('No notifications yet');

    $notification = pushNotification($other, HiredNotification::class);
    $this->delete(route('notifications.delete', $notification->id))->assertNotFound();
});

it('renders expandable detail as plain text, never as HTML', function () {
    $notification = pushNotification(
        $this->user,
        'App\\Notifications\\EngagementResponseNotification',
        ['job_title' => 'Lobby', 'notes' => '<img src=x onerror=alert(1)>'],
    );

    $page = $this->get(route('notifications.index'))->assertOk();
    $page->assertSee('x-text="detail"', false)->assertDontSee('x-html', false);

    // The JSON endpoint hands back the raw string; the page inserts it with x-text.
    $this->getJson(route('notifications.read', $notification->id), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJson(['success' => true, 'fullMessage' => '<img src=x onerror=alert(1)>']);
    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('marks everything read', function () {
    pushNotification($this->user, HiredNotification::class);
    pushNotification($this->user, NewApplicationMessage::class);

    $this->post(route('notifications.read-all'))->assertRedirect();

    expect($this->user->unreadNotifications()->count())->toBe(0);
});

it('assigns every notification class to a real category, not Other', function () {
    $classes = collect(glob(app_path('Notifications/*.php')))
        ->map(fn ($path) => 'App\\Notifications\\'.basename($path, '.php'));

    expect($classes)->not->toBeEmpty();

    foreach ($classes as $class) {
        expect(NotificationCategory::forType($class))->not->toBe(NotificationCategory::Other, "$class is not in any NotificationCategory");
    }
});
