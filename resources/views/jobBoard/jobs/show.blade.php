<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-tertiary font-bold text-xl text-primary leading-tight">
                    {{ __('Project') }}: <span class="text-secondary">{{ $job->title }}</span>
                </h2>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('jobs.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors duration-200 font-main text-sm font-medium shadow-sm">
                    <svg class="h-5 w-5 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path
                            d="M19.707 9.293l-2-2-7-7a1 1 0 00-1.414 0l-7 7-2 2a1 1 0 001.414 1.414L2 10.414V18a2 2 0 002 2h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a2 2 0 002-2v-7.586l.293.293a1 1 0 001.414-1.414z" />
                    </svg>
                    Home
                </a>

                <a href="{{ route('project.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary/90 transition-colors duration-200 font-main text-sm font-medium shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="h-5 w-5 mr-2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" />
                    </svg>
                    Project Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="container mx-auto max-w-7xl">
        <div class="max-w-7xl mx-auto px-4 py-14 sm:px-6 lg:px-8">
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
                    <div class="relative z-10 mb-1">
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium space-x-2 {{ $job->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            @if ($job->is_active)
                                <!-- Checkmark icon -->
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Active</span>
                            @else
                                <!-- Exclamation icon -->
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>Closed</span>
                            @endif
                        </span>
                    </div>

                    <!-- Title & Budget -->
                    <div class="relative z-10 flex flex-wrap justify-between items-start gap-4">
                        <h1 class="text-xl sm:text-2xl font-bold font-main">{{ $job->title }}</h1>
                        <div class="flex flex-col items-end">
                            <span class="text-xs font-tertiary uppercase tracking-wider text-neutral-100">Budget</span>
                            <span class="text-lg sm:text-xl font-semibold font-secondary">
                                Ksh.{{ number_format($job->budget, 2) }}
                            </span>
                        </div>
                    </div>

                    <!-- Job Meta Info -->
                    <div class="relative z-10 mt-2 flex flex-wrap items-center gap-3 text-sm">
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
                            <div class="aspect-w-16 aspect-h-10 overflow-hidden">
                                <img src="{{ asset('storage/' . $job->images) }}" alt="{{ $job->title }}"
                                    class="w-full h-full object-cover transition-all duration-500 hover:scale-105"
                                    id="mainImage">
                            </div>
                        @else
                            <div class="aspect-w-16 aspect-h-10 bg-neutral-100 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-neutral-300"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                        @endif
                        <!-- Image Gallery -->
                        @if ($job->jobImages && $job->jobImages->isNotEmpty())
                            <div class="p-4 border-t border-neutral-200">
                                <div class="grid grid-cols-5 gap-2" id="imageGallery">
                                    @if ($job->images)
                                        <div
                                            class="aspect-w-1 aspect-h-1 cursor-pointer border-2 border-secondary rounded-md overflow-hidden">
                                            <img src="{{ asset('storage/' . $job->images) }}"
                                                alt="{{ $job->title }}" class="w-full h-full object-cover"
                                                onclick="changeMainImage('{{ asset('storage/' . $job->images) }}')">
                                        </div>
                                    @endif

                                    @foreach ($job->jobImages as $image)
                                        <div
                                            class="aspect-w-1 aspect-h-1 cursor-pointer border-2 border-transparent hover:border-secondary rounded-md overflow-hidden transition-all">
                                            <img src="{{ asset('storage/' . $image->image_path) }}" alt="Job Image"
                                                class="w-full h-full object-cover"
                                                onclick="changeMainImage('{{ asset('storage/' . $image->image_path) }}')">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif


                        <!-- Quick Actions -->
                        <div class="p-4 border-t border-neutral-200 bg-neutral-50">
                            <h3 class="text-sm font-tertiary uppercase text-tertiary mb-3 tracking-wider">Quick Actions
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    class="flex items-center px-3 py-1.5 bg-white rounded-lg border border-neutral-200 text-sm text-tertiary hover:border-secondary hover:text-secondary transition-colors"
                                    onclick="copyToClipboard('{{ $jobUrl }}')" id="shareButton">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                    </svg>
                                    Share
                                </button>
                                <a href="{{ route('jobs.edit', ['job' => $job->slug]) }}"
                                    class="flex items-center px-3 py-1.5 bg-white rounded-lg border border-neutral-200 text-sm text-tertiary hover:border-secondary hover:text-secondary transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </a>
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
                                        class="text-neutral-700 [&>h1]:text-xl [&>h1]:font-tertiary [&>h1]:font-bold [&>h1]:text-primary [&>h1]:mt-6 [&>h1]:mb-4
                                        [&>h2]:text-lg [&>h2]:font-tertiary [&>h2]:font-semibold [&>h2]:text-primary/90 [&>h2]:mt-5 [&>h2]:mb-3
                                        [&>h3]:text-base [&>h3]:font-tertiary [&>h3]:font-medium [&>h3]:text-neutral-800 [&>h3]:mt-4 [&>h3]:mb-2
                                        [&>p]:text-base [&>p]:leading-relaxed [&>p]:text-neutral-700 [&>p]:mb-4 [&>p]:font-secondary
                                        [&>ul]:list-disc [&>ul]:pl-5 [&>ul]:mb-4 [&>ul]:text-neutral-700 [&>ul]:font-secondary
                                        [&>ol]:list-decimal [&>ol]:pl-5 [&>ol]:mb-4 [&>ol]:text-neutral-700 [&>ol]:font-secondary
                                        [&>li]:mb-2 [&>a]:text-secondary [&>a]:underline [&>a]:font-medium [&>p]:text-justify">
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
                                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
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
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                            fill="currentColor" class="h-5 w-5 mr-2 text-secondary">
                                            <path d="M16.5 7.5h-9v9h9v-9Z" />
                                            <path fill-rule="evenodd"
                                                d="M8.25 2.25A.75.75 0 0 1 9 3v.75h2.25V3a.75.75 0 0 1 1.5 0v.75H15V3a.75.75 0 0 1 1.5 0v.75h.75a3 3 0 0 1 3 3v.75H21A.75.75 0 0 1 21 9h-.75v2.25H21a.75.75 0 0 1 0 1.5h-.75V15H21a.75.75 0 0 1 0 1.5h-.75v.75a3 3 0 0 1-3 3h-.75V21a.75.75 0 0 1-1.5 0v-.75h-2.25V21a.75.75 0 0 1-1.5 0v-.75H9V21a.75.75 0 0 1-1.5 0v-.75h-.75a3 3 0 0 1-3-3v-.75H3A.75.75 0 0 1 3 15h.75v-2.25H3a.75.75 0 0 1 0-1.5h.75V9H3a.75.75 0 0 1 0-1.5h.75v-.75a3 3 0 0 1 3-3h.75V3a.75.75 0 0 1 .75-.75ZM6 6.75A.75.75 0 0 1 6.75 6h10.5a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-.75.75H6.75a.75.75 0 0 1-.75-.75V6.75Z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Required Software
                                    </h2>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($software as $soft)
                                            <span
                                                class="bg-secondary/10 text-secondary px-3 py-1.5 rounded-full text-sm font-medium hover:bg-primary/20 transition-colors">
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
                    <a href="{{ route('my-jobs.index') }}"
                        class="group text-tertiary hover:text-primary font-medium flex items-center transition-all py-2 px-4 rounded-lg hover:bg-white hover:shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 mr-2 text-secondary group-hover:text-primary transition-colors"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to My Jobs
                    </a>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('jobs.create') }}"
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
    <!-- Image Gallery -->
    <script>
        function changeMainImage(src) {
            document.getElementById('mainImage').src = src;

            // Update border colors to show active thumbnail
            const galleryItems = document.querySelectorAll('#imageGallery > div');
            galleryItems.forEach(item => {
                const img = item.querySelector('img');
                if (img.getAttribute('src') === src) {
                    item.classList.add('border-secondary');
                    item.classList.remove('border-transparent');
                } else {
                    item.classList.remove('border-secondary');
                    item.classList.add('border-transparent');
                }
            });
        }
    </script>
    <!-- Copy to Clipboard Function -->
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Visual feedback
                const button = document.getElementById('shareButton');
                const originalText = button.innerHTML;

                button.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Copied!
        `;

                setTimeout(function() {
                    button.innerHTML = originalText;
                }, 2000);
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
            });
        }
    </script>
</x-app-layout>
