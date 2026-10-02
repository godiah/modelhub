<?php

use App\Enums\LicenceTier;
use App\Models\IssuedLicence;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\Marketplace\LicenceService;

/*
 * Licences: what a model costs under each tier, issuing the licence a buyer holds, and the pages that show it.
 */

function licensedModel(array $overrides = []): Product
{
    $seller = User::factory()->create(['name' => 'Seller Person']);
    // Store names are unique, so the first model's store is "Oak Studio" and any later one gets a number
    $name = 'Oak Studio';
    for ($n = 2; SellerProfile::where('display_name', $name)->exists(); $n++) {
        $name = "Oak Studio {$n}";
    }
    SellerProfile::factory()->approved()->create(['user_id' => $seller->id, 'display_name' => $name]);

    return Product::factory()->published()->create($overrides + ['user_id' => $seller->id, 'title' => 'Oak armchair', 'price_minor' => 120000, 'extended_price_minor' => 480000]);
}

/** ---------------------------------------------------------------- pricing */
it('knows which licences a model is sold under and what each costs', function () {
    $both = licensedModel();
    $standardOnly = licensedModel(['extended_price_minor' => null]);
    $free = licensedModel(['price_minor' => 0, 'extended_price_minor' => null]);

    expect($both->offeredLicences())->toBe([LicenceTier::Standard, LicenceTier::Extended])->and($both->priceFor(LicenceTier::Extended))->toBe(480000)
        ->and($standardOnly->offeredLicences())->toBe([LicenceTier::Standard])->and($standardOnly->priceFor(LicenceTier::Extended))->toBeNull()
        ->and($free->offeredLicences())->toBe([LicenceTier::Standard])->and($free->priceFor(LicenceTier::Standard))->toBe(0);
});

/** ---------------------------------------------------------------- issuing */
it('records the purchase and issues a licence with everything copied from the moment of sale', function () {
    $buyer = User::factory()->create(['name' => 'Buyer Person']);
    $product = licensedModel();

    $licence = app(LicenceService::class)->grant($buyer, $product, LicenceTier::Extended);

    expect($licence)->toBeInstanceOf(IssuedLicence::class)
        ->and($licence->tier)->toBe(LicenceTier::Extended)->and($licence->price_minor)->toBe(480000)->and($licence->currency)->toBe('KES')
        ->and($licence->licensee_name)->toBe('Buyer Person')->and($licence->product_title)->toBe('Oak armchair')->and($licence->seller_name)->toBe('Oak Studio')
        ->and($licence->terms_version)->toBe(LicenceTier::TERMS_VERSION)->and($licence->isActive())->toBeTrue()
        ->and($licence->key)->toMatch('/^LIC-[A-HJKMN-Z2-9]{4}-[A-HJKMN-Z2-9]{4}-[A-HJKMN-Z2-9]{4}$/');

    $purchase = Purchase::sole();
    expect($purchase->tier)->toBe(LicenceTier::Extended)->and($purchase->price_minor)->toBe(480000)->and($purchase->status)->toBe('completed')->and($purchase->licence->is($licence))->toBeTrue();
});

it('keeps the licence as it was issued when the listing, price, names or wording change afterwards', function () {
    $buyer = User::factory()->create(['name' => 'Buyer Person']);
    $product = licensedModel();
    $licence = app(LicenceService::class)->grant($buyer, $product, LicenceTier::Standard);

    $product->update(['title' => 'Renamed chair', 'price_minor' => 999900]);
    $product->sellerProfile->update(['display_name' => 'New Studio Name']);
    $buyer->update(['name' => 'Someone Else']);
    $product->delete();

    $licence->refresh();
    expect($licence->product_title)->toBe('Oak armchair')->and($licence->seller_name)->toBe('Oak Studio')->and($licence->licensee_name)->toBe('Buyer Person')
        ->and($licence->price_minor)->toBe(120000)->and($licence->terms['permitted'])->toBe(LicenceTier::Standard->permitted())->and($licence->product)->not->toBeNull();
});

it('issues a free licence for a free model', function () {
    $licence = app(LicenceService::class)->grant(User::factory()->create(), licensedModel(['price_minor' => 0, 'extended_price_minor' => null]), LicenceTier::Standard);

    expect($licence->price_minor)->toBe(0)->and(Purchase::sole()->price_minor)->toBe(0);
});

it('gives every licence its own key', function () {
    $product = licensedModel();
    $keys = collect(range(1, 6))->map(fn () => app(LicenceService::class)->grant(User::factory()->create(), $product, LicenceTier::Standard)->key);

    expect($keys->unique())->toHaveCount(6);
});

it('refuses what cannot be bought, and says why, without recording anything', function () {
    $service = app(LicenceService::class);
    $buyer = User::factory()->create();
    $product = licensedModel();
    $standardOnly = licensedModel(['extended_price_minor' => null]);
    $draft = Product::factory()->create();

    expect($service->grant($buyer, $draft, LicenceTier::Standard))->toBe('This model is not for sale.')
        ->and($service->grant($product->seller, $product, LicenceTier::Standard))->toBe('You cannot buy your own model.')
        ->and($service->grant($buyer, $standardOnly, LicenceTier::Extended))->toBe('This model is not sold with an Extended licence.');

    $suspendedSeller = licensedModel();
    $suspendedSeller->seller->forceFill(['suspended_at' => now()])->save();
    expect($service->grant($buyer, $suspendedSeller, LicenceTier::Standard))->toBe('This model is not for sale.');

    expect(Purchase::count())->toBe(0)->and(IssuedLicence::count())->toBe(0);
});

it('does not sell the same licence twice, and lets Extended follow Standard but not the other way round', function () {
    $service = app(LicenceService::class);
    $buyer = User::factory()->create();
    $product = licensedModel();

    expect($service->grant($buyer, $product, LicenceTier::Standard))->toBeInstanceOf(IssuedLicence::class)
        ->and($service->grant($buyer, $product, LicenceTier::Standard))->toBe('You already hold the Standard licence for this model.')
        ->and($service->grant($buyer, $product, LicenceTier::Extended))->toBeInstanceOf(IssuedLicence::class)
        ->and($service->grant($buyer, $product, LicenceTier::Extended))->toBe('You already hold the Extended licence for this model.');

    $other = User::factory()->create();
    $service->grant($other, $product, LicenceTier::Extended);
    expect($service->grant($other, $product, LicenceTier::Standard))->toBe('Your Extended licence for this model already covers everything the Standard one does.');
});

it('ends a licence and its purchase on a refund, keeping the record, and lets the buyer buy again', function () {
    $service = app(LicenceService::class);
    $buyer = User::factory()->create();
    $product = licensedModel();
    $licence = $service->grant($buyer, $product, LicenceTier::Standard);

    $service->revoke($licence, '  The file would not open.  ');

    $licence->refresh();
    expect($licence->isActive())->toBeFalse()->and($licence->revoked_reason)->toBe('The file would not open.')->and($licence->purchase->status)->toBe('refunded')
        ->and(IssuedLicence::active()->count())->toBe(0)->and(IssuedLicence::count())->toBe(1);

    $revokedAt = $licence->revoked_at;
    $service->revoke($licence, 'Again');
    expect($licence->fresh()->revoked_reason)->toBe('The file would not open.')->and($licence->fresh()->revoked_at->equalTo($revokedAt))->toBeTrue();

    expect($service->grant($buyer, $product, LicenceTier::Standard))->toBeInstanceOf(IssuedLicence::class);
});

/** ---------------------------------------------------------------- the pages */
it('shows a buyer their licences, newest first, and each certificate with the wording it was issued under', function () {
    $buyer = User::factory()->create(['name' => 'Buyer Person']);
    $service = app(LicenceService::class);
    $first = $service->grant($buyer, licensedModel(['title' => 'First chair']), LicenceTier::Standard);
    $second = $service->grant($buyer, licensedModel(['title' => 'Second chair']), LicenceTier::Extended);
    $second->forceFill(['issued_at' => now()->addMinute()])->save();
    $this->actingAs($buyer);

    $this->get(route('licences.index'))->assertOk()->assertSeeInOrder(['Second chair', 'First chair'])->assertSee($first->key)->assertSee('Extended');

    // The certificate shows the stored wording, not whatever the wording says today
    $first->update(['terms' => ['permitted' => ['Frozen permission text.'], 'restrictions' => ['Frozen restriction text.'], 'general' => ['Frozen general text.']]]);
    $this->get(route('licences.show', $first))->assertOk()->assertSee('Standard licence')->assertSee($first->key)->assertSee('Buyer Person')->assertSee('First chair')
        ->assertSee('Frozen permission text.')->assertSee('Frozen restriction text.')->assertSee('Frozen general text.')->assertSee('Print or save as PDF')->assertDontSee('This licence ended');
});

it('marks an ended licence on its certificate and in the list', function () {
    $buyer = User::factory()->create();
    $licence = app(LicenceService::class)->grant($buyer, licensedModel(), LicenceTier::Standard);
    app(LicenceService::class)->revoke($licence, 'Refunded: the file was broken.');
    $this->actingAs($buyer);

    $this->get(route('licences.show', $licence))->assertSee('This licence ended on')->assertSee('Refunded: the file was broken.');
    $this->get(route('licences.index'))->assertSee('Ended');
});

it('keeps a licence private to its holder, and behind the sign-in', function () {
    $licence = app(LicenceService::class)->grant(User::factory()->create(), licensedModel(), LicenceTier::Standard);

    $this->get(route('licences.show', $licence))->assertRedirect(route('login'));
    $this->get(route('licences.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())->get(route('licences.show', $licence))->assertNotFound();
    $this->get(route('licences.show', 'LIC-NOPE-NOPE-NOPE'))->assertNotFound();
    $this->get(route('licences.index'))->assertOk()->assertSee('No licences yet')->assertDontSee($licence->key);
});

it('puts My licences in the sidebar for every member', function () {
    $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()->assertSee('My licences');
});

it('publishes the licence wording to everyone, from the same source the licences use', function () {
    $page = $this->get(route('legal.licences'))->assertOk()->assertSee('Model licences')->assertSee('The Standard licence')->assertSee('The Extended licence')->assertSee('What no licence allows')
        ->assertSee('version '.LicenceTier::TERMS_VERSION);

    foreach ([...LicenceTier::Standard->permitted(), ...LicenceTier::Extended->permitted(), ...LicenceTier::restrictions()] as $line) {
        $page->assertSee(e($line), false);
    }
    $page->assertSee('within 7 days');
});

it('shows both licences and their prices on the product page, and only Standard when that is all there is', function () {
    $both = licensedModel(['title' => 'Two licence chair']);
    $one = licensedModel(['title' => 'One licence chair', 'extended_price_minor' => null]);

    $this->get(route('models.show', $both))->assertOk()->assertSee('Extended licence')->assertSee('Compare the licences')->assertSee(LicenceTier::Extended->summary())->assertSee('4,800');
    $this->get(route('models.show', $one))->assertOk()->assertDontSee('Extended licence')->assertSee('Compare the licences');
});

it('lets staff see the Extended price on the model page', function () {
    $product = licensedModel();

    $this->actingAs(staffWith('Auditor'), 'staff')->get(route('admin.catalogue.show', $product))->assertOk()->assertSee('Extended');
});
