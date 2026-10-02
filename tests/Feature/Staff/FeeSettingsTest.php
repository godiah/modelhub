<?php

use App\Helpers\Applications\ApplicationCalculationHelper;
use App\Models\SellerProfile;
use App\Models\StaffActivity;
use App\Support\Settings\FeePolicy;
use App\Support\Settings\PlatformSettings;

/*
 * The money settings: commission on jobs and model sales (with a per-seller override), the minimum model price and the sale hold.
 */

function feesForm(array $overrides = []): array
{
    return ['settings' => $overrides + ['jobs_percent' => '10', 'models_percent' => '15', 'min_model_price' => '100', 'sale_hold_days' => '7', 'min_payout' => '500', 'payout_fee' => '30']];
}

it('starts at the decided launch values', function () {
    expect(FeePolicy::jobsRate())->toBe(0.10)->and(FeePolicy::modelsRate())->toBe(0.15)->and(FeePolicy::minModelPriceMinor())->toBe(10000)->and(FeePolicy::saleHoldDays())->toBe(7);
});

it('gives a seller their own rate when they have one, and the platform rate otherwise', function () {
    $special = SellerProfile::factory()->approved()->create(['commission_percent' => 8]);
    $normal = SellerProfile::factory()->approved()->create();

    expect(FeePolicy::modelsRate($special))->toBe(0.08)->and(FeePolicy::modelsRate($normal))->toBe(0.15)->and(FeePolicy::modelsRate())->toBe(0.15);

    setting('fees.models_percent', 20);
    expect(FeePolicy::modelsRate($normal->fresh()))->toBe(0.20)->and(FeePolicy::modelsRate($special->fresh()))->toBe(0.08);
});

it('prices a job offer with the rate in force, and a later change does not touch what was already calculated', function () {
    $before = ApplicationCalculationHelper::calculateAmounts(1000.0);
    expect($before)->toMatchArray(['service_fee' => 100.0, 'net_amount' => 900.0]);

    setting('fees.jobs_percent', 12.5);

    expect(ApplicationCalculationHelper::calculateAmounts(1000.0))->toMatchArray(['service_fee' => 125.0, 'net_amount' => 875.0])
        ->and(ApplicationCalculationHelper::getServiceFeePercentage())->toBe(0.125)
        ->and($before['service_fee'])->toBe(100.0);
});

it('shows the fees page to Super admins only, beside Security', function () {
    foreach (['Platform manager', 'Auditor', 'Support'] as $role) {
        $this->actingAs(staffWith($role), 'staff')->get(route('admin.settings.fees'))->assertForbidden();
        $this->actingAs(staffWith($role), 'staff')->patch(route('admin.settings.fees.update'), feesForm())->assertForbidden();
    }

    $this->actingAs(staffWith('Super admin'), 'staff')->get(route('admin.settings.fees'))->assertOk()->assertSee('Fees and payments')->assertSee('Commission on jobs')->assertSee('Commission on model sales')
        ->assertSee('Lowest price for a paid model')->assertSee('Hold sale earnings for')->assertSee(route('admin.settings.security'), false);
    $this->get(route('admin.settings.security'))->assertOk()->assertSee(route('admin.settings.fees'), false);
});

it('saves the fee settings, reads them back and logs the change with its old and new value', function () {
    $admin = staffWith('Super admin');

    $this->actingAs($admin, 'staff')->patch(route('admin.settings.fees.update'), feesForm(['models_percent' => '12.5', 'sale_hold_days' => '14']))->assertRedirect();

    expect(PlatformSettings::float('fees.models_percent'))->toBe(12.5)->and(PlatformSettings::int('fees.sale_hold_days'))->toBe(14)->and(PlatformSettings::float('fees.jobs_percent'))->toBe(10.0);

    $entry = StaffActivity::where('action', 'settings.fees-updated')->sole();
    expect($entry->staff_id)->toBe($admin->id)->and($entry->summary)->toContain('Changed 2 fees settings')->toContain('Commission on model sales (15 to 12.5)')->toContain('Hold sale earnings for (7 to 14)');
});

it('keeps each fee setting within its limits', function () {
    $this->actingAs(staffWith('Super admin'), 'staff');

    foreach (['jobs_percent' => '-1', 'models_percent' => '51', 'min_model_price' => '-5', 'sale_hold_days' => '61', 'models_percent ' => 'abc'] as $field => $bad) {
        $this->patch(route('admin.settings.fees.update'), feesForm([trim($field) => $bad]))->assertSessionHasErrors('settings.'.trim($field));
    }
    $this->patch(route('admin.settings.fees.update'), feesForm(['jobs_percent' => '10.123']))->assertSessionHasErrors('settings.jobs_percent');

    expect(PlatformSettings::float('fees.jobs_percent'))->toBe(10.0);
});

it('lets a Super admin give one seller a special commission, and clear it again, logging each change', function () {
    $store = SellerProfile::factory()->approved()->create(['display_name' => 'Deal Studio']);
    $admin = staffWith('Super admin');
    $this->actingAs($admin, 'staff');

    $this->get(route('admin.stores.show', $store))->assertSee('Using the platform rate.')->assertSee('Save rate');

    $this->patch(route('admin.stores.commission', $store), ['commission_percent' => '7.5'])->assertSessionHasNoErrors();
    expect($store->fresh()->commission_percent)->toBe('7.50')->and(FeePolicy::modelsRate($store->fresh()))->toBe(0.075);
    $this->get(route('admin.stores.show', $store))->assertSee('A special rate is set for this seller.');
    $entry = StaffActivity::where('action', 'seller.commission-changed')->sole();
    expect($entry->summary)->toBe('Set the commission of Deal Studio to 7.5%')->and($entry->details)->toMatchArray(['from' => null, 'to' => 7.5]);

    $this->patch(route('admin.stores.commission', $store), ['commission_percent' => ''])->assertSessionHasNoErrors();
    expect($store->fresh()->commission_percent)->toBeNull()->and(StaffActivity::where('action', 'seller.commission-changed')->count())->toBe(2);

    // Saving the same value again records nothing
    $this->patch(route('admin.stores.commission', $store), ['commission_percent' => '']);
    expect(StaffActivity::where('action', 'seller.commission-changed')->count())->toBe(2);

    $this->patch(route('admin.stores.commission', $store), ['commission_percent' => '60'])->assertSessionHasErrors('commission_percent');
});

it('keeps the special commission to Super admins', function () {
    $store = SellerProfile::factory()->approved()->create();

    foreach (['Platform manager', 'Marketplace moderator', 'Auditor'] as $role) {
        $this->actingAs(staffWith($role), 'staff')->patch(route('admin.stores.commission', $store), ['commission_percent' => '1'])->assertForbidden();
    }
    $this->actingAs(staffWith('Platform manager'), 'staff')->get(route('admin.stores.show', $store))->assertDontSee('Save rate');

    expect($store->fresh()->commission_percent)->toBeNull();
});

it('lists the fees page in the palette for Super admins only', function () {
    $this->actingAs(staffWith('Super admin'), 'staff')->get(route('admin.dashboard'))->assertSee('Fees and payments');
    $this->actingAs(staffWith('Support'), 'staff')->get(route('admin.dashboard'))->assertDontSee('Fees and payments');
});
