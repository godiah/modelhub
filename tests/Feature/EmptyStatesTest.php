<?php

use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;

/*
 * Every page that has nothing to list shows the same empty state: the design first set out on My licences (a card with an inset frame, a tinted
 * icon, a title, a sentence and the thing to do), from the one <x-empty-state> component.
 */

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('shows the standard empty state on every member page that has nothing in it', function (string $route, string $title) {
    $page = $this->get(route($route))->assertOk();

    $page->assertSee('data-empty-state', false)->assertSee($title);
    // The shared design: the outer card with its inset frame, and the tinted icon circle
    $page->assertSee('rounded-2xl border border-neutral-200 bg-white p-2 shadow-sm', false)->assertSee('rounded-full bg-primary/10', false);
})->with([
    'my licences' => ['licences.index', 'No licences yet'],
    'engagements' => ['engagements.index', 'No engagements yet'],
    'my applications' => ['applications.my', 'No applications yet'],
    'application drafts' => ['applications.drafts', 'No drafts'],
    'archived applications' => ['applications.archived', 'Nothing archived'],
    'posted projects' => ['my-jobs.index', 'You have not posted any projects yet'],
    'archived projects' => ['my-jobs.archived.posted-jobs', 'Nothing archived'],
    'wishlist' => ['wishlist.index', 'Nothing saved yet'],
    'earnings' => ['earnings.index', 'No earnings yet'],
    'browse models' => ['models.index', 'No models yet'],
]);

it('shows it for a seller with no models, and inside the notification list with its own frame', function () {
    SellerProfile::factory()->approved()->create(['user_id' => $this->user->id]);

    $this->get(route('seller.models.index'))->assertOk()->assertSee('data-empty-state', false)->assertSee('No models yet')->assertSee('rounded-2xl border border-neutral-200 bg-white p-2 shadow-sm', false);

    // Lists that sit inside a card of their own get the same content without a second frame
    $this->get(route('engagements.archived'))->assertOk()->assertSee('data-empty-state', false)->assertSee('Nothing archived')->assertSee('rounded-full bg-primary/10', false);
    $page = $this->get(route('notifications.index'))->assertOk()->assertSee('data-empty-state', false)->assertSee('No notifications yet')->assertSee('rounded-full bg-primary/10', false);
    expect(substr_count($page->getContent(), 'rounded-2xl border border-neutral-200 bg-white p-2 shadow-sm'))->toBe(0);
});

it('uses the same design for a search or filter that finds nothing', function () {
    Product::factory()->published()->create(['title' => 'Oak armchair']);
    $this->get(route('models.index', ['q' => 'zzzzzz']))->assertOk()->assertSee('data-empty-state', false)->assertSee('No models match');
    $this->get(route('licences.index', ['q' => 'zzz']))->assertOk();
});

it('no longer has the old one-off empty states', function () {
    $this->get(route('engagements.index'))->assertDontSee('rounded-2xl p-12 text-center', false)->assertDontSee('bg-neutral-100 text-neutral-500', false);
    $this->get(route('engagements.archived'))->assertDontSee('No Current Engagements Found')->assertDontSee('from-primary to-secondary', false);
});
