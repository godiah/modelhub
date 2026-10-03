<?php

use App\Models\User;
use App\Support\Navigation\SidebarMenu;

/*
 * The members' guide to payments and earnings: a public page rendered from resources/policies/payments.md in the policy pages' look.
 */

it('is public, in the policy pages\' look, with its sections and contents list', function () {
    $this->get(route('policies.payments'))->assertOk()->assertSee('How payments and earnings work on ModelHub')->assertSee('On this page')->assertSee('<section id="what-it-costs"', false)
        ->assertSee('href="#taking-money-out-withdrawals"', false)->assertSee('Last updated: October 2, 2026')->assertSee('Buying a model')->assertSee('Cancelling a funded project');
});

it('renders the markdown properly: tables, numbered steps, no raw markdown', function () {
    $this->get(route('policies.payments'))->assertOk()->assertSee('<table class="w-full', false)->assertSee('<ol class="space-y-2.5', false)->assertDontSee('## ')->assertDontSee('|---|', false)->assertDontSee('**', false);
});

it('shows reviewers the drafting notes outside production, and never shows them to the public in production', function () {
    $this->get(route('policies.payments'))->assertOk()->assertSee('[CONFIRM')->assertSee('DRAFT for review');

    app()->detectEnvironment(fn () => 'production');
    $page = $this->get(route('policies.payments'))->assertOk();
    $page->assertDontSee('CONFIRM')->assertDontSee('DRAFT for review')->assertSee('What it costs')->assertSee('KES 30');
});

it('is linked from the footers, the user menu, the money pages and the cancellation policy', function () {
    $url = route('policies.payments');

    $this->get(route('home'))->assertOk()->assertSee($url, false);
    $this->get(route('engagements.policy'))->assertOk()->assertSee($url, false)->assertSee('Looking for how money is held and returned?');

    $this->actingAs(User::factory()->create());
    $this->get(route('earnings.index'))->assertOk()->assertSee($url, false)->assertSee('How earnings and withdrawals work');
    $this->get(route('dashboard'))->assertOk()->assertSee($url, false);
    expect(SidebarMenu::breadcrumb())->toBeArray();
});

it('strips raw html from the guide, so it can never run script on the page', function () {
    $path = resource_path('policies/payments.md');
    $original = file_get_contents($path);
    file_put_contents($path, $original."\n\n<script>alert('x')</script>\n\n[bad](javascript:alert(1))\n");

    try {
        $this->get(route('policies.payments'))->assertOk()->assertDontSee("<script>alert('x')</script>", false)->assertDontSee('href="javascript:', false);
    } finally {
        file_put_contents($path, $original);
    }
});
