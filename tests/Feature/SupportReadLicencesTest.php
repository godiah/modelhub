<?php

use App\Enums\LicenceTier;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\SupportReadAudit;
use App\Models\User;
use App\Services\Marketplace\LicenceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

/*
 * The licences a member holds, as the assistant reads them: only theirs, in force first, the key and names left out, and nothing changed.
 */

require_once __DIR__.'/Support/ReadApiHelpers.php';

beforeEach(function () {
    setUpReadApi($this);
    $this->seller = User::factory()->create(['name' => 'Seller Person']);
    SellerProfile::factory()->approved()->create(['user_id' => $this->seller->id]);
});

function grantTo(User $buyer, string $title = 'Robot', LicenceTier $tier = LicenceTier::Standard)
{
    $product = Product::factory()->published()->create(['user_id' => test()->seller->id, 'title' => $title, 'price_minor' => 120000]);

    return app(LicenceService::class)->grant($buyer, $product, $tier);
}

function readLicences(object $test, User $member): TestResponse
{
    return call($test, readCall($path = '/api/support/v1/licences', ['claim' => claimFor($member)]), $path);
}

it('lists licences in force first, newest first, at most five, with the counts', function () {
    $ended = grantTo($this->member, 'Old one');
    $ended->update(['revoked_at' => now(), 'revoked_reason' => 'Refunded']);
    $titles = collect(range(1, 5))->map(fn ($i) => 'Model '.$i);
    foreach ($titles as $title) {
        grantTo($this->member, $title);
    }

    $response = readLicences($this, $this->member)->assertOk();

    expect($response->json('total'))->toBe(6)->and($response->json('active'))->toBe(5)
        ->and(count($response->json('data')))->toBe(5)
        ->and(collect($response->json('data'))->pluck('status')->unique()->all())->toBe(['active'])
        ->and($response->json('data.0.title'))->toBe('Model 5');
});

it('shows an ended licence with its recorded reason, after those in force', function () {
    grantTo($this->member, 'Fine one');
    grantTo($this->member, 'Gone one')->update(['revoked_at' => now(), 'revoked_reason' => 'Refunded by staff']);

    $data = readLicences($this, $this->member)->json('data');

    expect($data[0]['status'])->toBe('active')->and($data[1]['status'])->toBe('ended')->and($data[1]['reason'])->toBe('Refunded by staff')->and($data[1]['ended_at'])->not->toBeNull();
});

it('shows only an explicit list of fields, with the title shortened and no key or names', function () {
    $licence = grantTo($this->member, str_repeat('Long title ', 9));

    $item = readLicences($this, $this->member)->json('data.0');

    expect(array_keys($item))->toBe(['tier', 'tier_label', 'title', 'status', 'issued_at', 'ended_at', 'reason', 'price_display'])
        ->and(mb_strlen($item['title']))->toBeLessThanOrEqual(80)->and($item['tier'])->toBe('standard')->and($item['price_display'])->toBe('Ksh1,200');
    $json = json_encode($item);
    expect($json)->not->toContain($licence->key)->not->toContain('Seller Person')->not->toContain($this->member->name);
});

it('lists only the member\'s own licences', function () {
    grantTo(User::factory()->create(), 'Someone else\'s');
    grantTo($this->member, 'Mine');

    $response = readLicences($this, $this->member);

    expect($response->json('total'))->toBe(1)->and($response->json('data.0.title'))->toBe('Mine');
});

it('is empty, not an error, for a member with no licences, and changes nothing', function () {
    $before = DB::table('issued_licences')->count();

    readLicences($this, $this->member)->assertOk()->assertJsonPath('total', 0)->assertJsonPath('data', [])->assertJsonPath('active', 0);

    expect(DB::table('issued_licences')->count())->toBe($before)->and(SupportReadAudit::sole()->endpoint)->toBe('support.api.licences.index');
});

it('can be switched off on its own', function () {
    config(['support.reads.capabilities.licences' => false]);

    readLicences($this, $this->member)->assertNotFound()->assertExactJson(['error' => 'not_found']);
});

function searchLicences(object $test, User $member, string $query): TestResponse
{
    return call($test, readCall($path = '/api/support/v1/licences?'.$query, ['claim' => claimFor($member)]), $path);
}

it('narrows to the licences whose title has every word asked for, in either case, and says what it matched', function () {
    grantTo($this->member, 'Robot Walker Rigged');
    grantTo($this->member, 'Castle Door');
    grantTo($this->member, 'Robot Arm');

    $one = searchLicences($this, $this->member, 'q=ROBOT')->assertOk();
    $two = searchLicences($this, $this->member, 'q=robot+walker')->assertOk();

    expect($one->json('total'))->toBe(2)->and($one->json('matching'))->toBe('ROBOT')
        ->and($two->json('total'))->toBe(1)->and($two->json('data.0.title'))->toBe('Robot Walker Rigged')
        ->and($two->json('matching'))->toBe('robot walker');
});

it('finds nothing of anyone else\'s by title, and says so as plainly as for a title that does not exist', function () {
    grantTo(User::factory()->create(), 'Secret Spaceship');
    grantTo($this->member, 'Mine');

    $theirs = searchLicences($this, $this->member, 'q=spaceship');
    $made_up = searchLicences($this, $this->member, 'q=nonexistentthing');

    expect($theirs->json('total'))->toBe(0)->and($theirs->json('data'))->toBe([])
        ->and($theirs->json('data'))->toBe($made_up->json('data'))->and($theirs->json('total'))->toBe($made_up->json('total'));
});

it('takes only letters and digits from the search: wildcards and odd values never widen it past the member\'s own licences', function (string $query) {
    grantTo($this->member, 'Plain model');
    grantTo(User::factory()->create(), 'Plain model too');

    $response = searchLicences($this, $this->member, $query)->assertOk();

    // odd input never widens: at most the member's own one licence, never the other member's
    expect($response->json('total'))->toBeLessThanOrEqual(1)
        ->and(collect($response->json('data'))->pluck('title')->diff(['Plain model'])->all())->toBe([]);
})->with(['q=%25', 'q=_', 'q=%27%20OR%201%3D1%20--', 'q%5B%5D=a', 'q=', 'q=a']);

it('uses at most three words and ignores one-letter words', function () {
    grantTo($this->member, 'Alpha Beta Gamma Delta');

    $response = searchLicences($this, $this->member, 'q=alpha+beta+gamma+nomatch+x')->assertOk();

    expect($response->json('matching'))->toBe('alpha beta gamma')->and($response->json('total'))->toBe(1);
});
