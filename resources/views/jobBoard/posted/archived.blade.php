<x-app-layout crumb="Archived">
    <section>
        <div class="container mx-auto max-w-7xl px-4 py-8">
            @if ($archivedJobs->isEmpty())
                <x-card class="flex flex-col items-center justify-center min-h-[300px] p-12">
                    <div class="relative">
                        <div class="absolute inset-0 bg-primary/10 rounded-full blur-3xl animate-pulse"></div>
                        <x-icon name="archive-box" class="relative h-24 w-24 text-primary mb-6" stroke-width="1.5" />
                    </div>
                    <h3 class="text-xl font-tertiary font-semibold text-neutral-800 mb-3">No Archived Jobs</h3>
                    <p class="text-neutral-600 font-main mb-8 text-center max-w-sm">
                        You haven't archived any jobs yet. Archived jobs will appear here when you close them.
                    </p>
                    <x-button size="lg" class="text-sm shadow-sm" href="{{ route('my-jobs.index') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                        </svg>
                        View Active Jobs
                    </x-button>
                </x-card>
            @else
                <div class="grid gap-6">
                    @foreach ($archivedJobs as $job)
                        <x-card clip class="hover:shadow-lg transition-all duration-300 group" x-data="{ showingRestore: false }">
                            <div class="p-6">
                                <div class="flex justify-between items-start">
                                    <div class="flex-grow">
                                        <div class="flex items-center">
                                            <h3
                                                class="text-xl font-semibold font-tertiary text-neutral-800 group-hover:text-primary transition-colors">
                                                {{ $job->title }}
                                            </h3>
                                            <span
                                                class="ml-3 px-3 py-1 text-xs font-medium font-main rounded-full bg-neutral-100 text-neutral-700">
                                                Archived
                                            </span>
                                        </div>

                                        <div
                                            class="mt-3 flex flex-wrap items-center text-sm text-neutral-500 gap-x-4 font-secondary">
                                            <div class="flex items-center">
                                                <x-icon name="calendar" class="h-4 w-4 mr-2 text-neutral-400" />
                                                @if ($job->no_deadline)
                                                    <span>No deadline</span>
                                                @else
                                                    <span>Deadline: <x-date :date="$job->deadline" format="M d, Y" /></span>
                                                @endif
                                            </div>
                                            <div class="flex items-center">
                                                <x-icon name="coins" class="h-4 w-4 mr-2 text-neutral-400" fill="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                <span>Budget: <x-money :amount="$job->budget" /></span>
                                            </div>
                                            <div class="flex items-center">
                                                <x-icon name="users" class="h-4 w-4 mr-2 text-neutral-400" />
                                                <span>{{ $job->applicants_count }}
                                                    {{ Str::plural('application', $job->applicants_count) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Job Description -->
                                <div x-data="{ expanded: false, shouldShowMore: false }" x-init="$nextTick(() => {
                                    const el = $refs.content;
                                    shouldShowMore = el.scrollHeight > (window.innerWidth < 768 ? 80 : 40); // Approximate 5rem/2.5rem in pixels
                                })"
                                    class="mt-4 bg-neutral-50 rounded-lg p-4 border border-neutral-100">

                                    <!-- Markdown content div with reference -->
                                    <div x-ref="content"
                                        class="prose prose-sm max-w-none text-neutral-700 text-sm font-main transition-all duration-300 overflow-hidden
                                        [&>ul]:list-disc [&>ul]:pl-5 [&>ul]:mb-1 
                                        [&>ol]:list-decimal [&>ol]:pl-5 [&>ol]:mb-1
                                        [&>blockquote]:border-l-4 [&>blockquote]:border-neutral-200 [&>blockquote]:pl-4 [&>blockquote]:italic [&>blockquote]:my-2
                                        [&>h1]:text-lg [&>h1]:font-bold [&>h1]:mb-2 [&>h1]:mt-3
                                        [&>h2]:text-base [&>h2]:font-bold [&>h2]:mb-1.5 [&>h2]:mt-2.5
                                        [&>h3]:text-sm [&>h3]:font-bold [&>h3]:mb-1 [&>h3]:mt-2
                                        [&>h4,&>h5,&>h6]:text-sm [&>h4,&>h5,&>h6]:font-semibold [&>h4,&>h5,&>h6]:mb-1 [&>h4,&>h5,&>h6]:mt-2
                                        [&>p]:mb-1"
                                        :class="expanded ? 'max-h-none' : 'max-h-[5rem] md:max-h-[2.5rem]'">
                                        {!! Str::markdown($job->description) !!}
                                    </div>

                                    <!-- Toggle button that only shows when needed -->
                                    <button x-show="shouldShowMore" x-on:click="expanded = !expanded"
                                        class="mt-2 text-secondary text-sm font-medium font-main hover:text-primary transition"
                                        x-text="expanded ? 'Show less' : 'Read more'"></button>
                                </div>

                                <!-- Skills and Software -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                                    <div>
                                        <h4 class="text-sm font-medium text-neutral-700 flex items-center font-main">
                                            <x-icon name="light-bulb" class="h-4 w-4 mr-2 text-primary" />
                                            Skills Required
                                        </h4>
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach ($job->skills as $skill)
                                                <span
                                                    class="px-2.5 py-1 text-xs font-medium font-secondary rounded-full bg-blue-50 text-primary border border-blue-100">
                                                    {{ $skill }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-neutral-700 flex items-center font-main">
                                            <x-icon name="cpu-chip" class="h-4 w-4 mr-2 text-secondary" />
                                            Software Required
                                        </h4>
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach ($job->software as $software)
                                                <span
                                                    class="px-2.5 py-1 text-xs font-medium font-secondary rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                                                    {{ $software }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="mt-6 pt-6 border-t border-neutral-100 flex flex-wrap justify-end gap-3">
                                    <a href="{{ route('my-jobs.archived.show', $job) }}"
                                        class="inline-flex items-center px-4 py-2 bg-neutral-100 text-neutral-700 rounded-lg hover:bg-neutral-200 transition-colors duration-200 font-main text-sm font-medium">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        View Details
                                    </a>
                                    <x-button variant="secondary" class="text-sm" @click="showingRestore = true">
                                        <x-icon name="arrow-path" class="h-4 w-4 mr-2" />
                                        Restore Job
                                    </x-button>
                                </div>
                            </div>

                            <!-- Restore Confirmation Modal -->
                            <x-confirm-dialog bind="showingRestore" title="Restore Job" icon="arrow-path" tone="success"
                                confirm-label="Restore" method="PATCH"
                                :action="route('my-jobs.archived.restore', $job)"
                                message="Are you sure you want to restore this job? This will make the job active again." />
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
