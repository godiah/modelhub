<?php

use App\Enums\LicenceTier;
use App\Models\JobApplication;
use App\Models\JobDeliverable;
use App\Models\JobEngagement;
use App\Models\ModelJob;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\Ledger\LedgerService;
use App\Services\Payments\EarningsInsights;
use App\Services\Payments\EarningsService;
use App\Services\Payments\EscrowService;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PayoutService;
use App\Services\Payments\RefundService;
use App\Support\Ledger\LedgerLine;
use Illuminate\Support\Facades\Notification;

/*
 * The Earnings page: the balance and its one action, what was earned in a period against the one before, a chart by source, top earners,
 * and one feed of every sale, job payment and withdrawal.
 */

beforeEach(function () {
    config(['marketplace.purchases_enabled' => true, 'marketplace.jobs_escrow_enabled' => true, 'payments.fake.delay_seconds' => 0]);
    Notification::fake();

    $this->seller = User::factory()->create(['name' => 'Seller Person']);
    SellerProfile::factory()->approved()->create(['user_id' => $this->seller->id]);
});

/** A sale of a model, backdated: the seller's share is 85% of the price (15% commission). */
function saleOf(string $title, int $priceMinor, int $daysAgo, ?User $seller = null, bool $refunded = false): Payment
{
    $seller ??= test()->seller;
    $product = Product::factory()->published()->create(['user_id' => $seller->id, 'title' => $title, 'price_minor' => $priceMinor]);
    $buyer = User::factory()->create();
    $payments = app(PaymentService::class);

    test()->travelTo(now()->subDays($daysAgo));
    $payment = $payments->refresh($payments->start($buyer, $product, LicenceTier::Standard, '0712345675'));
    test()->travelBack();

    if ($refunded) {
        app(RefundService::class)->refund($payment, staffWith('Finance'), 'Broken file for this test.');
    }

    return $payment->fresh();
}

function insightsFor(User $user, string $range = '30d'): array
{
    return app(EarningsInsights::class)->overview($user, $range);
}

it('adds up what was earned in a period and compares it with the one before', function () {
    saleOf('Oak armchair', 120000, 3);   // 1,020.00 share, in the last 30 days
    saleOf('Pine table', 100000, 10);     // 850.00, in the last 30 days
    saleOf('Old lamp', 200000, 40);       // 1,700.00, in the 30 days before
    saleOf('Ancient vase', 100000, 100);  // outside both

    $overview = insightsFor($this->seller);

    expect($overview['earned'])->toBe(187000)->and($overview['previous'])->toBe(170000)->and($overview['change'])->toBe(10.0)->and($overview['count'])->toBe(2)->and($overview['average'])->toBe(93500)
        ->and($overview['by_source'])->toBe(['models' => 187000, 'jobs' => 0])->and($overview['unit'])->toBe('day')->and($overview['buckets'])->toHaveCount(30);
    expect(collect($overview['top'])->pluck('title')->all())->toBe(['Oak armchair', 'Pine table']);
});

it('has no growth figure when there was nothing before, none at all time, and counts a refunded sale for nothing', function () {
    saleOf('Oak armchair', 120000, 3);
    saleOf('Refunded chair', 200000, 4, refunded: true);

    $thirty = insightsFor($this->seller);
    expect($thirty['earned'])->toBe(102000)->and($thirty['previous'])->toBe(0)->and($thirty['change'])->toBeNull();

    $all = insightsFor($this->seller, 'all');
    expect($all['previous'])->toBeNull()->and($all['change'])->toBeNull()->and($all['earned'])->toBe(102000);
});

it('chooses day, week or month buckets to keep the chart readable, and fills the quiet days with zero', function () {
    saleOf('Oak armchair', 120000, 2);

    expect(insightsFor($this->seller, '7d'))->toMatchArray(['unit' => 'day'])->and(insightsFor($this->seller, '7d')['buckets'])->toHaveCount(7)
        ->and(insightsFor($this->seller, '90d')['unit'])->toBe('week')->and(insightsFor($this->seller, 'ytd')['unit'])->toBeIn(['day', 'week', 'month']);

    $week = insightsFor($this->seller, '7d')['buckets'];
    expect(collect($week)->sum(fn ($b) => $b['models']))->toBe(102000)->and(collect($week)->where('models', 0)->count())->toBe(6);
});

it('falls back to 30 days and to everything for names it does not know', function () {
    expect(EarningsInsights::range('nonsense'))->toBe('30d')->and(EarningsInsights::range(null))->toBe('30d')->and(EarningsInsights::range('ytd'))->toBe('ytd')
        ->and(EarningsInsights::filter('nope'))->toBe('all')->and(EarningsInsights::filter('jobs'))->toBe('jobs');
});

it('counts what jobs have paid, as its own source, and what is still to come from them', function () {
    $client = User::factory()->create();
    $application = JobApplication::factory()->hired()->create([
        'job_id' => ModelJob::factory()->create(['user_id' => $client->id, 'title' => 'Villa render']), 'poster_id' => $client->id, 'applicant_id' => $this->seller->id, 'offer_amount' => 1100, 'service_fee' => 100, 'net_amount' => 1000,
    ]);
    $engagement = JobEngagement::create(['application_id' => $application->id, 'status' => 'applicant_accepted', 'agreed_amount' => 1100, 'service_fee' => 100, 'net_amount' => 1000, 'employer_accepted_at' => now()]);
    foreach (['A', 'B', 'C'] as $t) {
        JobDeliverable::create(['engagement_id' => $engagement->id, 'title' => $t, 'description' => 'd', 'status' => 'pending']);
    }
    $payments = app(PaymentService::class);
    $payments->refresh($payments->startEscrow($client, $engagement, '0712345675'));
    $engagement->deliverables()->first()->update(['status' => 'approved', 'approved_at' => now()]);
    app(EscrowService::class)->releaseApproved($engagement->fresh());
    saleOf('Oak armchair', 120000, 1);

    $overview = insightsFor($this->seller, '7d');
    expect($overview['by_source'])->toBe(['models' => 102000, 'jobs' => 33333])->and($overview['earned'])->toBe(135333);

    $incoming = app(EarningsInsights::class)->incoming($this->seller, app(EarningsService::class)->summary($this->seller)['pending']);
    expect($incoming)->toMatchArray(['in_hold' => 102000, 'from_jobs' => 66667, 'jobs_count' => 1])->and($incoming['next_release'])->not->toBeNull();
});

it('lists sales, job payments and withdrawals in one feed, newest first, and filters it', function () {
    saleOf('Oak armchair', 120000, 12);
    saleOf('Refunded chair', 200000, 5, refunded: true);
    $this->travel(12)->days();
    app(EarningsService::class)->releaseDue();
    $payouts = app(PayoutService::class);
    $payout = $payouts->request($this->seller, 50000, '0712345676');
    $this->travelBack();

    $feed = app(EarningsInsights::class)->feed($this->seller, 'all', 1);
    expect($feed->total())->toBe(3)->and($feed->pluck('kind')->all())->toBeArray();
    $sale = $feed->firstWhere('title', 'Oak armchair');
    $refunded = $feed->firstWhere('title', 'Refunded chair');
    $withdrawal = $feed->firstWhere('kind', 'withdrawal');
    expect($sale->minor)->toBe(102000)->and($sale->struck)->toBeFalse()->and($refunded->minor)->toBe(0)->and($refunded->struck)->toBeTrue()->and($refunded->status[0])->toBe('Refunded')
        ->and($withdrawal->minor)->toBe(-50000)->and($withdrawal->payout->is($payout))->toBeTrue();

    expect(app(EarningsInsights::class)->feed($this->seller, 'sales', 1)->total())->toBe(2)->and(app(EarningsInsights::class)->feed($this->seller, 'withdrawals', 1)->total())->toBe(1)
        ->and(app(EarningsInsights::class)->feed($this->seller, 'jobs', 1)->total())->toBe(0);
});

it('shows the balance with a withdraw button, the earned figure with its growth, the chart, the breakdown and the activity', function () {
    saleOf('Oak armchair', 120000, 3);
    saleOf('Old lamp', 200000, 40);
    $this->travel(8)->days();
    app(EarningsService::class)->releaseDue();
    $this->withSession(['session_rules.web.last' => now()->timestamp]);
    $this->travelBack();
    $this->withSession(['session_rules.web.last' => now()->timestamp]);

    $page = $this->actingAs($this->seller)->get(route('earnings.index'))->assertOk();
    $page->assertSee('Available to withdraw')->assertSee('Withdraw to M-Pesa')->assertSee('In the hold period')->assertSee('Still to come from jobs')->assertSee('Withdrawn so far')
        ->assertSee('Earned')->assertSee('Last 30 days')->assertSee('Earnings over time')->assertSee('Hover a bar')->assertSee('data-earnings-chart', false)
        ->assertSee('Where it comes from')->assertSee('Top earners')->assertSee('Oak armchair')->assertSee('Activity')->assertSee('Model sales')->assertSee('Jobs')
        ->assertSee('7D')->assertSee('30D')->assertSee('90D')->assertSee('YTD')->assertSee('How earnings and withdrawals work');
});

it('switches the period from the toggle and keeps the activity filter', function () {
    saleOf('Oak armchair', 120000, 3);
    saleOf('Old lamp', 200000, 40);
    $this->actingAs($this->seller);

    $this->get(route('earnings.index', ['range' => '7d']))->assertOk()->assertSee('Last 7 days')->assertSee('aria-current="true"', false);
    $this->get(route('earnings.index', ['range' => 'all']))->assertOk()->assertSee('All time')->assertSee('Since your first payment');
    $this->get(route('earnings.index', ['range' => 'bogus', 'show' => 'bogus']))->assertOk()->assertSee('Last 30 days');
    $this->get(route('earnings.index', ['show' => 'withdrawals']))->assertOk()->assertSee('Nothing matches this filter.');
    $this->get(route('earnings.index', ['range' => '90d', 'show' => 'sales']))->assertOk()->assertSee('range=90d&amp;show=sales', false);
});

it('says so when nothing was earned in the period, and offers a longer one', function () {
    saleOf('Old lamp', 200000, 60);

    $this->actingAs($this->seller)->get(route('earnings.index', ['range' => '7d']))->assertOk()->assertSee('Nothing earned in this period')->assertSee('Show all time');
});

it('welcomes a member who has earned nothing, instead of showing empty charts', function () {
    $newcomer = User::factory()->create();

    $this->actingAs($newcomer)->get(route('earnings.index'))->assertOk()->assertSee('No earnings yet')->assertSee('Sell a model')->assertSee('Find a project')->assertDontSee('Earnings over time')->assertDontSee('Top earners');
});

it('puts the withdrawal form in a window, opened by the button, with the fee and what they will receive', function () {
    saleOf('Oak armchair', 400000, 20);
    $this->travel(2)->days();
    app(EarningsService::class)->releaseDue();
    $this->travelBack();

    $page = $this->actingAs($this->seller)->get(route('earnings.index'))->assertOk();
    $page->assertSee("\$dispatch('open-modal', 'withdraw')", false)->assertSee('name="amount"', false)->assertSee('You receive')->assertSee('Check the number carefully')->assertSee('Ask to withdraw');
    expect($this->seller->fresh())->not->toBeNull();
});

it('explains why it cannot be withdrawn yet: below the minimum, or one already in progress', function () {
    $this->actingAs($this->seller)->get(route('earnings.index'))->assertOk()->assertSee('You can withdraw once you have')->assertDontSee("open-modal', 'withdraw'", false);

    $ledger = app(LedgerService::class);
    $ledger->post('seed', 'seed:funds', [LedgerLine::debit($ledger->platformAccount('gateway'), 300000), LedgerLine::credit($ledger->userAccount($this->seller, 'available'), 300000)], 'Funds');
    $payout = app(PayoutService::class)->request($this->seller, 100000, '0712345675');

    $this->get(route('earnings.index'))->assertOk()->assertSee('Withdrawal of Ksh1,000: waiting for approval')->assertSee('A withdrawal is in progress')->assertSee('Cancel');
    $this->delete(route('earnings.cancel', $payout))->assertSessionHas('success');
    $this->get(route('earnings.index'))->assertOk()->assertDontSee('A withdrawal is in progress')->assertSee('Withdraw to M-Pesa');
    expect(Payout::first()->status->value)->toBe('cancelled');
});

it('opens the withdrawal window again with the error when the request is refused', function () {
    $ledger = app(LedgerService::class);
    $ledger->post('seed', 'seed:funds', [LedgerLine::debit($ledger->platformAccount('gateway'), 300000), LedgerLine::credit($ledger->userAccount($this->seller, 'available'), 300000)], 'Funds');

    $this->actingAs($this->seller)->from(route('earnings.index'))->post(route('earnings.withdraw'), ['amount' => 1000, 'phone' => 'not a number'])->assertRedirect(route('earnings.index'))->assertSessionHasErrors('phone');
    $this->get(route('earnings.index'))->assertOk()->assertSee('Enter a valid Safaricom number');
});

it('does not leak another member\'s earnings', function () {
    saleOf('Oak armchair', 120000, 3);
    $other = User::factory()->create();

    $this->actingAs($other)->get(route('earnings.index'))->assertOk()->assertDontSee('Oak armchair')->assertSee('No earnings yet');
});
