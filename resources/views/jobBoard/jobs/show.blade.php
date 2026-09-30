<x-app-layout :crumb="$job->title">
    <div class="container mx-auto max-w-7xl">
        <div class="max-w-7xl mx-auto px-4 py-14 sm:px-6 lg:px-8">
            <x-card rounded="2xl" shadow="xl" clip class="transition-all duration-300 hover:shadow-2xl">
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
                                <x-icon name="check-circle-solid" class="w-5 h-5" />
                                <span>Active</span>
                            @else
                                <!-- Exclamation icon -->
                                <x-icon name="exclamation-triangle-solid" class="w-5 h-5" />
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
                                <x-money :amount="$job->budget" />
                            </span>
                        </div>
                    </div>

                    <!-- Job Meta Info -->
                    <div class="relative z-10 mt-2 flex flex-wrap items-center gap-3 text-sm">
                        <span class="bg-white/15 backdrop-blur-sm text-white px-3 py-1 rounded-full flex items-center">
                            <x-icon name="calendar" class="h-4 w-4 mr-1.5" />
                            Posted on {{ \Carbon\Carbon::parse($job->created_at)->format('F j, Y') }}
                        </span>
                        <span class="bg-white/15 backdrop-blur-sm text-white px-3 py-1 rounded-full flex items-center">
                            <x-icon name="clock" class="h-4 w-4 mr-1.5" />
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
                                <x-icon name="photo" class="h-16 w-16 text-neutral-300" stroke-width="1.5" />
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
                                <x-btn variant="secondary" size="sm" onclick="copyToClipboard('{{ $jobUrl }}')" id="shareButton">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                    </svg>
                                    Share
                                </x-btn>
                                <x-btn variant="secondary" size="sm" href="{{ route('jobs.edit', ['job' => $job->slug]) }}">
                                    <x-icon name="pencil-square" class="h-4 w-4" />
                                    Edit
                                </x-btn>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Job Details -->
                    <div class="md:w-3/5 p-6">
                        <!-- Description -->
                        @if ($job->description)
                            <div class="mb-6">
                                <h2 class="text-lg font-bold text-primary mb-3 flex items-center font-tertiary">
                                    <x-icon name="document-text" class="h-5 w-5 mr-2 text-secondary" />
                                    Job Description
                                </h2>
                                <div class="border-l-4 border-secondary/20 pl-4">
                                    <x-jobs.markdown-description :content="$job->description" secondary-font />
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
                                        <x-icon name="light-bulb" class="h-5 w-5 mr-2 text-secondary" />
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
                    <x-btn variant="ghost" href="{{ route('my-jobs.index') }}">
                        <x-icon name="arrow-left" class="h-5 w-5" />
                        Back to My Jobs
                    </x-btn>

                    <div class="flex items-center gap-3">
                        <x-btn href="{{ route('jobs.create') }}">
                            <x-icon name="plus" class="h-5 w-5" />
                            Create New Project
                        </x-btn>
                    </div>
                </div>
            </x-card>
        </div>
    </div>

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
