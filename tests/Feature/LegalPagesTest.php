<?php

use App\Models\ModelJob;
use App\Models\User;

/*
 * The public legal documents: Terms of Service, Privacy Policy and the Cancellation & Payment Policy.
 * They must be readable by guests, carry the configured business details, and be linked from where
 * people agree to them.
 */

it('shows the terms of service to guests', function () {
    $this->get(route('legal.terms'))
        ->assertOk()
        ->assertSee('Terms of Service')
        ->assertSee('Agreeing to these terms')
        ->assertSee('Fees and payments')
        ->assertSee('Governing law')
        ->assertSee(route('legal.privacy'), false)
        ->assertSee(route('engagements.policy'), false);
});

it('shows the privacy policy to guests', function () {
    $this->get(route('legal.privacy'))
        ->assertOk()
        ->assertSee('Privacy Policy')
        ->assertSee('What we collect')
        ->assertSee('Your rights')
        ->assertSee('We do not use advertising or analytics cookies.');
});

it('shows the cancellation and payment policy to guests as well as members', function () {
    $this->get(route('engagements.policy'))->assertOk()->assertSee('Cancellation & Payment Policy')->assertSee('Dispute resolution');

    $this->actingAs(User::factory()->create())->get(route('engagements.policy'))->assertOk()->assertSee('Cancellation & Payment Policy');
});

it('renders the documents inside the signed-in shell for members', function () {
    $this->actingAs(User::factory()->create());

    foreach ([route('legal.terms') => 'Terms of service', route('legal.privacy') => 'Privacy policy', route('engagements.policy') => 'Cancellation policy'] as $url => $crumb) {
        $this->get($url)->assertOk()->assertSee('Help')->assertSee($crumb);
    }
});

it('uses the business details from config', function () {
    config([
        'legal.entity' => 'Acme 3D Studios Ltd',
        'legal.email' => 'legal@acme3d.test',
        'legal.jurisdiction' => 'Narnia',
        'legal.address' => '1 Lamp Post Lane',
        'legal.effective' => '2027-02-03',
    ]);

    $this->get(route('legal.terms'))->assertOk()
        ->assertSee('Acme 3D Studios Ltd')
        ->assertSee('legal@acme3d.test')
        ->assertSee('laws of Narnia')
        ->assertSee('1 Lamp Post Lane')
        ->assertSee('February 3, 2027');

    $this->get(route('legal.privacy'))->assertOk()->assertSee('Acme 3D Studios Ltd')->assertSee('legal@acme3d.test');
});

it('states the real service fee', function () {
    $this->get(route('legal.terms'))->assertOk()->assertSee('service fee of 10%');
});

it('does not leave template placeholders behind', function () {
    foreach ([route('legal.terms'), route('legal.privacy')] as $url) {
        $this->get($url)->assertOk()->assertDontSee(':entity')->assertDontSee(':app')->assertDontSee(':jurisdiction')->assertDontSee('[Company');
    }
});

it('links the documents from the footer, the apply form, registration and the member footer', function () {
    $this->get('/')->assertOk()->assertSee(route('legal.terms'), false)->assertSee(route('legal.privacy'), false)->assertSee(route('engagements.policy'), false);
    $this->get(route('register'))->assertOk()->assertSee(route('legal.terms'), false)->assertSee(route('legal.privacy'), false);

    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()
        ->assertSee(route('legal.terms'), false)->assertSee(route('legal.privacy'), false);
});

it('links the documents next to the application consent', function () {
    $client = User::factory()->create();
    $job = ModelJob::factory()->create(['user_id' => $client->id]);
    $this->actingAs(User::factory()->create())
        ->get(route('jobs.apply', $job->slug))
        ->assertOk()
        ->assertSee(route('legal.terms'), false)
        ->assertSee(route('legal.privacy'), false);
});
