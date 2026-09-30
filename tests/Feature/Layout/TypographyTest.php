<?php

use App\Models\User;

/*
 * Two families only: Inter for body/UI/numbers, Plus Jakarta Sans for headings, buttons and labels.
 * (Montserrat, Poppins, Roboto and Figtree were loaded before but are no longer used.)
 */

it('loads only Inter and Plus Jakarta Sans on every shell', function (string $url, bool $signedIn) {
    if ($signedIn) {
        $this->actingAs(User::factory()->create());
    }

    $html = $this->get($url)->assertOk()->getContent();

    expect($html)
        ->toContain('family=Inter')
        ->toContain('family=Plus+Jakarta+Sans')
        ->not->toContain('Montserrat')
        ->not->toContain('Poppins')
        ->not->toContain('Roboto')
        ->not->toContain('figtree');
})->with([
    'landing (guest)' => ['/', false],
    'login (auth layout)' => ['/login', false],
    'browse (public shell)' => ['/jobs/browse', false],
    'dashboard (app shell)' => ['/dashboard', true],
]);

it('makes Inter the default sans so text without a font class is not the system font', function () {
    $config = file_get_contents(base_path('tailwind.config.js'));

    expect($config)
        ->toContain('sans: ["Inter"')
        ->toContain('main: ["Inter"')
        ->toContain('tertiary: ["Plus Jakarta Sans"')
        ->toContain('secondary: ["Plus Jakarta Sans"');
});
