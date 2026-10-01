<?php

use App\Enums\DisputeStatus;
use App\Enums\EngagementStatus;
use App\Models\JobApplication;
use App\Models\JobCancellation;
use App\Models\JobEngagement;
use App\Models\JobPaymentDispute;
use App\Models\ProductReview;
use App\Models\ReviewReport;
use App\Models\Staff;
use App\Models\User;
use App\Support\Auth\Totp;
use App\Support\Settings\PlatformSettings;
use App\Support\Staff\StaffAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * A staff member holding the given roles (names from StaffAccess, e.g. 'Super admin', 'Support', 'Dispute manager').
 * Staff are not members: act as one with `$this->actingAs(staffWith('Support'), 'staff')`, or just `actingAsStaff('Support')`.
 */
function staffWith(string ...$roles): Staff
{
    StaffAccess::sync();

    $staff = Staff::factory()->create();
    $staff->assignRole($roles);

    return $staff;
}

function actingAsStaff(string ...$roles): Staff
{
    $staff = staffWith(...$roles);
    test()->actingAs($staff, 'staff');

    return $staff;
}

/** Change a platform setting the way a Super admin would (logged, cache cleared): setting('security.otp_members_required', true). */
function setting(string $key, mixed $value): void
{
    PlatformSettings::update([$key => $value], staffWith('Super admin'));
}

/** Give a member or staff member a confirmed authenticator app; returns its secret. */
function authenticatorFor(User|Staff $account): string
{
    $account->startAuthenticatorSetup();
    $account->confirmAuthenticator(Totp::at($account->two_factor_secret, Totp::step()));
    $account->forceFill(['two_factor_last_step' => null])->save();

    return $account->two_factor_secret;
}

function openReport(?ProductReview $review = null, ?Carbon $at = null): ProductReview
{
    $review ??= ProductReview::factory()->create();
    $report = ReviewReport::create(['review_id' => $review->id, 'user_id' => User::factory()->create()->id, 'reason' => 'spam', 'status' => 'open']);
    if ($at) {
        $report->forceFill(['created_at' => $at])->save();
    }

    return $review;
}

function makeDisputedEngagement(float $netAmount = 1000): array
{
    $application = JobApplication::factory()->hired()->create(['net_amount' => $netAmount]);

    $engagement = JobEngagement::create([
        'application_id' => $application->id,
        'status' => EngagementStatus::Disputed,
        'agreed_amount' => $application->offer_amount,
        'service_fee' => $application->service_fee,
        'net_amount' => $netAmount,
    ]);

    // A real dispute is always preceded by a processed partial payment, which already set
    // partial_payment_amount on the cancellation — mirror that here so resolveDispute()'s
    // null-$finalAmount fallback (partial_payment_amount) has a real value to fall back to.
    $cancellation = JobCancellation::create([
        'engagement_id' => $engagement->id,
        'initiator_id' => $application->applicant_id,
        'cancellation_type' => 'dispute',
        'reason_category' => 'other',
        'reason_details' => 'test reason',
        'partial_payment_amount' => $netAmount * 0.5,
        'is_dispute' => true,
    ]);

    $dispute = JobPaymentDispute::create([
        'cancellation_id' => $cancellation->id,
        'disputed_by' => $application->applicant_id,
        'dispute_reason' => 'incorrect_amount',
        'dispute_details' => 'test details',
        'status' => DisputeStatus::Pending,
    ]);

    return compact('application', 'engagement', 'cancellation', 'dispute');
}
