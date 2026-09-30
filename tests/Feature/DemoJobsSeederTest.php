<?php

use App\Models\JobApplication;
use App\Models\ModelJob;
use App\Models\User;
use Database\Seeders\DemoJobsSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/*
 * The demo seeder feeds the browse and apply pages with open projects and real photos.
 * Photos are faked here; the point is what it creates, that it is repeatable, and that it degrades without a network.
 */

beforeEach(function () {
    Storage::fake('public');
});

it('creates open demo projects with images and applicants', function () {
    Http::fake(['picsum.photos/*' => Http::response('fake-jpeg-bytes', 200, ['Content-Type' => 'image/jpeg'])]);

    $this->seed(DemoJobsSeeder::class);

    $jobs = ModelJob::where('slug', 'like', '%-demo')->get();

    expect($jobs)->toHaveCount(12)
        ->and($jobs->every(fn (ModelJob $job) => $job->isOpenForApplications()))->toBeTrue()
        ->and($jobs->every(fn (ModelJob $job) => $job->images !== null && Storage::disk('public')->exists($job->images)))->toBeTrue()
        ->and($jobs->filter(fn (ModelJob $job) => $job->jobImages()->exists())->count())->toBeGreaterThan(8)
        ->and($jobs->every(fn (ModelJob $job) => $job->skills !== [] && $job->software !== []))->toBeTrue()
        ->and(User::where('email', 'like', '%@demo.test')->count())->toBe(7)
        ->and(JobApplication::count())->toBeGreaterThan(0)
        ->and(ModelJob::where('applicants_count', '>', 0)->count())->toBeGreaterThan(0);
});

it('is safe to run twice', function () {
    Http::fake(['picsum.photos/*' => Http::response('fake-jpeg-bytes', 200, ['Content-Type' => 'image/jpeg'])]);

    $this->seed(DemoJobsSeeder::class);
    $jobs = ModelJob::count();
    $users = User::count();
    $applications = JobApplication::count();
    $downloads = Http::recorded()->count();

    $this->seed(DemoJobsSeeder::class);

    expect(ModelJob::count())->toBe($jobs)
        ->and(User::count())->toBe($users)
        ->and(JobApplication::count())->toBe($applications)
        ->and(Http::recorded()->count())->toBe($downloads); // photos already on disk are not downloaded again
});

it('gives up on photos quickly when there is no network, but still creates the projects', function () {
    Http::fake(['picsum.photos/*' => fn () => throw new ConnectionException('offline')]);

    $this->seed(DemoJobsSeeder::class);

    expect(ModelJob::where('slug', 'like', '%-demo')->count())->toBe(12)
        ->and(Http::recorded()->count())->toBeLessThan(5); // one failed attempt (plus retries), not one per photo
});

it('still creates the projects when photos cannot be downloaded', function () {
    Http::fake(['picsum.photos/*' => Http::response('', 503)]);

    $this->seed(DemoJobsSeeder::class);

    expect(ModelJob::where('slug', 'like', '%-demo')->count())->toBe(12)
        ->and(ModelJob::where('slug', 'like', '%-demo')->whereNotNull('images')->count())->toBe(0);
});

it('never runs in production', function () {
    $this->app['env'] = 'production';

    // Called directly: db:seed itself would stop to ask for confirmation in production.
    app(DemoJobsSeeder::class)->run();

    expect(ModelJob::count())->toBe(0);
});
