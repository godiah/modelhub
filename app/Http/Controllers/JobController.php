<?php

namespace App\Http\Controllers;

use App\Mail\JobPostedMail;
use App\Models\ModelJob;
use App\Models\Skill;
use App\Models\Software;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JobController extends Controller
{
    /**
     * Job Board Home Page
     */
    public function index()
    {
        return view('jobBoard.jobs.index');
    }

    /**
     * Job Board New Job Page
     */
    public function new()
    {
        $skills = Skill::where('is_active', true)->get();
        $software = Software::where('is_active', true)->get();

        return view('jobBoard.jobs.new', [
            'skills' => $skills,
            'software' => $software
        ]);
    }

    /**
     * Store a new job
     */
    public function store(Request $request)
    {
        // Validate input
        $validatedData = $request->validate([
            'title'       => 'required|string|max:255|unique:model_jobs,title',
            'description' => 'required|string',
            'skills'      => 'sometimes|array',
            'software'    => 'sometimes|array',
            'image'       => 'sometimes|image|max:5120',
            'additional_images' => 'sometimes|array',
            'additional_images.*' => 'image|max:5120', // Validate each additional image
            'deadline'    => 'nullable|date|after:today',
            'no_deadline' => 'sometimes|boolean',
            'budget'      => 'required|numeric|min:0'
        ], [
            'deadline.after' => 'The deadline must be a future date.',
            'image.image'    => 'Uploaded file must be an image.',
            'image.max'      => 'Image size must not exceed 5MB.',
            'additional_images.*.image' => 'Each additional file must be an image.',
            'additional_images.*.max'   => 'Each additional image must not exceed 5MB.'
        ]);

        // If validation passes, proceed with storing the main image
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('job_images', 'public');
            $validatedData['image'] = $path;
        }

        // Generate a unique slug
        $slug = $this->generateUniqueSlug($validatedData['title']);

        $job = DB::transaction(function () use ($request, $validatedData, $slug) {
            // Create the job associated with the authenticated user
            $job = Auth::user()->jobs()->create([
                'title'       => $validatedData['title'],
                'slug'        => $slug,
                'description' => $validatedData['description'] ?? null,
                'skills'      => $validatedData['skills'] ?? [],
                'software'    => $validatedData['software'] ?? [],
                'images'       => $validatedData['image'] ?? null,
                'deadline'    => !empty($validatedData['no_deadline']) ? null : $validatedData['deadline'],
                'no_deadline' => $validatedData['no_deadline'] ?? false,
                'budget'      => $validatedData['budget'],
            ]);

            Log::info('Additional Images Count: ' . count($request->file('additional_images') ?? []));

            // Store additional images if they exist
            if ($request->hasFile('additional_images')) {
                foreach ($request->file('additional_images') as $index => $additionalImage) {
                    Log::info('Processing image: ' . $index);

                    $imagePath = $additionalImage->store('job_additional_images', 'public');

                    // Make sure model_job_id matches your foreign key
                    $job->jobImages()->create([
                        'model_job_id' => $job->id,
                        'image_path' => $imagePath
                    ]);
                }
            }

            // Send an email notification to the user
            Mail::to(Auth::user()->email)->send(new JobPostedMail($job));

            return $job;
        });

        return redirect()->route('jobs.show', $job->slug)
            ->with('success', 'Project posted successfully!');
    }

    /**
     * Return view to edit a job
     */
    public function edit(ModelJob $job)
    {
        // // Check if user has permission to edit this job
        // $this->authorize('update', $job);

        return view('jobBoard.jobs.edit', [
            'job' => $job,
            'skills' => Skill::where('is_active', true)->get(),
            'software' => Software::where('is_active', true)->get()
        ]);
    }

    /**
     * Update job details
     */
    // Update your controller method:
    public function update(Request $request, ModelJob $job)
    {
        $validatedData = $request->validate([
            'budget' => 'nullable|integer',
            'deadline' => 'nullable|date',
            'no_deadline' => 'required|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        $updateData = [
            'no_deadline' => $request->no_deadline, // Add this line
            'deadline' => $request->no_deadline ? null : $request->deadline,
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->filled('budget')) {
            $updateData['budget'] = intval($request->budget);
        }

        $job->update($updateData);

        return redirect()->route('jobs.show', $job->slug)->with('success', 'Project details updated successfully');
    }


    /**
     * Show a specific job
     */
    public function show(ModelJob $job)
    {
        $jobUrl = url("/jobs/{$job->slug}");
        return view('jobBoard.jobs.show', compact('job', 'jobUrl'));
    }

    /**
     * List projects
     */
    public function browseJobs(Request $request)
    {
        $query = ModelJob::active();

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by skills
        if ($request->has('skills') && !empty($request->skills)) {
            $skillId = $request->skills;
            $query->whereJsonContains('skills', $skillId);
        }

        // Filter by software
        if ($request->has('software') && !empty($request->software)) {
            $softwareId = $request->software;
            $query->whereJsonContains('software', $softwareId);
        }

        // Sorting
        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'budget_high':
                    $query->orderBy('budget', 'desc');
                    break;
                case 'budget_low':
                    $query->orderBy('budget', 'asc');
                    break;
                case 'deadline':
                    $query->whereNotNull('deadline')
                        ->orderBy('deadline', 'asc');
                    break;
                case 'newest':
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }
        } else {
            // Default sorting
            $query->orderBy('created_at', 'desc');
        }

        $jobs = $query->paginate(5);

        // Pass the current filters to the view for maintaining state
        $filters = [
            'search' => $request->search,
            'skills' => $request->skills,
            'software' => $request->software,
            'sort' => $request->sort ?? 'newest'
        ];

        return view('jobBoard.jobs.browse', compact('jobs', 'filters'));
    }

    /**
     * Apply for a job
     */
    public function apply(ModelJob $job)
    {
        // Ensure the job is active
        if (!$job->is_active || !$job->isActive()) {
            return $this->jobClosedError();
        }

        // Eager load the user relationship to prevent N+1 queries
        $job->loadMissing('user');

        // Cache key based on job ID and last updated timestamp
        $cacheKey = 'similar_jobs_' . $job->id . '_' . $job->updated_at->timestamp;

        // Get similar jobs from cache or calculate if not cached
        $similarJobs = Cache::remember($cacheKey, now()->addHours(24), function () use ($job) {
            // Get current job's tags
            $currentJobTags = array_merge(
                $job->skills ?? [],
                $job->software ?? []
            );

            // If no tags, just get the most recent jobs
            if (empty($currentJobTags)) {
                return ModelJob::where('id', '!=', $job->id)
                    ->active()
                    ->latest()
                    ->take(4)
                    ->get();
            } else {
                // Use database queries for efficiency instead of loading all jobs
                $similarJobsQuery = ModelJob::where('id', '!=', $job->id)
                    ->active();

                // Use raw SQL for JSON array comparison
                $similarJobsQuery->where(function ($query) use ($currentJobTags) {
                    foreach ($currentJobTags as $tag) {
                        $query->orWhereRaw("JSON_CONTAINS(skills, ?)", ['"' . $tag . '"'])
                            ->orWhereRaw("JSON_CONTAINS(software, ?)", ['"' . $tag . '"']);
                    }
                });

                // Calculate similarity score at database level and order by it
                $selectRaw = [];
                foreach ($currentJobTags as $tag) {
                    $selectRaw[] = "JSON_CONTAINS(skills, '\"" . $tag . "\"')";
                    $selectRaw[] = "JSON_CONTAINS(software, '\"" . $tag . "\"')";
                }

                $similarJobs = $similarJobsQuery
                    ->select('*')
                    ->selectRaw('(' . implode(' + ', $selectRaw) . ') as similarity_score')
                    ->orderByDesc('similarity_score')
                    ->orderByDesc('created_at')
                    ->take(4)
                    ->get();

                // If we found fewer than 4 similar jobs, supplement with recent jobs
                if ($similarJobs->count() < 4) {
                    $existingIds = $similarJobs->pluck('id')->toArray();
                    $additionalJobs = ModelJob::where('id', '!=', $job->id)
                        ->whereNotIn('id', $existingIds)
                        ->active()
                        ->latest()
                        ->take(4 - $similarJobs->count())
                        ->get();

                    $similarJobs = $similarJobs->merge($additionalJobs);
                }

                return $similarJobs;
            }
        });

        return view('jobBoard.jobs.apply', compact('job', 'similarJobs'));
    }

    // Check if project title exists
    public function checkTitle(Request $request)
    {
        $title = $request->input('title');
        $exists = ModelJob::where('title', $title)->exists();

        return response()->json(['exists' => $exists]);
    }

    // Generating unique slugs
    protected function generateUniqueSlug(string $title): string
    {
        // Convert to slug (replace spaces with dashes, convert to lowercase)
        $slug = Str::slug($title);

        // Check if the slug already exists
        $originalSlug = $slug;
        $count = 1;

        while (ModelJob::where('slug', $slug)->exists()) {
            // If slug exists, append a number
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }

    protected function jobClosedError()
    {
        return redirect()->route('jobs.browse')->with([
            'error' => 'This job is no longer accepting new applications',
            'alert' => [
                'type' => 'error',
                'title' => 'This job is no longer accepting new applications.',
                'text' => 'This job is no longer accepting new applications.',
                'icon' => 'error'
            ]
        ]);
    }
}
