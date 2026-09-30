<div class="bg-white rounded-2xl shadow-lg border border-neutral-100 overflow-hidden">
    <!-- Header Section -->
    <div class="relative bg-gradient-to-r from-primary to-primary/90 px-8 py-6">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 400 200" fill="currentColor">
                <defs>
                    <pattern id="dots" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                        <circle cx="2" cy="2" r="1" fill="currentColor" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#dots)" />
            </svg>
        </div>

        <!-- Header Content -->
        <div class="relative flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center space-x-3 mb-2">
                    <x-icon name="briefcase" class="w-6 h-6 text-white" />
                    <h2 class="text-xl font-bold text-white font-main">Job Details</h2>
                </div>
                <h3 class="text-2xl font-bold text-white font-main leading-tight">
                    {{ $engagement->application->job->title }}
                </h3>
            </div>

            <!-- Status Badge -->
            <div class="flex-shrink-0">
                @php
                    $statusClasses = $engagement->getStatusClasses();
                @endphp
                <div class="bg-white/20 backdrop-blur-sm rounded-full p-1">
                    <span
                        class="inline-flex items-center px-4 py-2 text-sm font-semibold font-secondary rounded-full bg-white shadow-sm {{ $statusClasses['text'] }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            {!! $engagement->statusIconPath !!}
                        </svg>
                        {{ $engagement->statusLabel }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Job Description Section -->
    <div class="px-8 py-6">
        <div x-data="{ expanded: false, shouldShowMore: false }" x-init="$nextTick(() => {
            const el = $refs.content;
            shouldShowMore = el.scrollHeight > (window.innerWidth < 768 ? 120 : 80);
        })"
            class="bg-gradient-to-br from-neutral-50 to-neutral-100/50 rounded-xl p-6 border border-neutral-200 relative overflow-hidden">

            <!-- Decorative Elements -->
            <div class="absolute top-0 right-0 w-24 h-24 opacity-5">
                <svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full text-primary">
                    <path
                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                </svg>
            </div>

            <div class="relative">
                <div class="flex items-center mb-4">
                    <div class="w-1 h-6 bg-gradient-to-b from-secondary to-secondary/60 rounded-full mr-3">
                    </div>
                    <h4 class="text-lg font-semibold text-neutral-800 font-main">Project Description
                    </h4>
                </div>

                <div x-ref="content"
                    class="prose prose-sm max-w-none text-neutral-700 font-main transition-all duration-300 overflow-hidden text-sm
                            [&>ul]:list-disc [&>ul]:pl-6 [&>ul]:mb-3 [&>ul]:space-y-1
                            [&>ol]:list-decimal [&>ol]:pl-6 [&>ol]:mb-3 [&>ol]:space-y-1
                            [&>blockquote]:border-l-4 [&>blockquote]:border-secondary [&>blockquote]:pl-4 [&>blockquote]:italic [&>blockquote]:my-4 [&>blockquote]:bg-secondary/5 [&>blockquote]:py-2 [&>blockquote]:rounded-r
                            [&>h1]:text-xl [&>h1]:font-bold [&>h1]:mb-3 [&>h1]:mt-4 [&>h1]:text-primary
                            [&>h2]:text-lg [&>h2]:font-bold [&>h2]:mb-2 [&>h2]:mt-3 [&>h2]:text-primary
                            [&>h3]:text-base [&>h3]:font-semibold [&>h3]:mb-2 [&>h3]:mt-3 [&>h3]:text-neutral-800
                            [&>h4,&>h5,&>h6]:text-sm [&>h4,&>h5,&>h6]:font-semibold [&>h4,&>h5,&>h6]:mb-1 [&>h4,&>h5,&>h6]:mt-2 [&>h4,&>h5,&>h6]:text-neutral-700
                            [&>p]:mb-3 [&>p]:leading-relaxed"
                    :class="expanded ? 'max-h-none' : 'max-h-[7.5rem] md:max-h-[5rem]'">
                    {!! Str::markdown($engagement->application->job->description) !!}
                </div>

                <button x-show="shouldShowMore" x-on:click="expanded = !expanded"
                    class="mt-4 inline-flex items-center px-4 py-2 text-sm font-medium text-secondary hover:text-primary transition-all duration-200 bg-white rounded-lg shadow-sm border border-neutral-200 hover:border-secondary/30 hover:shadow-md"
                    x-text="expanded ? 'Show less' : 'Read more'">
                </button>
            </div>
        </div>
    </div>

    <!-- Skills & Software Section -->
    <div class="px-8 pb-6 bg-gradient-to-r from-neutral-50/50 to-transparent">
        <div class="flex items-center mb-4">
            <x-icon name="light-bulb" class="w-5 h-5 text-secondary mr-2" />
            <h4 class="text-lg font-semibold text-neutral-800 font-main">Required Skills & Software
            </h4>
        </div>

        <div class="space-y-4">
            <!-- Skills -->
            <div>
                <span class="block text-sm font-medium text-neutral-600 font-secondary mb-2">Skills</span>
                <div class="flex flex-wrap gap-2">
                    @foreach ($engagement->application->job->skills as $skill)
                        <span
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium font-secondary rounded-full bg-gradient-to-r from-primary/10 to-primary/5 text-primary border border-primary/20 hover:border-primary/40 transition-colors">
                            <svg class="w-3 h-3 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            {{ $skill }}
                        </span>
                    @endforeach
                </div>
            </div>

            <!-- Software -->
            <div>
                <span class="block text-sm font-medium text-neutral-600 font-secondary mb-2">Software</span>
                <div class="flex flex-wrap gap-2">
                    @foreach ($engagement->application->job->software as $software)
                        <span
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium font-secondary rounded-full bg-gradient-to-r from-secondary/10 to-secondary/5 text-secondary border border-secondary/20 hover:border-secondary/40 transition-colors">
                            <svg class="w-3 h-3 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 01-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 13.586V12a1 1 0 011-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            {{ $software }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Project Info Grid -->
    <div class="px-8 py-6 border-t border-neutral-100">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Budget -->
            <div class="bg-gradient-to-br from-accent/5 to-accent/10 rounded-xl p-4 border border-accent/20">
                <div class="flex items-center mb-2">
                    <div class="w-8 h-8 bg-accent/20 rounded-lg flex items-center justify-center mr-3">
                        <x-icon name="coins" class="w-4 h-4 text-accent" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </div>
                    <span class="text-sm font-medium text-neutral-600 font-secondary">Budget</span>
                </div>
                <p class="text-lg font-bold text-accent font-main">
                    Ksh {{ number_format($engagement->application->job->budget, 2) }}
                </p>
            </div>

            <!-- Posted Date -->
            <div class="bg-gradient-to-br from-primary/5 to-primary/10 rounded-xl p-4 border border-primary/20">
                <div class="flex items-center mb-2">
                    <div class="w-8 h-8 bg-primary/20 rounded-lg flex items-center justify-center mr-3">
                        <x-icon name="calendar" class="w-4 h-4 text-primary" />
                    </div>
                    <span class="text-sm font-medium text-neutral-600 font-secondary">Posted</span>
                </div>
                <p class="text-lg font-bold text-primary font-main">
                    {{ $engagement->application->job->created_at->format('M j, Y') }}
                </p>
            </div>

            <!-- Deadline -->
            <div class="bg-gradient-to-br from-secondary/5 to-secondary/10 rounded-xl p-4 border border-secondary/20">
                <div class="flex items-center mb-2">
                    <div class="w-8 h-8 bg-secondary/20 rounded-lg flex items-center justify-center mr-3">
                        <x-icon name="clock" class="w-4 h-4 text-secondary" />
                    </div>
                    <span class="text-sm font-medium text-neutral-600 font-secondary">Deadline</span>
                </div>
                <p class="text-lg font-bold text-secondary font-main">
                    {{ $engagement->application->job->deadline ? $engagement->application->job->deadline->format('M j, Y') : 'Not specified' }}
                </p>
            </div>
        </div>
    </div>
</div>
