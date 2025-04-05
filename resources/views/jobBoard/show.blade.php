<x-app-layout>
    <div class="container mx-auto max-w-7xl">
        <!-- Breadcrumb -->
        <nav class="flex max-w-xl p-8" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-2">
                <!-- First Link -->
                <li class="inline-flex items-center">
                    <a href="{{ route('home') }}"
                        class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700">
                        <svg class="w-3 h-3 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path
                                d="M19.707 9.293l-2-2-7-7a1 1 0 00-1.414 0l-7 7-2 2a1 1 0 001.414 1.414L2 10.414V18a2 2 0 002 2h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a2 2 0 002-2v-7.586l.293.293a1 1 0 001.414-1.414z" />
                        </svg>
                        ModelHub
                    </a>
                </li>
                <!-- Second Link -->
                <li>
                    <div class="flex items-center">
                        <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M1 9l4-4-4-4" />
                        </svg>
                        <a href="{{ route('job.home') }}"
                            class="ml-1 text-sm font-medium text-gray-500 hover:text-gray-700 md:ml-2">
                            Modelling Jobs
                        </a>
                    </div>
                </li>
                <!-- Active Link -->
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M1 9l4-4-4-4" />
                        </svg>
                        <span class="ml-1 text-sm font-medium text-black md:ml-2">
                            {{ $job->title }}
                        </span>
                    </div>
                </li>
            </ol>
        </nav>

        <div class="max-w-7xl mx-auto px-4 pb-12">
            <div
                class="bg-white rounded-2xl shadow-xl overflow-hidden border border-neutral-200 transition-all duration-300 hover:shadow-2xl">
                <!-- Header Section -->
                <div class="bg-gradient-to-r from-primary to-primary/90 text-white p-6 relative overflow-hidden">
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 opacity-10">
                        <svg width="100%" height="100%" viewBox="0 0 100 100" preserveAspectRatio="none">
                            <pattern id="pattern-circles" x="0" y="0" width="20" height="20"
                                patternUnits="userSpaceOnUse" patternContentUnits="userSpaceOnUse">
                                <circle id="pattern-circle" cx="10" cy="10" r="1.5" fill="#ffffff">
                                </circle>
                            </pattern>
                            <rect x="0" y="0" width="100%" height="100%" fill="url(#pattern-circles)"></rect>
                        </svg>
                    </div>

                    <!-- Job Status Badge -->
                    <div class="relative z-10 mb-4">
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full bg-white/20 backdrop-blur-sm text-sm font-medium">
                            <span class="h-2 w-2 rounded-full bg-secondary animate-pulse mr-2"></span>
                            Active Project
                        </span>
                    </div>

                    <!-- Title & Budget -->
                    <div class="relative z-10 flex flex-wrap justify-between items-start gap-4">
                        <h1 class="text-2xl sm:text-3xl font-bold font-main">{{ $job->title }}</h1>
                        <div class="flex flex-col items-end">
                            <span class="text-xs font-tertiary uppercase tracking-wider text-neutral-100">Budget</span>
                            <span class="text-xl sm:text-2xl font-semibold font-secondary">
                                Ksh. {{ number_format($job->budget, 2) }}
                            </span>
                        </div>
                    </div>

                    <!-- Job Meta Info -->
                    <div class="relative z-10 mt-4 flex flex-wrap items-center gap-3 text-sm">
                        <span class="bg-white/15 backdrop-blur-sm text-white px-3 py-1 rounded-full flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Posted on {{ \Carbon\Carbon::parse($job->created_at)->format('F j, Y') }}
                        </span>
                        <span class="bg-white/15 backdrop-blur-sm text-white px-3 py-1 rounded-full flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            @if ($job->deadline)
                                Deadline: {{ \Carbon\Carbon::parse($job->deadline)->format('F j, Y') }}
                            @else
                                No Fixed Deadline
                            @endif
                        </span>
                    </div>
                </div>

                <!-- Main Content Section -->
                <div class="flex flex-col md:flex-row">
                    <!-- Left Column - Job Image -->
                    <div class="md:w-2/5 border-r border-neutral-200">
                        @if ($job->images)
                            <div class="aspect-w-4 aspect-h-3 overflow-hidden">
                                <img src="{{ asset('storage/' . $job->images) }}" alt="{{ $job->title }}"
                                    class="w-full h-full object-cover transition-all duration-500 hover:scale-105">
                            </div>
                        @else
                            <div class="aspect-w-4 aspect-h-3 bg-neutral-100 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-neutral-300"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        @endif

                        <!-- Quick Actions -->
                        <div class="p-4 border-t border-neutral-200 bg-neutral-50">
                            <h3 class="text-sm font-tertiary uppercase text-tertiary mb-3 tracking-wider">Quick Actions
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    class="flex items-center px-3 py-1.5 bg-white rounded-lg border border-neutral-200 text-sm text-tertiary hover:border-secondary hover:text-secondary transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                    </svg>
                                    Save
                                </button>
                                <button
                                    class="flex items-center px-3 py-1.5 bg-white rounded-lg border border-neutral-200 text-sm text-tertiary hover:border-secondary hover:text-secondary transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                    </svg>
                                    Share
                                </button>
                                <button
                                    class="flex items-center px-3 py-1.5 bg-white rounded-lg border border-neutral-200 text-sm text-tertiary hover:border-secondary hover:text-secondary transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    Contact
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Job Details -->
                    <div class="md:w-3/5 p-6">
                        <!-- Description -->
                        @if ($job->description)
                            <div class="mb-6">
                                <h2 class="text-lg font-bold text-primary mb-3 flex items-center font-tertiary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-secondary"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Job Description
                                </h2>
                                <div class="border-l-4 border-secondary/20 pl-4">
                                    <div
                                        class="text-neutral-700 leading-relaxed font-secondary prose prose-sm max-w-none">
                                        {!! Str::markdown($job->description) !!}
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Skills & Software -->
                        @php
                            $skills = is_string($job->skills) ? json_decode($job->skills, true) : $job->skills;
                            $software = is_string($job->software) ? json_decode($job->software, true) : $job->software;
                        @endphp

                        <div class="space-y-6">
                            <!-- Skills -->
                            @if (!empty($skills))
                                <div
                                    class="bg-neutral-50 rounded-xl p-4 border border-neutral-200 hover:border-secondary/30 transition-colors">
                                    <h2 class="text-lg font-bold text-primary mb-3 flex items-center font-tertiary">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-secondary"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        Skills Required
                                    </h2>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($skills as $skill)
                                            <span
                                                class="bg-secondary/10 text-secondary px-3 py-1.5 rounded-full text-sm font-medium hover:bg-secondary/20 transition-colors">
                                                {{ $skill }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Software -->
                            @if (!empty($software))
                                <div
                                    class="bg-neutral-50 rounded-xl p-4 border border-neutral-200 hover:border-primary/30 transition-colors">
                                    <h2 class="text-lg font-bold text-primary mb-3 flex items-center font-tertiary">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-secondary"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        Required Software
                                    </h2>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($software as $soft)
                                            <span
                                                class="bg-primary/10 text-primary px-3 py-1.5 rounded-full text-sm font-medium hover:bg-primary/20 transition-colors">
                                                {{ $soft }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div
                    class="bg-gradient-to-r from-neutral-50 to-neutral-100 p-5 flex flex-col sm:flex-row justify-between items-center gap-4 border-t border-neutral-200">
                    <a href="{{ route('job.home') }}"
                        class="group text-tertiary hover:text-primary font-medium flex items-center transition-all py-2 px-4 rounded-lg hover:bg-white hover:shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 mr-2 text-secondary group-hover:text-primary transition-colors"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Jobs
                    </a>

                    <div class="flex items-center gap-3">
                        {{-- <a href="#"
                            class="border border-accent text-accent hover:bg-accent/10 font-medium py-2 px-4 rounded-lg transition-all flex items-center font-tertiary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Preview
                        </a> --}}

                        <a href="{{ route('job.new') }}"
                            class="bg-accent hover:bg-accent/90 text-white font-semibold py-2 px-5 rounded-lg transition-all shadow-md hover:shadow-lg flex items-center justify-center font-tertiary focus:ring-2 focus:ring-accent/50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Create New Project
                        </a>
                    </div>
                </div>
            </div>

            <!-- Related Jobs Section -->
            <div class="mt-8 text-center">
                <div
                    class="inline-flex items-center px-4 py-2 rounded-full bg-white shadow-md text-sm text-tertiary font-medium">
                    <span class="h-2.5 w-2.5 rounded-full bg-secondary animate-pulse mr-2"></span>
                    <a href="#" class="text-secondary hover:text-primary hover:underline ml-1 font-semibold">
                        Want to see more jobs like this?
                    </a>
                </div>

                <!-- Related Jobs Section -->
                <div class="mt-12">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-primary font-tertiary flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-secondary"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Similar Projects
                        </h2>
                        <a href="#"
                            class="text-secondary hover:text-primary font-tertiary font-medium text-sm flex items-center">
                            View All
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>

                    <!-- Related Jobs Cards - Example placeholder -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Placeholder cards with skeleton loading effect -->
                        @for ($i = 0; $i < 3; $i++)
                            <div
                                class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 overflow-hidden border border-neutral-200">
                                <div class="h-32 bg-gradient-to-r from-neutral-200 to-neutral-300 animate-pulse"></div>
                                <div class="p-5">
                                    <div class="h-6 bg-neutral-200 rounded animate-pulse mb-3 w-3/4"></div>
                                    <div class="h-4 bg-neutral-200 rounded animate-pulse mb-2 w-1/2"></div>
                                    <div class="h-4 bg-neutral-200 rounded animate-pulse mb-4 w-5/6"></div>
                                    <div class="flex justify-between items-center pt-2">
                                        <div class="h-8 bg-neutral-200 rounded-full animate-pulse w-24"></div>
                                        <div class="h-8 bg-neutral-200 rounded-full animate-pulse w-20"></div>
                                    </div>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>

    </div>

    @include('partials\footer-secondary')

    {{-- Sweet Alert Script --}}
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            @if (session('success'))
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-right',
                    iconColor: 'white',
                    customClass: {
                        popup: 'colored-toast',
                    },
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                })
                Toast.fire({
                    icon: 'success',
                    title: '{{ session('success') }}'
                });
            @endif
        });
    </script>
</x-app-layout>
