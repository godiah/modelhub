<?php

use App\Console\Commands\ReleaseEscrow;
use App\Enums\EngagementStatus;
use App\Enums\GatewayState;
use App\Enums\LicenceTier;
use App\Enums\PaymentStatus;
use App\Models\EscrowRefund;
use App\Models\JobApplication;
use App\Models\JobDeliverable;
use App\Models\JobEngagement;
use App\Models\LedgerTransaction;
use App\Models\ModelJob;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\StaffActivity;
use App\Models\User;
use App\Notifications\EscrowRefundedNotification;
use App\Notifications\JobAwaitingFundingNotification;
use App\Notifications\JobEarningReleasedNotification;
use App\Notifications\JobFundedNotification;
use App\Notifications\JobFundedReceiptNotification;
use App\Services\Ledger\LedgerReport;
use App\Services\Ledger\LedgerService;
use App\Services\Payments\EarningsService;
use App\Services\Payments\EscrowService;
use App\Services\Payments\PartialPaymentService;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PayoutService;
use App\Services\Payments\RefundService;
use App\Support\Navigation\SidebarMenu;
use App\Support\Navigation\StaffMenu;
use App\Support\Payments\PaymentOutcome;
use Illuminate\Support\Facades\Notification;

/*
 * Job escrow: the freelancer accepts, the client funds the agreed amount by M-Pesa, the money is held in a ledger account for that job, and each
 * approved deliverable releases its share to the freelancer (less the platform's service fee). Agreed KES 1,100, fee KES 100, net KES 1,000, three deliverables.
 */

beforeEach(function () {
    config(['marketplace.jobs_escrow_enabled' => true, 'marketplace.purchases_enabled' => true, 'payments.fake.delay_seconds' => 0]);
    Notification::fake();

    $this->client = User::factory()->create(['name' => 'Client Person']);
    $this->freelancer = User::factory()->create(['name' => 'Freelancer Person']);
    $this->ledger = app(LedgerService::class);
});

function jobOffer(array $money = [], EngagementStatus $status = EngagementStatus::EmployerAccepted): JobEngagement
{
    $application = JobApplication::factory()->hired()->create([
        'job_id' => ModelJob::factory()->create(['user_id' => test()->client->id, 'title' => 'Villa render']),
        'poster_id' => test()->client->id, 'applicant_id' => test()->freelancer->id,
        'offer_amount' => $money['agreed'] ?? 1100, 'service_fee' => $money['fee'] ?? 100, 'net_amount' => ($money['agreed'] ?? 1100) - ($money['fee'] ?? 100),
    ]);

    $engagement = JobEngagement::create([
        'application_id' => $application->id, 'status' => $status, 'agreed_amount' => $money['agreed'] ?? 1100, 'service_fee' => $money['fee'] ?? 100,
        'net_amount' => ($money['agreed'] ?? 1100) - ($money['fee'] ?? 100), 'employer_accepted_at' => now(),
    ]);

    foreach (['Concept', 'Model', 'Render'] as $title) {
        JobDeliverable::create(['engagement_id' => $engagement->id, 'title' => $title, 'description' => 'd', 'status' => 'pending']);
    }

    return $engagement;
}

/** An accepted offer waiting for the client's money. */
function awaitingFunding(array $money = []): JobEngagement
{
    return jobOffer($money, EngagementStatus::ApplicantAccepted);
}

/** An accepted offer that has been funded through the real flow, so it is Active with the money in escrow. */
function fundedJob(array $money = []): JobEngagement
{
    $engagement = awaitingFunding($money);
    $payments = app(PaymentService::class);
    $payments->refresh($payments->startEscrow(test()->client, $engagement, '0712345675'));

    return $engagement->fresh();
}

function escrowBalance(JobEngagement $engagement): int
{
    return app(LedgerService::class)->escrowAccount($engagement)->balanceMinor();
}

/** The client approves a deliverable (it has to have been submitted first). */
function approveDeliverable(JobEngagement $engagement, int $index = 0): void
{
    $deliverable = $engagement->deliverables()->orderBy('id')->get()[$index];
    $deliverable->update(['status' => 'submitted', 'submitted_at' => now()]);
    test()->actingAs(test()->client)->post(route('engagements.deliverables.approve', $deliverable), [])->assertRedirect();
}

/** ---------------------------------------------------------------- accepting the offer */
it('starts work at once when job escrow is off, as engagements always did', function () {
    config(['marketplace.jobs_escrow_enabled' => false]);
    $engagement = jobOffer();

    $this->actingAs($this->freelancer)->post(route('engagements.respond', $engagement), ['response' => 'accepted'])->assertRedirect();

    expect($engagement->fresh()->status)->toBe(EngagementStatus::Active)->and($engagement->fresh()->started_at)->not->toBeNull();
    Notification::assertNotSentTo($this->client, JobAwaitingFundingNotification::class);
});

it('waits for funding when job escrow is on: nothing starts, the job closes and the client is asked to pay', function () {
    $engagement = jobOffer();

    $this->actingAs($this->freelancer)->post(route('engagements.respond', $engagement), ['response' => 'accepted', 'notes' => 'Happy to.'])->assertRedirect();

    $engagement->refresh();
    expect($engagement->status)->toBe(EngagementStatus::ApplicantAccepted)->and($engagement->started_at)->toBeNull()->and($engagement->escrow_minor)->toBe(0)
        ->and($engagement->application->job->fresh()->is_active)->toBeFalse()->and($engagement->application->fresh()->status->value)->toBe('hired');
    Notification::assertSentTo($this->client, JobAwaitingFundingNotification::class, fn ($n) => $n->amountMinor === 110000 && $n->title === 'Villa render');
});

it('lets the freelancer decline as before', function () {
    $engagement = jobOffer();

    $this->actingAs($this->freelancer)->post(route('engagements.respond', $engagement), ['response' => 'declined'])->assertRedirect();

    expect($engagement->fresh()->status)->toBe(EngagementStatus::Cancelled);
});

it('shows the client how to fund and the freelancer that they are waiting, and keeps the freelancer from submitting', function () {
    $engagement = awaitingFunding();
    $deliverable = $engagement->deliverables()->first();

    $this->actingAs($this->client)->get(route('engagements.show', $engagement))->assertOk()->assertSee('Fund this job to start work')->assertSee('Your M-Pesa number')->assertSee('Awaiting funding')->assertSee(route('engagements.fund', $engagement), false);
    $this->actingAs($this->freelancer)->get(route('engagements.show', $engagement))->assertOk()->assertSee('Waiting for the client to put')->assertDontSee('Your M-Pesa number');

    $this->post(route('engagements.deliverables.submit', $deliverable), ['submission_notes' => 'Here you go'])->assertSessionHas('error');
    expect($deliverable->fresh()->status)->toBe('pending');
});

/** ---------------------------------------------------------------- the client funds it */
it('sends the prompt and waits: the payment is for the agreed amount, to the freelancer, with the fee noted, and nothing starts yet', function () {
    $engagement = awaitingFunding();

    $response = $this->actingAs($this->client)->post(route('engagements.fund', $engagement), ['phone' => '0712 345 675']);

    $payment = Payment::sole();
    $response->assertRedirect(route('payments.show', $payment));
    expect($payment->purpose)->toBe(Payment::PURPOSE_ESCROW)->and($payment->status)->toBe(PaymentStatus::Pending)->and($payment->amount_minor)->toBe(110000)->and($payment->user_id)->toBe($this->client->id)
        ->and($payment->seller_id)->toBe($this->freelancer->id)->and($payment->engagement_id)->toBe($engagement->id)->and($payment->product_id)->toBeNull()->and($payment->tier)->toBeNull()
        ->and($payment->commission_minor)->toBe(10000)->and($payment->seller_share_minor)->toBe(100000)->and($payment->msisdn)->toBe('254712345675');
    expect($engagement->fresh()->status)->toBe(EngagementStatus::ApplicantAccepted)->and($engagement->fresh()->escrow_minor)->toBe(0)->and(LedgerTransaction::count())->toBe(0);
});

it('presses pay twice and gets the same prompt', function () {
    config(['payments.fake.delay_seconds' => 60]);
    $engagement = awaitingFunding();

    $this->actingAs($this->client)->post(route('engagements.fund', $engagement), ['phone' => '0712345675']);
    $this->post(route('engagements.fund', $engagement), ['phone' => '0712345675'])->assertRedirect(route('payments.show', Payment::sole()));

    expect(Payment::count())->toBe(1);
});

it('refuses to start funding when it should not', function () {
    $engagement = awaitingFunding();
    $this->actingAs($this->client);

    $this->post(route('engagements.fund', $engagement), ['phone' => ''])->assertSessionHasErrors('phone');
    $this->post(route('engagements.fund', $engagement), ['phone' => '12345'])->assertSessionHas('error');

    $this->actingAs($this->freelancer)->post(route('engagements.fund', $engagement), ['phone' => '0712345675'])->assertNotFound();
    $this->actingAs(User::factory()->create())->post(route('engagements.fund', $engagement), ['phone' => '0712345675'])->assertNotFound();

    config(['marketplace.jobs_escrow_enabled' => false]);
    $this->actingAs($this->client)->post(route('engagements.fund', $engagement), ['phone' => '0712345675'])->assertSessionHas('error');
    config(['marketplace.jobs_escrow_enabled' => true]);

    $active = jobOffer(status: EngagementStatus::Active);
    $this->post(route('engagements.fund', $active), ['phone' => '0712345675'])->assertSessionHas('error');

    expect(Payment::count())->toBe(0);
});

it('cannot fund an amount M-Pesa cannot take', function () {
    $service = app(EscrowService::class);

    expect($service->cannotFund(awaitingFunding(['agreed' => 1100.50, 'fee' => 100]), $this->client))->toContain('whole number of shillings')
        ->and($service->cannotFund(awaitingFunding(['agreed' => 200000, 'fee' => 20000]), $this->client))->toContain('150,000')
        ->and($service->cannotFund(awaitingFunding(['agreed' => 0, 'fee' => 0]), $this->client))->toContain('whole number')
        ->and($service->cannotFund(awaitingFunding(), $this->client))->toBeNull();
});

it('opens the job for work when the money arrives: escrow held in its own account, books balanced, both sides told', function () {
    $engagement = awaitingFunding();
    $payments = app(PaymentService::class);

    $payment = $payments->refresh($payments->startEscrow($this->client, $engagement, '0712345675'));

    $engagement->refresh();
    expect($payment->status)->toBe(PaymentStatus::Succeeded)->and($payment->received_minor)->toBe(110000)->and($payment->receipt)->not->toBeNull()
        ->and($engagement->status)->toBe(EngagementStatus::Active)->and($engagement->started_at)->not->toBeNull()->and($engagement->payment_escrowed_at)->not->toBeNull()->and($engagement->escrow_minor)->toBe(110000);
    expect(escrowBalance($engagement))->toBe(110000)->and($this->ledger->platformAccount('gateway')->balanceMinor())->toBe(110000)->and($this->ledger->trialBalance()['balanced'])->toBeTrue()
        ->and(LedgerTransaction::where('type', 'escrow_funded')->sole()->idempotency_key)->toBe("escrow:fund:payment:{$payment->id}");
    Notification::assertSentTo($this->client, JobFundedReceiptNotification::class, fn ($n) => $n->amountMinor === 110000 && $n->receipt === $payment->receipt);
    Notification::assertSentTo($this->freelancer, JobFundedNotification::class, fn ($n) => $n->title === 'Villa render');
});

it('records the money once however many times the gateway says so', function () {
    $engagement = awaitingFunding();
    $payments = app(PaymentService::class);
    $payment = $payments->startEscrow($this->client, $engagement, '0712345675');
    $outcome = new PaymentOutcome($payment->gateway_reference, GatewayState::Succeeded, 'RCPT123456', 110000);

    $payments->applyOutcome($payment, $outcome);
    $payments->applyOutcome($payment, $outcome);
    $payments->refresh($payment);

    expect(LedgerTransaction::where('type', 'escrow_funded')->count())->toBe(1)->and(escrowBalance($engagement))->toBe(110000)->and($engagement->fresh()->escrow_minor)->toBe(110000);
    Notification::assertSentToTimes($this->freelancer, JobFundedNotification::class, 1);
});

it('parks money that arrives in the wrong amount, and leaves the job waiting', function () {
    $engagement = awaitingFunding();
    $payments = app(PaymentService::class);
    $payment = $payments->startEscrow($this->client, $engagement, '0712345675');

    $payments->applyOutcome($payment, new PaymentOutcome($payment->gateway_reference, GatewayState::Succeeded, 'ODD0000001', 100000));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Review)->and($engagement->fresh()->status)->toBe(EngagementStatus::ApplicantAccepted)->and($engagement->fresh()->escrow_minor)->toBe(0)
        ->and($this->ledger->platformAccount('suspense')->balanceMinor())->toBe(100000)->and(escrowBalance($engagement))->toBe(0);
});

it('parks money that arrives after the job was cancelled in the meantime', function () {
    $engagement = awaitingFunding();
    $payments = app(PaymentService::class);
    $payment = $payments->startEscrow($this->client, $engagement, '0712345675');
    $engagement->update(['status' => EngagementStatus::Cancelled, 'cancelled_at' => now()]);

    $payments->applyOutcome($payment, new PaymentOutcome($payment->gateway_reference, GatewayState::Succeeded, 'LATE0000001', 110000));

    $payment->refresh();
    expect($payment->status)->toBe(PaymentStatus::Review)->and($payment->failure_reason)->toContain('no longer waiting for funding')->and($engagement->fresh()->escrow_minor)->toBe(0)
        ->and($this->ledger->platformAccount('suspense')->balanceMinor())->toBe(110000);
});

it('sends the client to the workspace once paid, from the waiting page and its status check', function () {
    config(['payments.fake.delay_seconds' => 60]);
    $engagement = awaitingFunding();
    $this->actingAs($this->client)->post(route('engagements.fund', $engagement), ['phone' => '0712345675']);
    $payment = Payment::sole();

    $this->get(route('payments.show', $payment))->assertOk()->assertSee('Fund the job')->assertSee('Villa render')->assertSee('Held in escrow until you approve the work');
    $this->getJson(route('payments.status', $payment))->assertOk()->assertJson(['status' => 'pending', 'redirect' => null]);

    $this->travel(2)->minutes();
    $this->getJson(route('payments.status', $payment))->assertOk()->assertJson(['status' => 'succeeded', 'redirect' => route('engagements.show', $engagement)]);
    $this->get(route('payments.show', $payment))->assertRedirect(route('engagements.show', $engagement));
});

it('lets the freelancer work once it is funded, and shows the escrow on the workspace', function () {
    $engagement = fundedJob();

    $this->actingAs($this->client)->get(route('engagements.show', $engagement))->assertOk()->assertDontSee('Fund this job to start work')->assertSee('Held in escrow')->assertSee('Ksh0 released');
    $this->actingAs($this->freelancer)->get(route('engagements.show', $engagement))->assertOk()->assertDontSee('Waiting for the client to put');
});

/** ---------------------------------------------------------------- release as work is approved */
it('releases each approved deliverable\'s share, so that all three move exactly the net amount and the fee', function () {
    $engagement = fundedJob();

    approveDeliverable($engagement, 0);
    $engagement->refresh();
    expect($this->ledger->balances($this->freelancer)['available'])->toBe(33333)->and($this->ledger->platformAccount('revenue')->balanceMinor())->toBe(3333)->and(escrowBalance($engagement))->toBe(73334)
        ->and($engagement->released_net_minor)->toBe(33333)->and($engagement->released_fee_minor)->toBe(3333)->and($engagement->status)->toBe(EngagementStatus::Active)->and($engagement->payment_released_at)->toBeNull();
    Notification::assertSentTo($this->freelancer, JobEarningReleasedNotification::class, fn ($n) => $n->amountMinor === 33333);

    approveDeliverable($engagement, 1);
    expect($this->ledger->balances($this->freelancer)['available'])->toBe(66667)->and($this->ledger->platformAccount('revenue')->balanceMinor())->toBe(6667);

    approveDeliverable($engagement, 2);
    $engagement->refresh();
    expect($this->ledger->balances($this->freelancer)['available'])->toBe(100000)->and($this->ledger->platformAccount('revenue')->balanceMinor())->toBe(10000)->and(escrowBalance($engagement))->toBe(0)
        ->and($engagement->status)->toBe(EngagementStatus::Completed)->and($engagement->payment_released_at)->not->toBeNull()->and($this->ledger->platformAccount('gateway')->balanceMinor())->toBe(110000)
        ->and($this->ledger->trialBalance()['balanced'])->toBeTrue();
    Notification::assertSentToTimes($this->freelancer, JobEarningReleasedNotification::class, 3);
});

it('keeps the whole platform reconciled while money is in escrow, partly released and paid out', function () {
    $engagement = fundedJob();
    $report = app(LedgerReport::class);

    expect($report->summary())->toMatchArray(['gateway' => 110000, 'escrow' => 110000, 'owed' => 110000, 'earned' => 0, 'reconciled' => true]);

    approveDeliverable($engagement, 0);

    expect($report->summary())->toMatchArray(['gateway' => 110000, 'escrow' => 73334, 'available' => 33333, 'commission' => 3333, 'owed' => 106667, 'earned' => 3333, 'reconciled' => true, 'difference' => 0]);
});

it('releases nothing for a job that was never funded, as before', function () {
    $legacy = jobOffer(status: EngagementStatus::Active);
    $legacy->update(['started_at' => now()]);

    approveDeliverable($legacy, 0);

    expect(LedgerTransaction::count())->toBe(0)->and($this->ledger->balances($this->freelancer)['available'])->toBe(0);
    Notification::assertNotSentTo($this->freelancer, JobEarningReleasedNotification::class);
});

it('never takes back what was released when a deliverable is added later, and still ends exact', function () {
    $engagement = fundedJob();
    approveDeliverable($engagement, 0);
    approveDeliverable($engagement, 1);
    expect($this->ledger->balances($this->freelancer)['available'])->toBe(66667);

    JobDeliverable::create(['engagement_id' => $engagement->id, 'title' => 'Extra', 'description' => 'd', 'status' => 'pending']);
    expect(app(EscrowService::class)->releaseApproved($engagement->fresh()))->toBe(0)->and($this->ledger->balances($this->freelancer)['available'])->toBe(66667);

    approveDeliverable($engagement, 2);
    expect($this->ledger->balances($this->freelancer)['available'])->toBe(75000);

    approveDeliverable($engagement, 3);
    expect($this->ledger->balances($this->freelancer)['available'])->toBe(100000)->and(escrowBalance($engagement))->toBe(0)->and($this->ledger->platformAccount('revenue')->balanceMinor())->toBe(10000);
});

it('is safe to release again and again', function () {
    $engagement = fundedJob();
    approveDeliverable($engagement, 0);
    $escrow = app(EscrowService::class);

    expect($escrow->releaseApproved($engagement->fresh()))->toBe(0)->and($escrow->releaseApproved($engagement->fresh()))->toBe(0)
        ->and(LedgerTransaction::where('type', 'escrow_released')->count())->toBe(1);
});

it('catches up on an approval whose release never happened, from the hourly sweep', function () {
    $engagement = fundedJob();
    // The approval was saved but the release did not run (say the process died in between)
    $engagement->deliverables()->orderBy('id')->first()->update(['status' => 'approved', 'approved_at' => now()]);
    expect($this->ledger->balances($this->freelancer)['available'])->toBe(0);

    $this->artisan(ReleaseEscrow::class)->expectsOutputToContain('Released escrow on 1 jobs.')->assertSuccessful();

    expect($this->ledger->balances($this->freelancer)['available'])->toBe(33333);
    $this->artisan(ReleaseEscrow::class)->expectsOutputToContain('Released escrow on 0 jobs.')->assertSuccessful();
});

it('lets a freelancer withdraw what a job released', function () {
    $engagement = fundedJob();
    approveDeliverable($engagement, 0);
    approveDeliverable($engagement, 1);
    approveDeliverable($engagement, 2);

    $payout = app(PayoutService::class)->request($this->freelancer, 90000, '0712345675');

    expect($payout)->toBeInstanceOf(Payout::class)->and($this->ledger->balances($this->freelancer)['available'])->toBe(10000);
});

/** ---------------------------------------------------------------- staff */
it('does not refund escrow money from the payments screen, but can return money that arrived unmatched', function () {
    $engagement = fundedJob();
    $payment = Payment::where('purpose', 'escrow')->sole();
    $refunds = app(RefundService::class);

    expect($refunds->cannotRefund($payment))->toContain('held in the job');

    // Money that arrived in the wrong amount for a job is parked, and can go back
    $other = awaitingFunding();
    $payments = app(PaymentService::class);
    $wrong = $payments->startEscrow($this->client, $other, '0712345675');
    $payments->applyOutcome($wrong, new PaymentOutcome($wrong->gateway_reference, GatewayState::Succeeded, 'ODD0000002', 90000));
    $wrong->refresh();

    expect($wrong->status)->toBe(PaymentStatus::Review)->and($refunds->cannotRefund($wrong))->toBeNull();
    expect($refunds->refund($wrong, staffWith('Finance'), 'You paid the wrong amount.'))->toBeInstanceOf(Payment::class)->and($this->ledger->platformAccount('suspense')->balanceMinor())->toBe(0);
});

it('shows escrow payments to staff with the job, not a model', function () {
    fundedJob();
    $payment = Payment::where('purpose', 'escrow')->sole();
    $finance = staffWith('Finance');

    $this->actingAs($finance, 'staff')->get(route('admin.payments.index'))->assertOk()->assertSee('Villa render')->assertSee('Job escrow')->assertDontSee('A deleted model');
    $this->get(route('admin.payments.index', ['q' => 'Villa']))->assertSee($payment->reference);
    $this->get(route('admin.payments.show', $payment))->assertOk()->assertSee('Villa render')->assertSee('Job escrow')->assertSee('Escrow funded')->assertDontSee('Record a refund');
    $this->get(route('admin.ledger.index'))->assertOk()->assertSee('The books add up.')->assertSee('in job escrow');
});

/** ---------------------------------------------------------------- step b: cancelling, partial payment, disputes and returning what is left */
function cancelJob(JobEngagement $engagement, User $by, string $type = 'client_initiated'): void
{
    test()->actingAs($by)->post(route('engagements.cancel', $engagement), ['cancellation_type' => $type, 'reason_category' => 'other', 'cancellation_reason' => 'Plans changed on this project.', 'terms' => '1'])->assertRedirect();
}

/** After the client's review window, or at once if the freelancer walked away: what is left is the client's to have back. */
function recordReturn(JobEngagement $engagement, $by = null)
{
    return app(EscrowService::class)->refundRemaining($engagement->fresh(), $by ?? staffWith('Finance'), 'Sent from the portal.');
}

it('keeps what is left in escrow for a review window when the client cancels, then makes it the client\'s to have back', function () {
    $engagement = fundedJob();
    cancelJob($engagement, $this->client);

    $engagement->refresh();
    expect($engagement->status)->toBe(EngagementStatus::Cancelled)->and($engagement->escrow_refund_due_at->isFuture())->toBeTrue()->and(app(EscrowService::class)->summary($engagement)['state'])->toBe('waiting')
        ->and(JobEngagement::escrowRefundDue()->count())->toBe(0)->and(escrowBalance($engagement))->toBe(110000);
    expect(recordReturn($engagement))->toContain('review window is still open');

    $this->travel(8)->days();
    expect(JobEngagement::escrowRefundDue()->count())->toBe(1)->and(app(EscrowService::class)->summary($engagement->fresh())['state'])->toBe('due');
});

it('returns the whole escrow to the client, in the books and with a record, when nothing was approved', function () {
    $engagement = fundedJob();
    cancelJob($engagement, $this->client);
    $this->travel(8)->days();

    $refund = recordReturn($engagement);

    expect($refund)->toBeInstanceOf(EscrowRefund::class)->and($refund->amount_minor)->toBe(110000)->and($refund->msisdn)->toBe('254712345675')->and($refund->funding_receipt)->not->toBeNull()
        ->and($refund->user_id)->toBe($this->client->id)->and($refund->note)->toBe('Sent from the portal.');
    $engagement->refresh();
    expect($engagement->refunded_minor)->toBe(110000)->and($engagement->escrowRemainingMinor())->toBe(0)->and($engagement->escrow_refund_due_at)->toBeNull()->and(escrowBalance($engagement))->toBe(0)
        ->and($this->ledger->platformAccount('gateway')->balanceMinor())->toBe(0)->and($this->ledger->trialBalance()['balanced'])->toBeTrue()
        ->and(LedgerTransaction::where('type', 'escrow_refund')->sole()->idempotency_key)->toBe("escrow:refund:{$engagement->id}:110000");
    Notification::assertSentTo($this->client, EscrowRefundedNotification::class, fn ($n) => $n->amountMinor === 110000 && $n->phone === '0712 345 675');
    expect(StaffActivity::where('action', 'escrow.refunded')->exists())->toBeTrue()->and(recordReturn($engagement))->toBe('Nothing is left in escrow for this job.');
});

it('makes the escrow the client\'s to have back at once when the freelancer walks away', function () {
    $engagement = fundedJob();
    cancelJob($engagement, $this->freelancer, 'freelancer_initiated');

    expect(JobEngagement::escrowRefundDue()->count())->toBe(1)->and(recordReturn($engagement))->toBeInstanceOf(EscrowRefund::class);
});

it('keeps what the freelancer already earned and returns only what is left', function () {
    $engagement = fundedJob();
    approveDeliverable($engagement, 0);
    cancelJob($engagement, $this->freelancer, 'freelancer_initiated');

    recordReturn($engagement);

    expect($this->ledger->balances($this->freelancer)['available'])->toBe(33333)->and(escrowBalance($engagement))->toBe(0)->and($engagement->fresh()->refunded_minor)->toBe(73334)
        ->and($this->ledger->platformAccount('gateway')->balanceMinor())->toBe(36666)->and(app(LedgerReport::class)->summary())->toMatchArray(['reconciled' => true, 'escrow' => 0, 'available' => 33333, 'commission' => 3333]);
});

it('pays extra for unapproved work when the freelancer accepts a partial payment, and returns the rest', function () {
    $engagement = fundedJob();
    approveDeliverable($engagement, 0);
    cancelJob($engagement, $this->client);

    $this->actingAs($this->client)->post(route('engagements.process-partial-payment', $engagement->id), ['payment_amount' => 300])->assertRedirect();
    $payment = $engagement->partialPayments()->sole();
    expect($payment->amount)->toBe('300.00')->and($engagement->fresh()->escrow_refund_due_at)->toBeNull()->and(JobEngagement::escrowRefundDue()->count())->toBe(0);
    $this->travel(30)->days();
    $this->withSession(['session_rules.web.last' => now()->timestamp]);
    expect(JobEngagement::escrowRefundDue()->count())->toBe(0);

    $this->actingAs($this->freelancer)->post(route('engagements.accept-partial-payment', $payment->id))->assertSessionMissing('error');

    $engagement->refresh();
    // 33,333 was released for the approved deliverable; now 30,000 more, with the fee in the same proportion (10%/90% of net)
    expect($engagement->status)->toBe(EngagementStatus::Settled)->and($this->ledger->balances($this->freelancer)['available'])->toBe(63333)->and($engagement->released_net_minor)->toBe(63333)->and($engagement->released_fee_minor)->toBe(6333)
        ->and($engagement->escrowRemainingMinor())->toBe(110000 - 63333 - 6333)->and($engagement->escrow_refund_due_at)->not->toBeNull()->and(JobEngagement::escrowRefundDue()->count())->toBe(1);
    Notification::assertSentTo($this->freelancer, JobEarningReleasedNotification::class, fn ($n) => $n->amountMinor === 30000);

    recordReturn($engagement);
    expect(escrowBalance($engagement))->toBe(0)->and($this->ledger->platformAccount('revenue')->balanceMinor())->toBe(6333)->and(app(LedgerReport::class)->summary())->toMatchArray(['reconciled' => true, 'escrow' => 0]);
});

it('lets a blank amount mean pay nothing more, which the freelancer still has to answer, then returns everything left', function () {
    $engagement = fundedJob();
    cancelJob($engagement, $this->client);

    $this->actingAs($this->client)->post(route('engagements.process-partial-payment', $engagement->id), [])->assertRedirect();
    $payment = $engagement->partialPayments()->sole();
    expect($payment->amount)->toBe('0.00');

    $this->actingAs($this->freelancer)->post(route('engagements.accept-partial-payment', $payment->id));

    $engagement->refresh();
    expect($engagement->status)->toBe(EngagementStatus::Settled)->and($this->ledger->balances($this->freelancer)['available'])->toBe(0)->and(LedgerTransaction::where('type', 'escrow_extra')->count())->toBe(0)
        ->and(recordReturn($engagement))->toBeInstanceOf(EscrowRefund::class)->and($engagement->fresh()->refunded_minor)->toBe(110000);
});

it('refuses a partial payment bigger than what is left for the freelancer, or after the window closed', function () {
    $engagement = fundedJob();
    approveDeliverable($engagement, 0);
    cancelJob($engagement, $this->client);

    $this->actingAs($this->client)->post(route('engagements.process-partial-payment', $engagement->id), ['payment_amount' => 700])->assertSessionHas('error');
    expect($engagement->partialPayments()->count())->toBe(0);
    $this->post(route('engagements.process-partial-payment', $engagement->id), ['payment_amount' => 666.67])->assertSessionMissing('error');

    $other = fundedJob();
    cancelJob($other, $this->client);
    $this->travel(8)->days();
    $this->withSession(['session_rules.web.last' => now()->timestamp]);
    $this->actingAs($this->client)->post(route('engagements.process-partial-payment', $other->id), ['payment_amount' => 100])->assertSessionHas('error');
    expect($other->partialPayments()->count())->toBe(0);
});

it('settles a disputed partial payment at the amount staff decide, from escrow, and returns the rest', function () {
    $engagement = fundedJob();
    cancelJob($engagement, $this->client);
    $this->actingAs($this->client)->post(route('engagements.process-partial-payment', $engagement->id), ['payment_amount' => 100]);
    $payment = $engagement->partialPayments()->sole();

    $this->actingAs($this->freelancer);
    $dispute = app(PartialPaymentService::class)->disputePartialPayment($engagement->fresh(), $payment, 'incorrect_amount', 'I did much more work than that.');
    expect($engagement->fresh()->status)->toBe(EngagementStatus::Disputed)->and($engagement->fresh()->escrow_refund_due_at)->toBeNull();

    $admin = staffWith('Super admin');
    // More than is left for the freelancer: refused, nothing moves
    $this->actingAs($admin, 'staff')->post(route('admin.disputes.resolve', $dispute), ['resolution_notes' => 'Too much.', 'resolution_amount' => 5000])->assertSessionHas('error');
    expect($dispute->fresh()->status->value)->not->toBe('resolved')->and(escrowBalance($engagement))->toBe(110000);

    $this->post(route('admin.disputes.resolve', $dispute), ['resolution_notes' => 'Half the work was delivered.', 'resolution_amount' => 500])->assertSessionHas('success');

    $engagement->refresh();
    expect($engagement->status)->toBe(EngagementStatus::Settled)->and($this->ledger->balances($this->freelancer)['available'])->toBe(50000)->and($this->ledger->platformAccount('revenue')->balanceMinor())->toBe(5000)
        ->and(JobEngagement::escrowRefundDue()->count())->toBe(1);
    expect(LedgerTransaction::where('type', 'escrow_extra')->sole()->staff_id)->toBe($admin->id);

    recordReturn($engagement);
    expect(escrowBalance($engagement))->toBe(0)->and(app(LedgerReport::class)->summary()['reconciled'])->toBeTrue();
});

it('moves no money for a job that was never funded, whatever the cancellation does', function () {
    $legacy = jobOffer(status: EngagementStatus::Active);
    cancelJob($legacy, $this->client);

    expect($legacy->fresh()->escrow_refund_due_at)->toBeNull()->and(LedgerTransaction::count())->toBe(0)->and(JobEngagement::escrowRefundDue()->count())->toBe(0);
});

/** ---------------------------------------------------------------- staff: the queue */
it('lists what is due back, what is waiting and what was returned, and records a return', function () {
    $due = fundedJob();
    cancelJob($due, $this->freelancer, 'freelancer_initiated');
    $waiting = fundedJob();
    cancelJob($waiting, $this->client);
    $finance = staffWith('Finance');
    $this->actingAs($finance, 'staff');

    $this->get(route('admin.escrow-refunds.index'))->assertOk()->assertSee('Villa render')->assertSee('Ksh1,100.00')->assertSee('0712 345 675')->assertSee('Record return')->assertSee('Due back')->assertSee('Waiting');
    $this->get(route('admin.escrow-refunds.index', ['tab' => 'waiting']))->assertOk()->assertSee('Villa render')->assertDontSee('$dispatch(\'return-escrow', false);

    $this->post(route('admin.escrow-refunds.refund', $due), ['note' => 'Sent via the portal, ref ABC123'])->assertSessionHas('success');
    $this->post(route('admin.escrow-refunds.refund', $due))->assertSessionHas('error');
    $this->post(route('admin.escrow-refunds.refund', $waiting))->assertSessionHas('error');

    $this->get(route('admin.escrow-refunds.index', ['tab' => 'returned']))->assertOk()->assertSee('Ksh1,100.00')->assertSee($finance->name)->assertSee('Sent via the portal');
    $this->get(route('admin.escrow-refunds.index'))->assertOk()->assertSee('Nothing is due back');
});

it('keeps the queue to those who may see it, shows viewers a masked number and no button, and lets only Finance record', function () {
    $due = fundedJob();
    cancelJob($due, $this->freelancer, 'freelancer_initiated');

    foreach (['Support', 'Marketplace moderator', 'Dispute manager', 'Platform manager'] as $role) {
        $this->actingAs(staffWith($role), 'staff')->get(route('admin.escrow-refunds.index'))->assertForbidden();
    }

    $this->actingAs(staffWith('Auditor'), 'staff')->get(route('admin.escrow-refunds.index'))->assertOk()->assertSee('Villa render')->assertDontSee('0712 345 675')->assertDontSee('Record this return');
    $this->post(route('admin.escrow-refunds.refund', $due))->assertForbidden();
    expect($due->fresh()->refunded_minor)->toBe(0);

    auth('staff')->logout();
    $this->get(route('admin.escrow-refunds.index'))->assertRedirect(route('admin.login'));
});

it('puts escrow due back into the staff menu, dashboard and ledger, and links the ledger to the job', function () {
    $due = fundedJob();
    cancelJob($due, $this->freelancer, 'freelancer_initiated');
    $finance = staffWith('Finance');

    $this->actingAs($finance, 'staff')->get(route('admin.dashboard'))->assertOk()->assertSee('Escrow to return to clients')->assertSee('Escrow to return')->assertSee('Villa render');
    expect(collect(StaffMenu::for($finance))->flatMap->items->firstWhere('label', 'Escrow refunds')['badge'])->toBe(1);

    recordReturn($due, $finance);
    $this->get(route('admin.ledger.index', ['tab' => 'escrow']))->assertOk()->assertSee('Escrow for')->assertSee('Escrow funded');
    $this->get(route('admin.ledger.show', LedgerTransaction::where('type', 'escrow_refund')->sole()))->assertOk()->assertSee('Job engagement #'.$due->id)->assertSee(route('admin.engagements.show', $due), false);
});

it('shows staff a job\'s escrow on its page and in the hires list', function () {
    $due = fundedJob();
    approveDeliverable($due, 0);
    cancelJob($due, $this->freelancer, 'freelancer_initiated');
    $staff = staffWith('Super admin');

    $this->actingAs($staff, 'staff')->get(route('admin.engagements.show', $due))->assertOk()->assertSee('Escrow')->assertSee('Ksh1,100.00')->assertSee('Ksh333.33')->assertSee('Ksh733.34')->assertSee('to have back')
        ->assertSee(Payment::where('engagement_id', $due->id)->value('reference'))->assertSee('Escrow funded');
    $this->get(route('admin.engagements.index'))->assertOk()->assertSee('Refund due');
});

/** ---------------------------------------------------------------- members see where the money is */
it('tells the client and the freelancer where the escrow stands, on the workspace and the settlement page', function () {
    $engagement = fundedJob();
    approveDeliverable($engagement, 0);
    cancelJob($engagement, $this->client);

    $this->actingAs($this->client)->get(route('engagements.show-cancelled', $engagement->id))->assertOk()->assertSee('Escrow')->assertSee('Released to the freelancer')->assertSee('You can still pay for work that was not approved until')
        ->assertSee('Already paid out for approved work')->assertSee('Settle and return the rest')->assertSee('Leave blank to pay nothing more');
    $this->actingAs($this->freelancer)->get(route('engagements.show-cancelled', $engagement->id))->assertOk()->assertSee('Paid to you')->assertSee('The client has until')->assertDontSee('Settle and return the rest');

    $this->travel(8)->days();
    $this->withSession(['session_rules.web.last' => now()->timestamp]);
    $this->actingAs($this->client)->get(route('engagements.show-cancelled', $engagement->id))->assertOk()->assertSee('is being returned to you');
    $this->actingAs($this->freelancer)->get(route('engagements.show-cancelled', $engagement->id))->assertOk()->assertSee('is being returned to the client');

    recordReturn($engagement);
    $this->actingAs($this->client)->get(route('engagements.show-cancelled', $engagement->id))->assertOk()->assertSee('Returned to you');
});

/** ---------------------------------------------------------------- step c: the freelancer's earnings */
function earningsLinks(User $user): array
{
    return collect(SidebarMenu::for($user))->mapWithKeys(fn ($group) => [$group['label'] => collect($group['items'])->pluck('label')->all()])->all();
}

it('gives a freelancer an Earnings link once a job of theirs has been funded, under Projects, and not before', function () {
    $engagement = awaitingFunding();
    expect(earningsLinks($this->freelancer)['Projects'])->not->toContain('Earnings')->and($this->freelancer->hasJobEarnings())->toBeFalse();

    $payments = app(PaymentService::class);
    $payments->refresh($payments->startEscrow($this->client, $engagement, '0712345675'));

    expect($this->freelancer->hasJobEarnings())->toBeTrue()->and(earningsLinks($this->freelancer)['Projects'])->toContain('Earnings')->and(earningsLinks($this->freelancer)['Models'] ?? [])->not->toContain('Earnings');
    // The client paid but earns nothing from it
    expect(earningsLinks($this->client)['Projects'])->not->toContain('Earnings');
});

it('keeps a seller\'s Earnings under Selling, and crumbs it to the right group for each', function () {
    $seller = User::factory()->create();
    SellerProfile::factory()->approved()->create(['user_id' => $seller->id]);
    $engagement = fundedJob();

    expect(earningsLinks($seller)['Models'])->toContain('Earnings')->and(earningsLinks($seller)['Projects'])->not->toContain('Earnings');

    $this->actingAs($this->freelancer)->get(route('earnings.index'))->assertOk();
    expect(SidebarMenu::breadcrumb())->toBe([['label' => 'Projects', 'url' => null], ['label' => 'Earnings', 'url' => null]]);
    $this->actingAs($seller)->get(route('earnings.index'))->assertOk();
    expect(SidebarMenu::breadcrumb())->toBe([['label' => 'Models', 'url' => null], ['label' => 'Earnings', 'url' => null]]);
});

it('shows a freelancer what jobs have paid them, in their balance and in the total, ready to withdraw', function () {
    $engagement = fundedJob();
    approveDeliverable($engagement, 0);
    approveDeliverable($engagement, 1);

    $page = $this->actingAs($this->freelancer)->get(route('earnings.index'))->assertOk();
    $page->assertSee('Job payments')->assertSee('Villa render')->assertSee('2 of 3 deliverables approved')->assertSee('Ksh333.33')->assertSee('Ksh333.34')->assertSee('Ksh666.67')
        ->assertSee(route('engagements.show', $engagement), false)->assertSee('Ready to take out')->assertDontSee('Your sales')->assertDontSee('No sales yet');
    expect(app(EarningsService::class)->summary($this->freelancer))->toMatchArray(['available' => 66667, 'pending' => 0, 'earned' => 66667]);
});

it('lets a freelancer withdraw job earnings from their page', function () {
    $engagement = fundedJob(['agreed' => 1500, 'fee' => 150]);
    foreach ([0, 1, 2] as $i) {
        approveDeliverable($engagement, $i);
    }
    expect($this->ledger->balances($this->freelancer)['available'])->toBe(135000);

    $this->actingAs($this->freelancer)->get(route('earnings.index'))->assertOk()->assertSee('Ask to withdraw');
    $this->post(route('earnings.withdraw'), ['amount' => 1000, 'phone' => '0712345675'])->assertSessionHas('success');

    expect(Payout::sole()->amount_minor)->toBe(100000)->and($this->ledger->balances($this->freelancer)['available'])->toBe(35000);
});

it('lists a settlement of a cancelled job as such, next to the approvals', function () {
    $engagement = fundedJob();
    approveDeliverable($engagement, 0);
    cancelJob($engagement, $this->client);
    $this->actingAs($this->client)->post(route('engagements.process-partial-payment', $engagement->id), ['payment_amount' => 300]);
    $this->actingAs($this->freelancer)->post(route('engagements.accept-partial-payment', $engagement->partialPayments()->sole()->id));

    $this->get(route('earnings.index'))->assertOk()->assertSee('Settlement of a cancelled job')->assertSee('1 of 3 deliverables approved')->assertSee('Ksh300.00')->assertSee('Ksh633.33');
});

it('shows a seller who is also paid for jobs both their sales and their job payments, counting both in the total', function () {
    $seller = $this->freelancer;
    SellerProfile::factory()->approved()->create(['user_id' => $seller->id]);
    $product = Product::factory()->published()->create(['user_id' => $seller->id, 'title' => 'Oak armchair', 'price_minor' => 120000]);
    $buyer = User::factory()->create();
    $payments = app(PaymentService::class);
    $payments->refresh($payments->startEscrow($this->client, awaitingFunding(), '0712345675'));
    $payments->refresh($payments->start($buyer, $product, LicenceTier::Standard, '0712345676'));
    $engagement = JobEngagement::where('escrow_minor', '>', 0)->first();
    approveDeliverable($engagement, 0);

    $this->actingAs($seller)->get(route('earnings.index'))->assertOk()->assertSee('Your sales')->assertSee('Oak armchair')->assertSee('Job payments')->assertSee('Villa render');
    // 1,020.00 from the sale (held) and 333.33 from the job: the sale is not counted twice
    expect(app(EarningsService::class)->summary($seller))->toMatchArray(['earned' => 102000 + 33333, 'available' => 33333, 'pending' => 102000]);
});
