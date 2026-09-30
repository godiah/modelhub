<?php

use App\Enums\DisputeStatus;
use App\Enums\EngagementStatus;
use App\Enums\PartialPaymentStatus;
use App\Helpers\EmailCssInlinerHelper;
use App\Mail\DeliverableSubmitted;
use App\Mail\TwoFactorCode;
use App\Models\ApplicantMessage;
use App\Models\JobApplication;
use App\Models\JobCancellation;
use App\Models\JobDeliverable;
use App\Models\JobEngagement;
use App\Models\JobPartialPayment;
use App\Models\JobPaymentDispute;
use App\Models\JobReview;
use App\Models\ModelJob;
use App\Models\User;
use App\Notifications\ApplicationWithdrawnNotification;
use App\Notifications\DisputeCreatedNotification;
use App\Notifications\EngagementCancelledNotification;
use App\Notifications\EngagementResponseNotification;
use App\Notifications\HiredNotification;
use App\Notifications\JobPostedNotification;
use App\Notifications\NewApplicationMessage;
use App\Notifications\PartialPaymentProcessedNotification;
use App\Notifications\PaymentAcceptedNotification;
use App\Notifications\PaymentDisputedNotification;
use App\Notifications\ReviewSubmittedNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

/*
 * One email template for the whole app. Every email the app can send is actually sent (array transport, sync
 * queue) and its HTML checked for the shared template's markers, so a new email that skips the template, or a
 * view that goes back to hand-rolled buttons, fails here.
 */

beforeEach(function () {
    $this->client = User::factory()->create(['name' => 'Amina Otieno']);
    $this->freelancer = User::factory()->create(['name' => 'Kevin Mwangi']);

    $this->job = ModelJob::factory()->create(['user_id' => $this->client->id, 'title' => 'Hospital lobby model', 'budget' => 1500]);
    $this->application = JobApplication::factory()->hired()->create(['job_id' => $this->job->id, 'applicant_id' => $this->freelancer->id, 'poster_id' => $this->client->id]);
    $this->engagement = JobEngagement::create([
        'application_id' => $this->application->id, 'status' => EngagementStatus::Cancelled,
        'agreed_amount' => 1100, 'service_fee' => 100, 'net_amount' => 1000, 'started_at' => now()->subDays(6),
    ]);
    $this->deliverable = JobDeliverable::create([
        'engagement_id' => $this->engagement->id, 'title' => 'Massing model', 'description' => 'Details',
        'due_date' => now()->addDays(4)->toDateString(), 'status' => 'submitted',
        'submitted_at' => now(), 'submission_notes' => 'First pass attached', 'submission_files' => [['name' => 'massing.pdf', 'path' => 'x/massing.pdf']],
    ]);
    $this->cancellation = JobCancellation::create([
        'engagement_id' => $this->engagement->id, 'initiator_id' => $this->client->id, 'cancellation_type' => 'client_initiated',
        'reason_category' => 'project_scope_change', 'reason_details' => 'The brief changed completely',
    ]);
    $this->payment = JobPartialPayment::create([
        'engagement_id' => $this->engagement->id, 'amount' => 450, 'processed_by' => $this->client->id,
        'processed_at' => now()->subDay(), 'status' => PartialPaymentStatus::Pending, 'accepted_at' => now(), 'notes' => 'Paid for approved work',
    ]);
    $this->dispute = JobPaymentDispute::create([
        'cancellation_id' => $this->cancellation->id, 'disputed_by' => $this->freelancer->id, 'dispute_reason' => 'incorrect_amount',
        'dispute_details' => 'The amount ignores two approved deliverables', 'status' => DisputeStatus::Pending,
    ]);
    $this->review = JobReview::create([
        'engagement_id' => $this->engagement->id, 'reviewer_id' => $this->client->id, 'reviewee_id' => $this->freelancer->id,
        'rating' => 4, 'review' => 'Great work', 'is_public' => true,
    ]);
    $this->message = ApplicantMessage::create([
        'job_application_id' => $this->application->id, 'sender_id' => $this->client->id, 'recipient_id' => $this->freelancer->id,
        'subject' => 'Interview invite', 'message' => "Can you talk on Friday?\nBring your portfolio.",
    ]);

    Mail::mailer('array')->getSymfonyTransport()->flush();
});

/** Every email the app can send, as [label => closure that sends it]. */
function allEmails(): array
{
    $t = test();

    return [
        'hired' => fn () => $t->freelancer->notify(new HiredNotification($t->application, $t->engagement)),
        'application message' => fn () => $t->freelancer->notify(new NewApplicationMessage($t->message)),
        'application withdrawn' => fn () => $t->client->notify(new ApplicationWithdrawnNotification($t->application)),
        'offer response' => fn () => $t->client->notify(new EngagementResponseNotification($t->engagement, 'accept', 'Looking forward to it')),
        'engagement cancelled (freelancer)' => fn () => $t->freelancer->notify(new EngagementCancelledNotification($t->engagement, $t->cancellation)),
        'engagement cancelled (client)' => fn () => $t->client->notify(new EngagementCancelledNotification($t->engagement, $t->cancellation)),
        'dispute created' => fn () => $t->client->notify(new DisputeCreatedNotification($t->engagement, $t->cancellation)),
        'partial payment processed' => fn () => $t->freelancer->notify(new PartialPaymentProcessedNotification($t->engagement, $t->payment)),
        'payment accepted' => fn () => $t->client->notify(new PaymentAcceptedNotification($t->engagement, $t->payment)),
        'payment disputed' => fn () => $t->client->notify(new PaymentDisputedNotification($t->engagement, $t->payment, $t->dispute)),
        'review submitted' => fn () => $t->freelancer->notify(new ReviewSubmittedNotification($t->review)),
        'job posted' => fn () => $t->client->notify(new JobPostedNotification($t->job)),
        'deliverable submitted' => fn () => Mail::to($t->client)->send(new DeliverableSubmitted($t->deliverable)),
        'two-factor code' => fn () => Mail::to($t->client)->send(new TwoFactorCode('482913')),
        'password reset' => fn () => $t->client->notify(new ResetPassword('reset-token')),
        'email verification' => fn () => $t->client->notify(new VerifyEmail),
    ];
}

function sentEmails(): array
{
    return collect(Mail::mailer('array')->getSymfonyTransport()->messages())
        ->map(fn ($sent) => $sent->getOriginalMessage()->getHtmlBody())
        ->all();
}

it('sends every email through the one template', function (string $label) {
    allEmails()[$label]();

    $emails = sentEmails();
    expect($emails)->toHaveCount(1);

    $html = $emails[0];
    expect($html)
        ->toContain('mail-card')                       // the template's card
        ->toContain('images/brand/logo-mark.png')       // brand header
        ->toContain('#F7F3ED')                          // the paper page background
        ->toContain('© '.date('Y').' '.config('app.name'))
        ->toContain('@media only screen')              // the responsive block survived inlining
        ->not->toContain('support@yourcompany.com')
        ->not->toContain('#1E3A8A')                     // the old blue header/heading colour
        ->not->toContain('#F59E0B; color: white')       // the old hand-rolled amber button
        ->not->toContain('class="btn');                 // no legacy button class
})->with([
    'hired', 'application message', 'application withdrawn', 'offer response', 'engagement cancelled (freelancer)', 'engagement cancelled (client)',
    'dispute created', 'partial payment processed', 'payment accepted', 'payment disputed', 'review submitted', 'job posted',
    'deliverable submitted', 'two-factor code', 'password reset', 'email verification',
]);

it('gives action emails a real, styled button', function (string $label) {
    allEmails()[$label]();

    expect(sentEmails()[0])->toContain('background-color:#0D9488');
})->with([
    'hired', 'application message', 'application withdrawn', 'offer response', 'engagement cancelled (client)', 'dispute created',
    'partial payment processed', 'payment accepted', 'payment disputed', 'review submitted', 'job posted', 'deliverable submitted',
    'password reset', 'email verification',
]);

it('puts the verification code front and centre in the two-factor email', function () {
    allEmails()['two-factor code']();

    expect(sentEmails()[0])->toContain('482913')->toContain('letter-spacing:0.3em')->toContain('Your verification code');
});

it('shows the real content in the account emails', function () {
    allEmails()['password reset']();
    allEmails()['email verification']();

    [$reset, $verify] = sentEmails();
    expect($reset)->toContain('Reset your password')->toContain('reset-token')->toContain('expires in 60 minutes')
        ->and($verify)->toContain('Verify email address')->toContain('/verify-email/');
});

it('keeps a message\'s line breaks and links it to the application', function () {
    allEmails()['application message']();

    expect(sentEmails()[0])->toContain('Bring your portfolio.')->toContain('white-space:pre-line')
        ->toContain(route('applications.show', $this->job->slug));
});

it('has no email view that skips the template or hand-rolls its own button', function () {
    $offenders = collect(File::allFiles(resource_path('views/emails')))
        ->reject(fn ($file) => str_ends_with($file->getPathname(), 'layouts/master.blade.php'))
        ->filter(function ($file) {
            $source = File::get($file->getPathname());

            return ! str_contains($source, "@extends('emails.layouts.master')") || preg_match('/display:\s*inline-block|class="btn/', $source);
        })
        ->map(fn ($file) => $file->getRelativePathname())
        ->values()
        ->all();

    expect($offenders)->toBe([]);
});

it('inlines styles but leaves data-embed style blocks alone', function () {
    $html = '<html><head><style data-embed>@media (max-width:600px){.a{color:red}}</style><style>p{color:blue}</style></head><body><p class="a">Hi</p></body></html>';

    $inlined = EmailCssInlinerHelper::inline($html);

    expect($inlined)->toContain('@media (max-width:600px){.a{color:red}}')->toMatch('/<p[^>]*style="[^"]*color:\s*blue/');
});
