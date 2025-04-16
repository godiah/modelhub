<x-app-layout>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-tertiary font-bold text-xl text-primary leading-tight">
                    {{ __('Edit') }}: <span class="text-secondary">{{ $job->title }}</span>
                </h2>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('my-jobs.index') }}"
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

    <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <div
            class="rounded-2xl shadow-xl overflow-hidden bg-white transition-all duration-300 hover:shadow-2xl border border-neutral-200">

            <!-- Form Header -->
            <div class="p-8 bg-primary text-white relative overflow-hidden">
                <div class="relative z-10">
                    <h1 class="text-3xl font-bold font-tertiary mb-3 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-3 text-accent" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        Edit 3D Design Project
                    </h1>
                    <p class="text-sm text-neutral-100 font-secondary max-w-2xl">
                        Update your project brief to refine your search for the ideal 3D design professional.
                    </p>
                </div>
                <div class="absolute top-0 right-0 w-64 h-64 transform translate-x-16 -translate-y-16 opacity-10">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="0.5">
                        <path d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" />
                    </svg>
                </div>
            </div>

            <form method="POST" action="{{ route('jobs.update', $job) }}" class="p-8" enctype="multipart/form-data"
                id="projectForm">
                @csrf
                @method('PATCH')

                <!-- Budget -->
                <div class="mb-6">
                    <label for="budget" class="block text-sm font-medium text-primary mb-2 font-tertiary">
                        Budget
                    </label>
                    @error('budget')
                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                    @enderror
                    <div class="relative">
                        <div
                            class="absolute inset-y-0 left-0 flex items-center pr-3 pl-3 pointer-events-none bg-neutral-100 rounded-l-lg border-r border-neutral-200">
                            <span class="text-neutral-600 font-medium">Ksh.</span>
                        </div>
                        <input type="number" id="budget" name="budget" min="0" step="1"
                            placeholder="Enter project budget"
                            class="w-full pl-16 px-4 py-3 border border-neutral-300 rounded-lg text-sm focus:ring-2 focus:ring-secondary/50 focus:border-secondary transition-all duration-200 font-secondary appearance-none"
                            value="{{ intval($job->budget) }}">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="#14b8a6" stroke="currentColor"
                                class="h-5 w-5 text-neutral-400" viewBox="0 0 512 512">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M512 80c0 18-14.3 34.6-38.4 48c-29.1 16.1-72.5 27.5-122.3 30.9c-3.7-1.8-7.4-3.5-11.3-5C300.6 137.4 248.2 128 192 128c-8.3 0-16.4 .2-24.5 .6l-1.1-.6C142.3 114.6 128 98 128 80c0-44.2 86-80 192-80S512 35.8 512 80zM160.7 161.1c10.2-.7 20.7-1.1 31.3-1.1c62.2 0 117.4 12.3 152.5 31.4C369.3 204.9 384 221.7 384 240c0 4-.7 7.9-2.1 11.7c-4.6 13.2-17 25.3-35 35.5c0 0 0 0 0 0c-.1 .1-.3 .1-.4 .2c0 0 0 0 0 0s0 0 0 0c-.3 .2-.6 .3-.9 .5c-35 19.4-90.8 32-153.6 32c-59.6 0-112.9-11.3-148.2-29.1c-1.9-.9-3.7-1.9-5.5-2.9C14.3 274.6 0 258 0 240c0-34.8 53.4-64.5 128-75.4c10.5-1.5 21.4-2.7 32.7-3.5zM416 240c0-21.9-10.6-39.9-24.1-53.4c28.3-4.4 54.2-11.4 76.2-20.5c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 19.3-16.5 37.1-43.8 50.9c-14.6 7.4-32.4 13.7-52.4 18.5c.1-1.8 .2-3.5 .2-5.3zm-32 96c0 18-14.3 34.6-38.4 48c-1.8 1-3.6 1.9-5.5 2.9C304.9 404.7 251.6 416 192 416c-62.8 0-118.6-12.6-153.6-32C14.3 370.6 0 354 0 336l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 342.6 135.8 352 192 352s108.6-9.4 148.1-25.9c7.8-3.2 15.3-6.9 22.4-10.9c6.1-3.4 11.8-7.2 17.2-11.2c1.5-1.1 2.9-2.3 4.3-3.4l0 3.4 0 5.7 0 26.3zm32 0l0-32 0-25.9c19-4.2 36.5-9.5 52.1-16c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 10.5-5 21-14.9 30.9c-16.3 16.3-45 29.7-81.3 38.4c.1-1.7 .2-3.5 .2-5.3zM192 448c56.2 0 108.6-9.4 148.1-25.9c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 44.2-86 80-192 80S0 476.2 0 432l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 438.6 135.8 448 192 448z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-tertiary mt-2 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-secondary" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Enter budget in Kenyan Shillings
                    </p>
                </div>

                <!-- Deadline -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <label for="deadline" class="text-sm font-medium text-primary font-tertiary">
                            Deadline
                        </label>
                        <div class="flex items-center">
                            <input type="hidden" name="no_deadline" value="0">
                            <input type="checkbox" id="no_deadline" name="no_deadline" value="1"
                                class="h-4 w-4 text-secondary focus:ring-secondary border-neutral-300 rounded mr-2"
                                {{ is_null($job->deadline) ? 'checked' : '' }}>
                            <label for="no_deadline" class="text-xs text-neutral-600">No Fixed Deadline</label>
                        </div>
                    </div>
                    @error('deadline')
                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                    @enderror
                    <div class="relative">
                        <div id="deadline-display"
                            class="w-full px-4 py-3 border border-neutral-300 rounded-lg cursor-pointer flex items-center justify-between transition-all duration-300 hover:border-secondary focus:ring-2 focus:ring-secondary/50 bg-white {{ is_null($job->deadline) ? 'opacity-50' : '' }}">
                            <span id="selected-date-text" class="text-neutral-600 text-sm">
                                {{ $job->deadline ? \Carbon\Carbon::parse($job->deadline)->format('F j, Y') : 'No Fixed Deadline' }}
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-secondary" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <input type="date" id="deadline" name="deadline"
                            class="absolute inset-0 opacity-0 cursor-pointer"
                            value="{{ $job->deadline ? $job->deadline->format('Y-m-d') : '' }}">
                        <div id="calendar-container"
                            class="absolute z-50 mt-2 bg-white border border-neutral-300 rounded-lg shadow-lg p-4 hidden">
                            <div id="calendar" class="grid grid-cols-7 gap-2 text-center"></div>
                        </div>
                    </div>
                    <p class="text-xs text-tertiary mt-2 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-secondary" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Specify when you need this project completed
                    </p>
                </div>

                <!-- Status Toggle -->
                <div class="mb-6">
                    <div class="flex items-center mb-2">
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                            class="h-4 w-4 text-secondary focus:ring-secondary border-neutral-300 rounded mr-2"
                            {{ $job->is_active ? 'checked' : '' }}>
                        <label for="is_active" class="block text-sm font-medium text-primary font-tertiary">
                            Active
                        </label>
                    </div>
                    @error('is_active')
                        <p class="text-red-500 text-xs italic">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-tertiary mt-2 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-secondary" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Uncheck to mark project as closed and hide it from public view
                    </p>
                </div>

                <!-- Submit button -->
                <div class="flex flex-col sm:flex-row justify-end gap-4 mt-8 border-t border-neutral-200 pt-6">
                    <button type="submit" id="submit-btn"
                        class="px-6 py-3 bg-secondary text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary font-secondary transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center shadow-md hover:shadow-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Update Project Details
                    </button>
                </div>
            </form>
        </div>

        <script src="{{ asset('js/submit-btn-edit.js') }}"></script>
    </div>

    <!-- Footer -->
    @include('partials\footer-secondary')
</x-app-layout>
