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
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" />
                    </svg>
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
            <svg class="w-5 h-5 text-secondary mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
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
                        <svg class="w-4 h-4 text-accent" fill="#f59e0b" stroke="currentColor" viewBox="0 0 512 512">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M512 80c0 18-14.3 34.6-38.4 48c-29.1 16.1-72.5 27.5-122.3 30.9c-3.7-1.8-7.4-3.5-11.3-5C300.6 137.4 248.2 128 192 128c-8.3 0-16.4 .2-24.5 .6l-1.1-.6C142.3 114.6 128 98 128 80c0-44.2 86-80 192-80S512 35.8 512 80zM160.7 161.1c10.2-.7 20.7-1.1 31.3-1.1c62.2 0 117.4 12.3 152.5 31.4C369.3 204.9 384 221.7 384 240c0 4-.7 7.9-2.1 11.7c-4.6 13.2-17 25.3-35 35.5c0 0 0 0 0 0c-.1 .1-.3 .1-.4 .2c0 0 0 0 0 0s0 0 0 0c-.3 .2-.6 .3-.9 .5c-35 19.4-90.8 32-153.6 32c-59.6 0-112.9-11.3-148.2-29.1c-1.9-.9-3.7-1.9-5.5-2.9C14.3 274.6 0 258 0 240c0-34.8 53.4-64.5 128-75.4c10.5-1.5 21.4-2.7 32.7-3.5zM416 240c0-21.9-10.6-39.9-24.1-53.4c28.3-4.4 54.2-11.4 76.2-20.5c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 19.3-16.5 37.1-43.8 50.9c-14.6 7.4-32.4 13.7-52.4 18.5c.1-1.8 .2-3.5 .2-5.3zm-32 96c0 18-14.3 34.6-38.4 48c-1.8 1-3.6 1.9-5.5 2.9C304.9 404.7 251.6 416 192 416c-62.8 0-118.6-12.6-153.6-32C14.3 370.6 0 354 0 336l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 342.6 135.8 352 192 352s108.6-9.4 148.1-25.9c7.8-3.2 15.3-6.9 22.4-10.9c6.1-3.4 11.8-7.2 17.2-11.2c1.5-1.1 2.9-2.3 4.3-3.4l0 3.4 0 5.7 0 26.3zm32 0l0-32 0-25.9c19-4.2 36.5-9.5 52.1-16c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 10.5-5 21-14.9 30.9c-16.3 16.3-45 29.7-81.3 38.4c.1-1.7 .2-3.5 .2-5.3zM192 448c56.2 0 108.6-9.4 148.1-25.9c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 44.2-86 80-192 80S0 476.2 0 432l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 438.6 135.8 448 192 448z" />
                        </svg>
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
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
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
                        <svg class="w-4 h-4 text-secondary" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
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
