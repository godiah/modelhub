<?php

use App\Models\ModelJob;
use App\Models\User;

/*
 * The public landing page: honest copy (only features that exist), real open projects, working links only.
 * Every test asserts OK first so a 500 cannot satisfy loose text assertions.
 */

it('shows the landing page to guests with both pillars and the models teaser', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Meet the best')
        ->assertSee('3D talent')
        ->assertSee('Hire talent')
        ->assertSee('Find work')
        ->assertSee('How it works')
        ->assertSee('A marketplace for 3D models')
        ->assertSee('Coming soon');
});

it('links its calls to action to real routes', function () {
    $this->get('/')->assertOk()
        ->assertSee(route('jobs.create'), false)
        ->assertSee(route('jobs.browse'), false)
        ->assertSee(route('register'), false)
        ->assertSee(route('login'), false);
});

it('no longer makes claims about a model catalogue that does not exist', function () {
    $this->get('/')->assertOk()
        ->assertDontSee('2 million')
        ->assertDontSee('Browse Categories')
        ->assertDontSee('Subscribe to our newsletter')
        ->assertDontSee('Connect With Us');
});

it('lists the newest open projects, and only open ones', function () {
    $client = User::factory()->create();
    ModelJob::factory()->create(['user_id' => $client->id, 'title' => 'Open lobby model']);
    ModelJob::factory()->create(['user_id' => $client->id, 'title' => 'Closed villa', 'is_active' => false]);
    ModelJob::factory()->create(['user_id' => $client->id, 'title' => 'Archived tower', 'is_archived' => true]);

    $this->get('/')->assertOk()
        ->assertSee('Open projects right now')
        ->assertSee('Open lobby model')
        ->assertDontSee('Closed villa')
        ->assertDontSee('Archived tower');
});

it('leaves the open-projects section out when there are none', function () {
    $this->get('/')->assertOk()->assertDontSee('Open projects right now');
});

it('sends signed-in users to their dashboard instead', function () {
    $this->actingAs(User::factory()->create())->get('/')->assertRedirect();
});

it('has a footer with only real destinations', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain(route('jobs.browse'))->not->toContain('href="#"');
});

it('puts every guest page on the same site header and footer', function () {
    foreach ([route('jobs.browse'), route('jobs.apply', ModelJob::factory()->create()->slug)] as $url) {
        $this->get($url)->assertOk()->assertSee('Join free')->assertSee('How it works')->assertDontSee('Connect With Us');
    }
});

it('explains how it works for clients by default and for freelancers on request', function () {
    $this->get(route('jobs.index'))->assertOk()
        ->assertSee('Hire the right 3D artist for your project')
        ->assertSee('Get your project done in 3 steps')
        ->assertSee(route('jobs.create'), false);

    $this->get(route('jobs.index', ['for' => 'work']))->assertOk()
        ->assertSee('Find 3D projects that fit your skills')
        ->assertSee('Start earning in 3 steps')
        ->assertSee(route('jobs.browse'), false);

    $this->get(route('jobs.index', ['for' => 'nonsense']))->assertOk()->assertSee('Hire the right 3D artist for your project');
});

it('marks the current page in the navigation', function () {
    expect($this->get(route('jobs.browse'))->assertOk()->getContent())->toMatch('/data-active="true"\s+aria-current="page"/');
    expect($this->get(route('jobs.index'))->assertOk()->getContent())->toMatch('/data-active="true"\s+aria-current="page"/');
    $this->get('/')->assertOk()->assertSee('data-spy="#hire"', false)->assertDontSee('aria-current="page"', false);
});

it('tells guests a free account is needed to post or apply', function () {
    $this->get('/')->assertOk()->assertSee('needs a free account')->assertSee('Free account required');
});

it('shows open projects on the landing without any client or applicant details', function () {
    $client = User::factory()->create(['name' => 'Wanjiru Private']);
    ModelJob::factory()->create([
        'user_id' => $client->id, 'title' => 'Atrium render', 'budget' => 55000, 'no_deadline' => false,
        'deadline' => now()->addDays(10), 'applicants_count' => 7, 'description' => 'A bright atrium with timber details',
        'skills' => ['Rendering & Lighting'], 'software' => ['Blender'],
    ]);

    $this->get('/')->assertOk()
        ->assertSee('Atrium render')
        ->assertSee('A bright atrium with timber details')
        ->assertSee('Rendering & Lighting')
        ->assertSee('Blender')
        ->assertSee('55,000')
        ->assertSee('10 days left')
        ->assertSee('Apply')
        ->assertDontSee('Wanjiru Private')
        ->assertDontSee('7 applicants')
        ->assertDontSee('View all projects');
});
