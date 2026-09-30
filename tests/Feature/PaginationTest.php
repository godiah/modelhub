<?php

use App\Enums\ApplicationStatus;
use App\Enums\EngagementStatus;
use App\Models\JobApplication;
use App\Models\JobEngagement;
use App\Models\ModelJob;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

/*
 * One pagination design app-wide: the browse-projects one ("Showing a–b of n", Previous / page / Next), which is
 * <x-pager>. `->links()` renders it by default, and no view may hand-roll its own.
 */

it('renders the browse-projects design for a plain ->links() call', function () {
    $paginator = new LengthAwarePaginator(collect(range(1, 10)), 25, 10, 2, ['path' => '/somewhere']);

    $html = (string) $paginator->links();

    expect($html)->toContain('Showing 11–20 of 25')
        ->toContain('Previous')
        ->toContain('Next')
        ->toContain('2 / 3')
        ->toContain('rel="prev"')
        ->toContain('rel="next"')
        ->not->toContain('class="pagination"');
});

it('renders nothing when everything fits on one page', function () {
    $paginator = new LengthAwarePaginator(collect(range(1, 3)), 3, 10, 1, ['path' => '/somewhere']);

    expect(trim((string) $paginator->links()))->toBe('');
});

it('supports the card-footer, wire:navigate and ajax variants', function () {
    $paginator = new LengthAwarePaginator(collect(range(1, 10)), 25, 10, 1, ['path' => '/somewhere']);

    $html = Blade::render('<x-pager :paginator="$paginator" :footer="true" :navigate="true" :ajax="true" />', ['paginator' => $paginator]);

    expect($html)->toContain('bg-neutral-50/60')->toContain('wire:navigate')->toContain('data-page-link');
});

it('has no view that hand-rolls its own pagination markup', function () {
    $offenders = collect(File::allFiles(resource_path('views')))
        ->reject(fn ($file) => str_contains($file->getPathname(), '/views/vendor/') || $file->getFilename() === 'pager.blade.php')
        ->filter(fn ($file) => preg_match('/previousPageUrl|nextPageUrl|->links\(\s*[\'"]|Showing :from/', File::get($file->getPathname())))
        ->map(fn ($file) => $file->getRelativePathname())
        ->values()
        ->all();

    expect($offenders)->toBe([]);
});

it('paginates the engagements list, which used to have no way past page one', function () {
    $me = User::factory()->create();
    $this->actingAs($me);

    foreach (range(1, 11) as $i) {
        $job = ModelJob::factory()->create();
        $application = JobApplication::factory()->create(['job_id' => $job->id, 'poster_id' => $job->user_id, 'applicant_id' => $me->id, 'status' => ApplicationStatus::Hired]);
        JobEngagement::create(['application_id' => $application->id, 'status' => EngagementStatus::Active, 'agreed_amount' => 100, 'service_fee' => 10, 'net_amount' => 90]);
    }

    $this->get(route('engagements.index'))
        ->assertOk()
        ->assertSee('Showing 1–10 of 11')
        ->assertSee('data-page-link', false)
        ->assertSee('page=2', false);
});
