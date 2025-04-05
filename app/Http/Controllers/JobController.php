<?php

namespace App\Http\Controllers;

use App\Mail\JobPostedMail;
use App\Models\ModelJob;
use App\Models\Skill;
use App\Models\Software;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        return view('jobBoard.home');
    }

    /**
     * Job Board New Job Page
     */
    public function new()
    {
        $skills = Skill::where('is_active', true)->get();
        $software = Software::where('is_active', true)->get();

        return view('jobBoard.new', [
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
            'deadline'    => 'nullable|date|after:today',
            'no_deadline' => 'sometimes|boolean',
            'budget'      => 'required|numeric|min:0'
        ], [
            'deadline.after' => 'The deadline must be a future date.',
            'image.image'    => 'Uploaded file must be an image.',
            'image.max'      => 'Image size must not exceed 5MB.'
        ]);

        // If validation passes, proceed with storing the image
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

            // Send an email notification to the user
            Mail::to(Auth::user()->email)->send(new JobPostedMail($job));

            return $job;
        });

        return redirect()->route('job.show', $job->slug)
            ->with('success', 'Project posted successfully!');
    }

    /**
     * Return view to edit a job
     */
    public function edit(ModelJob $job)
    {
        // // Check if user has permission to edit this job
        // $this->authorize('update', $job);

        return view('jobBoard.posted.edit-job', [
            'job' => $job,
            'skills' => Skill::where('is_active', true)->get(),
            'software' => Software::where('is_active', true)->get()
        ]);
    }

    /**
     * Update job details
     */
    public function update(Request $request, ModelJob $job)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255|unique:jobs,title,' . $job->id,
            'description' => 'required|string',
            'skills' => 'sometimes|array',
            'software' => 'sometimes|array',
            'image' => 'sometimes|image|max:5120',
            'deadline' => 'nullable|date|after:today',
            'no_deadline' => 'sometimes|boolean',
            'budget' => 'required|numeric|min:0'
        ]);

        // Handle image update
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($job->image) {
                Storage::disk('public')->delete($job->image);
            }
            $path = $request->file('image')->store('job_images', 'public');
            $validatedData['image'] = $path;
        } else {
            $validatedData['image'] = $job->image;
        }

        // Update job
        $job->update([
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'skills' => $validatedData['skills'] ?? [],
            'software' => $validatedData['software'] ?? [],
            'images' => $validatedData['image'],
            'deadline' => $validatedData['no_deadline'] ? null : $validatedData['deadline'],
            'no_deadline' => $validatedData['no_deadline'] ?? false,
            'budget' => $validatedData['budget']
        ]);

        return redirect()->route('jobs.show', $job)->with('success', 'Job updated successfully!');
    }

    /**
     * Show a specific job
     */
    public function show(ModelJob $job)
    {
        return view('jobBoard.show', compact('job'));
    }

    /**
     * List projects
     */
    public function browseJobs(Request $request)
    {
        $query = ModelJob::query();

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

        $jobs = $query->paginate(10);

        // Pass the current filters to the view for maintaining state
        $filters = [
            'search' => $request->search,
            'skills' => $request->skills,
            'software' => $request->software,
            'sort' => $request->sort ?? 'newest'
        ];

        return view('jobBoard.browse-jobs', compact('jobs', 'filters'));
    }

    /**
     * Apply for a job
     */
    public function apply(ModelJob $job)
    {
        // Ensure the job is active
        if (! $job->is_active) {
            abort(404, 'This job listing is no longer active');
        }

        // Eager load the user relationship to prevent N+1 queries
        $job->loadMissing('user');

        return view('jobBoard.apply-jobs', compact('job'));
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
}
