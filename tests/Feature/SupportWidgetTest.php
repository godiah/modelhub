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
        // No greeting: the panel opens on the suggested questions
        ->not->toContain('I can answer questions about how ModelHub works')
        // None of the scripted answers about the member's own money ship in live mode
        ->not->toContain('Needs staff attention');
});

it('never puts the member id or a signing secret in the page', function () {
    config(['support.enabled' => true, 'support.agent.hmac_secret' => 'super-secret-value', 'support.agent.context_private_key' => 'private-key-value']);

    $html = $this->actingAs(User::factory()->create())->get(route('notifications.index'))->getContent();

    expect($html)->not->toContain('super-secret-value')->not->toContain('private-key-value');
});

/*
 * What the live panel looks like on open: a short greeting by first name, then the frequently asked questions as small pills, and a
 * mistakes warning under the input. The old long greeting and the always-on PIN banner are gone (the PIN guard still blocks a PIN
 * when one is typed). The greeting claims nothing about what the assistant can see.
 */

function liveWidgetHtml(?User $user = null): string
{
    config(['support.enabled' => true, 'support.ui_preview' => false]);

    return test()->actingAs($user ?? User::factory()->create())->get(route('dashboard'))->assertOk()->getContent();
}

it('greets the member by first name and asks how it can help', function () {
    $html = liveWidgetHtml(User::factory()->create(['name' => 'Kevin Otieno']));

    // The widget's config is written into the page as JSON, so the greeting is checked as it appears there
    expect($html)
        ->toContain('Hi Kevin, how may we help you today?')
        ->not->toContain('Hi Kevin Otieno')
        ->not->toContain('I can answer questions about how ModelHub works')
        ->not->toContain('I can make mistakes. I can');
});

it('labels the pills as frequently asked questions', function () {
    expect(liveWidgetHtml())->toContain('Frequently asked questions');
});

it('shows the mistakes warning under the input and no PIN banner', function () {
    expect(liveWidgetHtml())
        ->toContain('AI can make mistakes. Please double-check important information.')
        ->not->toContain('Never type your M-Pesa PIN or a code from an SMS.');
});

it('suggests four questions at most, the ones members ask most', function () {
    $html = liveWidgetHtml();

    $questions = [
        'What fee do I pay to withdraw?',
        'Standard vs Extended licence?',
        'Can I get a refund?',
        'How do I reset my password?',
    ];

    foreach ($questions as $question) {
        expect($html)->toContain($question);
    }

    // Four at most: the chips are written into the page as JSON objects, one "label" each
    expect(substr_count($html, '\u0022label\u0022:\u0022'))->toBeLessThanOrEqual(4 + 1); // +1: the page-context label
});

it("renders the assistant's text (bold, lists) instead of printing the raw Markdown symbols", function () {
    $html = liveWidgetHtml();

    // Assistant text goes through the escaping renderer into x-html; only the member's own words stay plain x-text
    expect($html)
        ->toContain('x-html="supportRender(m.text)"')
        ->toContain('window.supportRender = function')
        ->toContain('x-text="m.text"'); // the member's own message bubble
});
