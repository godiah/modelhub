<?php

use App\Models\User;

/*
 * Where the support chat panel appears and which mode it is in. Live (the real assistant) only when SUPPORT_ENABLED; the scripted design preview only
 * when SUPPORT_UI_PREVIEW; and nothing at all when both are off, which is how production runs until the assistant is switched on.
 */

it('is not on the page at all when both switches are off', function () {
    config(['support.enabled' => false, 'support.ui_preview' => false]);

    $this->actingAs(User::factory()->create())->get(route('notifications.index'))
        ->assertOk()
        ->assertDontSee('supportChat(', false);
});

it('plays the scripted preview when only the preview flag is on', function () {
    config(['support.enabled' => false, 'support.ui_preview' => true]);

    $html = $this->actingAs(User::factory()->create())->get(route('notifications.index'))->assertOk()->getContent();

    // The widget's config is written into the page as JSON with escaped quotes (\u0022)
    expect($html)->toContain('supportChat(')
        ->toContain('\u0022live\u0022:false')
        ->toContain('\u0022endpoint\u0022:null');
});

it('talks to the real assistant when it is enabled, and only says what it can do', function () {
    config(['support.enabled' => true, 'support.ui_preview' => false]);

    $html = $this->actingAs(User::factory()->create())->get(route('notifications.index'))->assertOk()->getContent();

    expect($html)->toContain('\u0022live\u0022:true')
        ->toContain('\u0022endpoint\u0022:\u0022http')
        ->toContain('see your own payments or account yet')
        // None of the scripted answers about the member's own money ship in live mode
        ->not->toContain('Needs staff attention');
});

it('never puts the member id or a signing secret in the page', function () {
    config(['support.enabled' => true, 'support.agent.hmac_secret' => 'super-secret-value', 'support.agent.context_private_key' => 'private-key-value']);

    $html = $this->actingAs(User::factory()->create())->get(route('notifications.index'))->getContent();

    expect($html)->not->toContain('super-secret-value')->not->toContain('private-key-value');
});
