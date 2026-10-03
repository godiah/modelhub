<?php

use App\Enums\LicenceTier;
use App\Enums\ProductStatus;
use App\Models\IssuedLicence;
use App\Models\LicenceDownload;
use App\Models\Product;
use App\Models\ProductFile;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\Marketplace\LicenceLibrary;
use App\Services\Marketplace\LicenceService;

/*
 * My licences as a library: summary figures, search, filters and sorting, and on each card what to do next (download, rate, upgrade).
 */

beforeEach(function () {
    $this->buyer = User::factory()->create(['name' => 'Buyer Person']);
    $this->service = app(LicenceService::class);
    $this->library = app(LicenceLibrary::class);
});

function libraryModel(string $title, int $price = 120000, ?int $extended = 480000, string $store = 'Oak Studio'): Product
{
    $seller = User::factory()->create();
    SellerProfile::factory()->approved()->create(['user_id' => $seller->id, 'display_name' => $store.' '.$seller->id]);

    return Product::factory()->published()->create(['user_id' => $seller->id, 'title' => $title, 'price_minor' => $price, 'extended_price_minor' => $extended]);
}

function libraryLicence(User $buyer, string $title, LicenceTier $tier = LicenceTier::Standard, int $price = 120000, ?int $extended = 480000): IssuedLicence
{
    return app(LicenceService::class)->grant($buyer, libraryModel($title, $price, $extended), $tier);
}

it('counts what you hold, what ended, what you paid for the live ones and how often you took files', function () {
    $oak = libraryLicence($this->buyer, 'Oak armchair');                                  // 1,200 standard
    libraryLicence($this->buyer, 'Pine table', LicenceTier::Extended);                    // 4,800 extended
    $ended = libraryLicence($this->buyer, 'Old lamp', price: 50000);
    $this->service->revoke($ended, 'Refunded: broken.');
    $file = ProductFile::create(['product_id' => $oak->product_id, 'disk' => 'local', 'path' => 'x', 'original_name' => 'a.glb', 'extension' => 'glb', 'kind' => 'exchange', 'size_bytes' => 10, 'checksum' => str_repeat('a', 64)]);
    LicenceDownload::create(['issued_licence_id' => $oak->id, 'product_file_id' => $file->id, 'file_name' => 'a.glb']);
    LicenceDownload::create(['issued_licence_id' => $oak->id, 'product_file_id' => $file->id, 'file_name' => 'a.glb']);

    expect($this->library->stats($this->buyer))->toBe(['active' => 2, 'extended' => 1, 'spent' => 600000, 'downloads' => 2])
        ->and($this->library->counts($this->buyer))->toBe(['all' => 3, 'active' => 2, 'ended' => 1, 'standard' => 2, 'extended' => 1]);
});

it('only ever counts and lists the member\'s own licences', function () {
    libraryLicence($this->buyer, 'Mine');
    libraryLicence(User::factory()->create(), 'Somebody else\'s');

    expect($this->library->counts($this->buyer)['all'])->toBe(1)->and($this->library->stats($this->buyer)['active'])->toBe(1);
    $this->actingAs($this->buyer)->get(route('licences.index'))->assertOk()->assertSee('Mine')->assertDontSee('Somebody else');
});

it('filters by kind and state, searches by model, seller or key, and sorts', function () {
    $a = libraryLicence($this->buyer, 'Alder bench', price: 90000);
    $b = libraryLicence($this->buyer, 'Birch stool', LicenceTier::Extended, 300000);
    $c = libraryLicence($this->buyer, 'Cedar shelf', price: 60000);
    $this->service->revoke($c, 'Refunded.');
    $a->forceFill(['issued_at' => now()->subDays(3)])->save();
    $b->forceFill(['issued_at' => now()->subDays(2)])->save();
    $c->forceFill(['issued_at' => now()->subDay()])->save();
    $this->actingAs($this->buyer);
    $titles = fn (array $query) => collect($this->library->page($this->buyer, LicenceLibrary::tab($query['tab'] ?? null), $query['q'] ?? '', LicenceLibrary::sort($query['sort'] ?? null))->items())->pluck('product_title')->all();

    expect($titles([]))->toBe(['Cedar shelf', 'Birch stool', 'Alder bench'])
        ->and($titles(['sort' => 'oldest']))->toBe(['Alder bench', 'Birch stool', 'Cedar shelf'])
        ->and($titles(['sort' => 'title']))->toBe(['Alder bench', 'Birch stool', 'Cedar shelf'])
        ->and($titles(['sort' => 'price']))->toBe(['Birch stool', 'Alder bench', 'Cedar shelf'])
        ->and($titles(['tab' => 'active']))->toBe(['Birch stool', 'Alder bench'])->and($titles(['tab' => 'ended']))->toBe(['Cedar shelf'])
        ->and($titles(['tab' => 'extended']))->toBe(['Birch stool'])->and($titles(['tab' => 'standard']))->toBe(['Cedar shelf', 'Alder bench'])
        ->and($titles(['q' => 'birch']))->toBe(['Birch stool'])->and($titles(['q' => $a->key]))->toBe(['Alder bench'])->and($titles(['q' => $c->seller_name]))->toBe(['Cedar shelf'])
        ->and($titles(['q' => 'nothing like this']))->toBe([]);
});

it('falls back to all and newest first for names it does not know', function () {
    expect(LicenceLibrary::tab('nonsense'))->toBe('all')->and(LicenceLibrary::tab(null))->toBe('all')->and(LicenceLibrary::tab('ended'))->toBe('ended')
        ->and(LicenceLibrary::sort('nonsense'))->toBe('newest')->and(LicenceLibrary::sort('price'))->toBe('price');

    libraryLicence($this->buyer, 'Oak armchair');
    $this->actingAs($this->buyer)->get(route('licences.index', ['tab' => 'bogus', 'sort' => 'bogus', 'q' => '']))->assertOk()->assertSee('Oak armchair');
});

it('shows each licence with its model, tier, price, key and the date, as a card with the actions that fit it', function () {
    $licence = libraryLicence($this->buyer, 'Oak armchair');
    $image = ProductImage::create(['product_id' => $licence->product_id, 'disk' => 'public', 'path' => 'previews/oak.jpg', 'position' => 0]);
    ProductFile::create(['product_id' => $licence->product_id, 'disk' => 'local', 'path' => 'x', 'original_name' => 'oak.glb', 'extension' => 'glb', 'kind' => 'exchange', 'size_bytes' => 2097152, 'checksum' => str_repeat('b', 64)]);
    $this->actingAs($this->buyer);

    $page = $this->get(route('licences.index'))->assertOk();
    $page->assertSee('Oak armchair')->assertSee('Standard')->assertSee($licence->key)->assertSee('Ksh1,200')->assertSee($licence->issued_at->format('M j, Y'))->assertSee($image->url(), false)->assertSee('>glb<', false)
        ->assertSee('1 · 2.0 MB')->assertSee('Not downloaded yet')->assertSee('Download files')->assertSee(route('licences.show', $licence).'#files', false)->assertSee('Copy')
        ->assertSee('Active licences')->assertSee('Spent on models')->assertSee('File downloads')->assertSee('Search by model, seller or licence key')->assertSee('Compare the licences');
});

it('says how often and when the files were taken', function () {
    $licence = libraryLicence($this->buyer, 'Oak armchair');
    $file = ProductFile::create(['product_id' => $licence->product_id, 'disk' => 'local', 'path' => 'x', 'original_name' => 'oak.glb', 'extension' => 'glb', 'kind' => 'exchange', 'size_bytes' => 10, 'checksum' => str_repeat('c', 64)]);
    foreach (range(1, 3) as $i) {
        LicenceDownload::create(['issued_licence_id' => $licence->id, 'product_file_id' => $file->id, 'file_name' => 'oak.glb']);
    }

    $this->actingAs($this->buyer)->get(route('licences.index'))->assertOk()->assertSee('Downloaded 3 times')->assertDontSee('Not downloaded yet');
});

it('offers an upgrade to Extended only for a live Standard licence, and a rating only until the model has been reviewed', function () {
    $standard = libraryLicence($this->buyer, 'Oak armchair');
    $already = libraryLicence($this->buyer, 'Pine table');
    ProductReview::factory()->create(['product_id' => $already->product_id, 'user_id' => $this->buyer->id]);
    $upgraded = libraryLicence($this->buyer, 'Maple desk');
    $this->service->grant($this->buyer, $upgraded->product, LicenceTier::Extended);
    $noExtended = libraryLicence($this->buyer, 'Ash shelf', extended: null);
    $unlisted = libraryLicence($this->buyer, 'Gone model');
    $unlisted->product->update(['status' => ProductStatus::Draft]);
    $ended = libraryLicence($this->buyer, 'Ended model');
    $this->service->revoke($ended, 'Refunded.');
    $this->actingAs($this->buyer);

    $library = collect($this->library->page($this->buyer, 'all', '', 'title')->items())->keyBy('product_title');
    expect($library['Oak armchair']->library->upgrade_minor)->toBe(480000)->and($library['Oak armchair']->library->can_rate)->toBeTrue()
        ->and($library['Pine table']->library->can_rate)->toBeFalse()->and($library['Pine table']->library->upgrade_minor)->toBe(480000)
        ->and($library['Maple desk']->library->upgrade_minor)->toBeNull()
        ->and($library['Ash shelf']->library->upgrade_minor)->toBeNull()
        ->and($library['Gone model']->library->upgrade_minor)->toBeNull()->and($library['Gone model']->library->can_rate)->toBeFalse()->and($library['Gone model']->library->live)->toBeFalse()
        ->and($library['Ended model']->library->upgrade_minor)->toBeNull()->and($library['Ended model']->library->can_rate)->toBeFalse();

    $this->get(route('licences.index'))->assertOk()->assertSee('Upgrade to Extended for Ksh4,800')->assertSee('Rate this model')->assertSee(route('models.show', $standard->product).'#reviews', false)->assertSee('No longer listed');
});

it('marks an ended licence plainly and sends the member to the reason, not the files', function () {
    $licence = libraryLicence($this->buyer, 'Oak armchair');
    $this->service->revoke($licence, 'Refunded: the file was broken.');
    $this->actingAs($this->buyer);

    $this->get(route('licences.index'))->assertOk()->assertSee('Licence ended')->assertSee('See why it ended')->assertDontSee('Download files')->assertSee('Never downloaded')->assertSee('1 has ended');
    $this->get(route('licences.index', ['tab' => 'active']))->assertOk()->assertSee('No licences match')->assertSee('Show all licences');
});

it('welcomes a member with no licences, and says so when a search finds nothing', function () {
    $this->actingAs($this->buyer)->get(route('licences.index'))->assertOk()->assertSee('No licences yet')->assertSee('Browse models')->assertDontSee('Search by model');

    libraryLicence($this->buyer, 'Oak armchair');
    $this->get(route('licences.index', ['q' => 'zzz']))->assertOk()->assertSee('No licences match')->assertSee('Nothing matches');
});

it('pages through a large library and keeps the search and tab in the links', function () {
    foreach (range(1, 14) as $i) {
        libraryLicence($this->buyer, "Chair {$i}", extended: null);
    }
    $this->actingAs($this->buyer);

    $this->get(route('licences.index', ['q' => 'Chair', 'tab' => 'standard']))->assertOk()->assertSee('page=2', false)->assertSee('q=Chair', false);
    $second = $this->get(route('licences.index', ['q' => 'Chair', 'tab' => 'standard', 'page' => 2]))->assertOk();
    expect(substr_count($second->getContent(), 'Download files'))->toBe(2);
});

/** ---------------------------------------------------------------- one licence */
function licenceFile(IssuedLicence $licence, string $name, string $kind, int $bytes = 1048576): ProductFile
{
    return ProductFile::create(['product_id' => $licence->product_id, 'disk' => 'local', 'path' => 'x/'.$name, 'original_name' => $name, 'extension' => pathinfo($name, PATHINFO_EXTENSION), 'kind' => $kind, 'size_bytes' => $bytes, 'checksum' => str_repeat('d', 64)]);
}

it('shows the licence as a summary of the model with its key, status, tier and what to do next', function () {
    $licence = libraryLicence($this->buyer, 'Oak armchair');
    ProductImage::create(['product_id' => $licence->product_id, 'disk' => 'public', 'path' => 'previews/oak.jpg', 'position' => 0]);
    licenceFile($licence, 'oak.glb', 'exchange');
    $this->actingAs($this->buyer);

    $this->get(route('licences.show', $licence))->assertOk()->assertSee('Oak armchair')->assertSee('Standard')->assertSee('Active')->assertSee($licence->key)->assertSee('Copy')->assertSee('Buyer Person')->assertSee('Ksh1,200.00')
        ->assertSee('For one end product, by one licensee.')->assertSee('Download files')->assertSee('View the model')->assertSee('Upgrade to Extended for Ksh4,800')->assertSee('Rate this model')
        ->assertSee('id="certificate"', false)->assertSee('Print or save as PDF')->assertSee('What you may do')->assertSee('What this licence covers')->assertSee('Read the full terms');
});

it('groups the files by kind and says how often each was taken, and lists the latest downloads', function () {
    $licence = libraryLicence($this->buyer, 'Oak armchair');
    $glb = licenceFile($licence, 'oak.glb', 'exchange', 2097152);
    $blend = licenceFile($licence, 'oak.blend', 'native', 5242880);
    $png = licenceFile($licence, 'oak_diffuse.png', 'texture', 1048576);
    foreach ([$glb, $glb, $blend] as $file) {
        LicenceDownload::create(['issued_licence_id' => $licence->id, 'product_file_id' => $file->id, 'file_name' => $file->original_name]);
    }
    $this->actingAs($this->buyer);

    $page = $this->get(route('licences.show', $licence))->assertOk();
    $page->assertSeeInOrder(['Native formats', 'oak.blend', 'Exchange formats', 'oak.glb', 'Textures', 'oak_diffuse.png'])->assertSee('3 files · 8.0 MB')->assertSee('Downloaded 2 times')->assertSee('Downloaded 1 time')->assertSee('Not downloaded yet')
        ->assertSee(route('licences.download', [$licence, $glb]), false)->assertSee('Download history')->assertDontSee('You have not downloaded any files');
});

it('says nothing has been downloaded yet, and what to do about a broken file, with the refund window from the settings', function () {
    $licence = libraryLicence($this->buyer, 'Oak armchair');
    licenceFile($licence, 'oak.glb', 'exchange');
    $this->actingAs($this->buyer);

    $this->get(route('licences.show', $licence))->assertOk()->assertSee('You have not downloaded any files with this licence yet.')->assertSee('Something wrong with a file?')->assertSee('within 7 days')
        ->assertSee(route('policies.payments').'#refunds-for-models', false);
});

it('shows an ended licence without files or download buttons, but with why it ended and what it covered (no upgrade or rating)', function () {
    $licence = libraryLicence($this->buyer, 'Oak armchair');
    $file = licenceFile($licence, 'oak.glb', 'exchange');
    $this->service->revoke($licence, 'Refunded: the file was broken.');
    $this->actingAs($this->buyer);

    $this->get(route('licences.show', $licence))->assertOk()->assertSee('This licence ended on')->assertSee('Refunded: the file was broken.')->assertDontSee('Your files')->assertDontSee('Download files')
        ->assertDontSee(route('licences.download', [$licence, $file]), false)->assertSee('What this licence covers')->assertDontSee('Upgrade to Extended')->assertDontSee('Rate this model')->assertSee('id="certificate"', false);
});

it('keeps the certificate as issued, even when the model is no longer listed or has been removed', function () {
    $licence = libraryLicence($this->buyer, 'Oak armchair');
    $licence->update(['terms' => ['permitted' => ['Frozen permission text.'], 'restrictions' => ['Frozen restriction text.'], 'general' => ['Frozen general text.']]]);
    $licence->product->update(['status' => ProductStatus::Draft]);
    $this->actingAs($this->buyer);

    $this->get(route('licences.show', $licence))->assertOk()->assertSee('No longer listed')->assertDontSee('View the model')->assertSee('Frozen permission text.')->assertSee('Frozen restriction text.')->assertSee('Frozen general text.');

    $licence->product->delete();
    $this->get(route('licences.show', $licence))->assertOk()->assertSee('Oak armchair')->assertSee('No longer listed');
});

it('does not show a licence with no cover or files badly', function () {
    $licence = libraryLicence($this->buyer, 'Oak armchair');
    $this->actingAs($this->buyer);

    $this->get(route('licences.show', $licence))->assertOk()->assertSee('The files for this model are no longer available.');
});
