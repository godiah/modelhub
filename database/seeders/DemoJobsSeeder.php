<?php

namespace Database\Seeders;

use App\Helpers\Applications\ApplicationCalculationHelper;
use App\Models\JobApplication;
use App\Models\JobImage;
use App\Models\ModelJob;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Local demo data for the job board: a handful of clients posting realistic 3D projects that are open for
 * applications (so the browse and apply pages have something to show), a few freelancers who have applied
 * to some of them, and real photographs attached to every project.
 *
 *   php artisan db:seed --class=DemoJobsSeeder
 *
 * Safe to re-run: clients, projects and applications are matched before creating, and images already on disk
 * are not downloaded again. Everything it creates uses @demo.test emails and a "-demo" slug suffix, so it can
 * be found (and removed) by those markers. Never runs in production, and is not part of DatabaseSeeder.
 *
 * Images come from picsum.photos — real photos, picked deterministically per seed, so the same project
 * always gets the same pictures. With no network the projects are still created, just without images.
 */
class DemoJobsSeeder extends Seeder
{
    /** Set after the first connection failure, so an offline run doesn't wait on every photo. */
    private bool $offline = false;

    /** @var list<array{name: string, email: string}> */
    private const CLIENTS = [
        ['name' => 'Wanjiru Kamau', 'email' => 'demo.client1@demo.test'],
        ['name' => 'Daniel Mutua', 'email' => 'demo.client2@demo.test'],
        ['name' => 'Fatuma Hassan', 'email' => 'demo.client3@demo.test'],
        ['name' => 'Peter Njoroge', 'email' => 'demo.client4@demo.test'],
    ];

    /** @var list<array{name: string, email: string}> */
    private const FREELANCERS = [
        ['name' => 'Achieng Odhiambo', 'email' => 'demo.freelancer1@demo.test'],
        ['name' => 'Kiprono Rotich', 'email' => 'demo.freelancer2@demo.test'],
        ['name' => 'Zawadi Mwangi', 'email' => 'demo.freelancer3@demo.test'],
    ];

    /**
     * Budgets are in the app's currency; `deadline` is days from now (null = no fixed deadline);
     * `posted` is hours ago; `images` is how many photos to attach (the first becomes the cover).
     *
     * @return list<array<string, mixed>>
     */
    private function projects(): array
    {
        return [
            [
                'title' => 'Coastal villa exterior visualisation, four still renders',
                'budget' => 85000, 'deadline' => 12, 'posted' => 3, 'images' => 4,
                'skills' => ['Rendering & Lighting', 'Reference Research & Accuracy', 'Texturing (PBR, procedural, hand-painted)'],
                'software' => ['SketchUp', 'V-Ray'],
                'description' => "We are launching a four-unit villa development in Kilifi and need **four photoreal exterior stills** for the sales brochure.\n\n- Dusk, midday, aerial and pool-side views\n- We supply CAD plans, elevations and a moodboard\n- Landscaping and vegetation should feel local, not generic\n\nPlease include two similar projects in your proposal and how many revision rounds your price covers.",
            ],
            [
                'title' => 'Interior render pack for a two-bedroom show apartment',
                'budget' => 48000, 'deadline' => 7, 'posted' => 9, 'images' => 3,
                'skills' => ['Rendering & Lighting', 'Texturing (PBR, procedural, hand-painted)', 'Detailing & Surface Finish'],
                'software' => ['Autodesk 3ds Max', 'V-Ray'],
                'description' => "Our interior design studio needs the living room, kitchen, master bedroom and one bathroom rendered from our existing 3D scenes.\n\nYou would be lighting, adding materials and finishing details, **not modelling from scratch**. Scandinavian palette, warm evening light.\n\nDelivery: 4 × 4K stills plus a 1080p 360° panorama of the living room.",
            ],
            [
                'title' => 'Low-poly medieval market stall set for a mobile game',
                'budget' => 62000, 'deadline' => 21, 'posted' => 20, 'images' => 3,
                'skills' => ['Polygonal', 'Optimization & Level of Detail (LOD)', 'Texturing (PBR, procedural, hand-painted)'],
                'software' => ['Blender', 'Unity'],
                'description' => "An indie studio is building a cosy trading game and needs a **set of 12 market stalls and props** in a hand-painted low-poly style.\n\n- Under 1,500 triangles per stall\n- Shared 1024px atlas, no PBR maps\n- Delivered as FBX with a Unity-ready prefab for each stall\n\nStyle references are attached. We would love to work with someone who has shipped mobile assets before.",
            ],
            [
                'title' => 'Product visualisation for a handmade ceramic tableware range',
                'budget' => 54000, 'deadline' => null, 'posted' => 30, 'images' => 3,
                'skills' => ['Subdivision Modelling', 'Rendering & Lighting', 'Detailing & Surface Finish'],
                'software' => ['Blender', 'Octane Render'],
                'description' => "We make stoneware dinner sets in Nairobi and want **photoreal product renders** for our online shop and packaging.\n\nTwelve items, each on a clean studio background and in a styled table scene. Glaze and texture must look like the real pieces; we will courier samples if needed.\n\nNo fixed deadline, but we would like the first three items within a month.",
            ],
            [
                'title' => 'Architectural walkthrough for a mixed-use development',
                'budget' => 240000, 'deadline' => 30, 'posted' => 52, 'images' => 4,
                'skills' => ['Rendering & Lighting', 'Animation Basics', 'Optimization & Level of Detail (LOD)'],
                'software' => ['Unreal Engine', 'Rhino 3D'],
                'description' => "A two-minute cinematic walkthrough of a mixed-use development in Westlands for investor presentations.\n\n## What we have\n- Rhino model of the building shell and podium\n- Floor plans, material schedule and landscape design\n\n## What we need\n- Camera path, lighting and materials in Unreal Engine\n- Street life: people and traffic, kept subtle\n- 4K MP4 plus the project files\n\nPlease share a showreel with at least one architectural piece.",
            ],
            [
                'title' => 'Character model and rig for a brand mascot',
                'budget' => 95000, 'deadline' => 25, 'posted' => 70, 'images' => 2,
                'skills' => ['Organic Modelling', 'Sculpting', 'Retopology', 'Rigging'],
                'software' => ['ZBrush', 'Autodesk Maya'],
                'description' => "We need our fox mascot turned into a **fully rigged 3D character** for short social animations.\n\n- Sculpt from the supplied 2D turnarounds\n- Clean retopology with good deformation around shoulders and tail\n- Facial rig with at least eight expressions\n- Turntable render for approval before rigging starts\n\nFriendly and stylised, never creepy. Two review rounds included.",
            ],
            [
                'title' => 'Office fit-out 3D model with a reusable furniture library',
                'budget' => 72000, 'deadline' => 14, 'posted' => 96, 'images' => 3,
                'skills' => ['Hard-Surface Modelling', 'Subdivision Modelling', 'UV Mapping'],
                'software' => ['SketchUp', 'Rhino 3D'],
                'description' => "We fit out offices for mid-sized companies and want a **library of around 40 furniture pieces** modelled accurately from supplier catalogues, plus a sample 600 m² floor assembled from them.\n\nEach piece needs correct dimensions, clean geometry and named materials so our designers can drop them into any scene.",
            ],
            [
                'title' => 'Hard-surface sci-fi drone model for an AR app',
                'budget' => 58000, 'deadline' => 18, 'posted' => 120, 'images' => 3,
                'skills' => ['Hard-Surface Modelling', 'Optimization & Level of Detail (LOD)', 'UV Mapping', 'File Format Management & Export'],
                'software' => ['Blender', 'Substance Painter'],
                'description' => "A companion drone for our AR shooter: **hero model plus two LODs**, PBR textured and delivered as glTF/USDZ for mobile AR.\n\nBudget of 50k triangles for the hero mesh and 8k for the lowest LOD. Panel lines, vents and a readable silhouette matter more than tiny details.",
            ],
            [
                'title' => 'Landscape and garden design 3D plan for a private residence',
                'budget' => 66000, 'deadline' => null, 'posted' => 150, 'images' => 4,
                'skills' => ['Organic Modelling', 'Rendering & Lighting', 'Reference Research & Accuracy'],
                'software' => ['SketchUp', 'Blender'],
                'description' => "A homeowner in Karen wants to see a planned garden before breaking ground. We have the survey and a planting list.\n\nThe job: a 3D plan with terraces, paths, water feature and planting, shown in **three views** (day, evening and a top-down plan). Accurate plant sizes at maturity matter to the client.",
            ],
            [
                'title' => 'Retail kiosk concept renders for a shopping-mall activation',
                'budget' => 39000, 'deadline' => 5, 'posted' => 175, 'images' => 2,
                'skills' => ['Hard-Surface Modelling', 'Rendering & Lighting'],
                'software' => ['Cinema 4D', 'Octane Render'],
                'description' => "We are pitching a pop-up kiosk to a mall operator and need **three eye-catching concept renders** by the end of next week: front, three-quarter and a busy mall-floor context shot.\n\nFast turnaround, so we would rather work with someone who can start immediately. Branding assets and dimensions will be sent on day one.",
            ],
            [
                'title' => 'Restaurant interior 360° panoramas for a booking site',
                'budget' => 44000, 'deadline' => 10, 'posted' => 200, 'images' => 3,
                'skills' => ['Rendering & Lighting', 'Texturing (PBR, procedural, hand-painted)', 'Detailing & Surface Finish'],
                'software' => ['Autodesk 3ds Max', 'V-Ray'],
                'description' => "We are refurbishing a rooftop restaurant and want guests to preview the space online. Three **equirectangular 360° panoramas** (dining room, bar, terrace) in day and night variants.\n\nThe design is finished in 3ds Max; we need someone to light, finish and render it to a high standard, then export for a web viewer.",
            ],
            [
                'title' => 'Jewellery ring collection, photoreal product renders',
                'budget' => 36000, 'deadline' => 16, 'posted' => 230, 'images' => 2,
                'skills' => ['Subdivision Modelling', 'Detailing & Surface Finish', 'Rendering & Lighting'],
                'software' => ['Rhino 3D', 'Blender'],
                'description' => "A local jeweller is launching six engagement rings and needs **macro-quality renders** for the catalogue. We have Rhino files for each design.\n\nCorrect gold and gemstone shading is essential: real refraction, believable settings and soft studio reflections. Six rings, two angles each.",
            ],
        ];
    }

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoJobsSeeder never runs in production.');

            return;
        }

        $clients = collect(self::CLIENTS)->map(fn (array $data) => $this->user($data));
        $freelancers = collect(self::FREELANCERS)->map(fn (array $data) => $this->user($data));

        foreach ($this->projects() as $index => $project) {
            $client = $clients[$index % $clients->count()];
            $job = $this->project($client, $project);

            $this->attachImages($job, $project['images']);
            $this->applicants($job, $client, $freelancers, $index);
        }

        $this->backfillImages();

        $this->command?->info(count($this->projects()).' demo projects ready ('.ModelJob::whereNotNull('images')->count().' projects have images).');
    }

    /** @param array{name: string, email: string} $data */
    private function user(array $data): User
    {
        return User::firstOrCreate(['email' => $data['email']], [
            'name' => $data['name'],
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $project */
    private function project(User $client, array $project): ModelJob
    {
        $job = ModelJob::updateOrCreate(
            ['slug' => Str::slug($project['title']).'-demo'],
            [
                'user_id' => $client->id,
                'title' => $project['title'],
                'description' => $project['description'],
                'skills' => $project['skills'],
                'software' => $project['software'],
                'budget' => $project['budget'],
                'no_deadline' => $project['deadline'] === null,
                'deadline' => $project['deadline'] === null ? null : now()->addDays($project['deadline'])->toDateString(),
                'is_active' => true,
                'is_archived' => false,
            ],
        );

        // Spread the postings over the last week or so, newest first, for a believable "posted 3 hours ago" feed.
        $job->forceFill(['created_at' => now()->subHours($project['posted'])])->saveQuietly();

        return $job;
    }

    /** One cover image plus extras, downloaded once and kept in the public disk like real uploads. */
    private function attachImages(ModelJob $job, int $count): void
    {
        if ($job->images === null) {
            $cover = $this->photo($job->slug, 0, 'job_images');
            $job->forceFill(['images' => $cover])->saveQuietly();
        }

        $existing = $job->jobImages()->count();

        for ($i = $existing + 1; $i < $count; $i++) {
            if ($path = $this->photo($job->slug, $i, 'job_additional_images')) {
                JobImage::create(['model_job_id' => $job->id, 'image_path' => $path]);
            }
        }
    }

    /** A few applications from the demo freelancers, so posted projects have real applicants. */
    private function applicants(ModelJob $job, User $client, $freelancers, int $index): void
    {
        if ($index % 3 === 2) {
            return;
        }

        foreach ($freelancers->take(($index % 3) + 1) as $offset => $freelancer) {
            $offer = round($job->budget * (0.85 + 0.05 * $offset), 2);

            JobApplication::firstOrCreate(
                ['job_id' => $job->id, 'applicant_id' => $freelancer->id],
                ApplicationCalculationHelper::calculateAmounts($offer) + [
                    'poster_id' => $client->id,
                    'proposal' => "Hi {$client->name}, I have delivered similar work and can start this week. I would share two relevant examples and a clear plan for revisions once we agree the scope.",
                    'portfolio' => [],
                    'terms_accepted' => true,
                    'status' => 'submitted',
                ],
            );
        }
    }

    /** Give every project that still has no cover (from earlier seeding or hand-made data) a real photo. */
    private function backfillImages(): void
    {
        ModelJob::whereNull('images')->orWhere('images', '')->each(function (ModelJob $job) {
            if ($cover = $this->photo($job->slug, 0, 'job_images')) {
                $job->forceFill(['images' => $cover])->saveQuietly();
            }

            if ($job->jobImages()->doesntExist()) {
                foreach ([1, 2] as $i) {
                    if ($path = $this->photo($job->slug, $i, 'job_additional_images')) {
                        JobImage::create(['model_job_id' => $job->id, 'image_path' => $path]);
                    }
                }
            }
        });
    }

    /**
     * Download (or reuse) one real photo for a project and return its path on the public disk,
     * or null when there is no network. The same slug and index always give the same picture.
     */
    private function photo(string $slug, int $index, string $directory): ?string
    {
        $path = "{$directory}/demo-{$slug}-{$index}.jpg";

        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        if ($this->offline) {
            return null;
        }

        try {
            $response = Http::timeout(20)
                ->retry(2, 500, fn (\Throwable $e) => $e instanceof ConnectionException, throw: false)
                ->get('https://picsum.photos/seed/'.urlencode("{$slug}-{$index}").'/1200/800');
        } catch (ConnectionException) {
            $this->offline = true;
            $this->command?->warn('No network: demo projects are created without photos.');

            return null;
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful() || ! str_starts_with((string) $response->header('Content-Type'), 'image/')) {
            return null;
        }

        Storage::disk('public')->put($path, $response->body());

        return $path;
    }
}
