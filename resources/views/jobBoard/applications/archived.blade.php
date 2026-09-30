@use('App\Enums\ApplicationStatus')
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-tertiary font-bold text-2xl text-primary leading-tight">
                    {{ __('Archived Applications') }}
                </h2>

            </div>
            <div class="flex space-x-3">
                <a href="{{ route('applications.my') }}"
                    class="inline-flex items-center px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary/90 transition-colors duration-200 font-main text-sm font-medium shadow-sm">
                    <x-icon name="clipboard-check" class="h-5 w-5 mr-2" />
                    Back to Active Applications
                </a>
            </div>
        </div>
    </x-slot>

    <section class="bg-gradient-to-br from-neutral-50 to-neutral-100">
        <div class="container mx-auto max-w-7xl px-4 py-12 min-h-screen">
            {{-- Quick Stats --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                @php
                    $statusCounts = $applications->countBy('status');
                @endphp

                @foreach (['hired' => 'green', 'rejected' => 'red', 'withdrawn' => 'gray', 'other' => 'neutral'] as $status => $color)
                    <x-card class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-tertiary font-secondary">{{ ucfirst($status) }}</p>
                                <p class="text-2xl font-bold text-neutral-800 font-tertiary mt-1">
                                    {{ $status === 'other'
                                        ? $applications->count() -
                                            ($statusCounts['hired'] ?? 0) -
                                            ($statusCounts['rejected'] ?? 0) -
                                            ($statusCounts['withdrawn'] ?? 0)
                                        : $statusCounts[$status] ?? 0 }}
                                </p>
                            </div>
                            <div class="p-3 bg-{{ $color }}-50 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-{{ $color }}-600"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    @if ($status === 'hired')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    @elseif($status === 'rejected')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    @elseif($status === 'withdrawn')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                    @endif
                                </svg>
                            </div>
                        </div>
                    </x-card>
                @endforeach
            </div>

            {{-- Archived Applications List --}}
            <div class="grid gap-4 md:grid-cols-1">
                @forelse ($applications as $application)
                    <x-card clip class="hover:shadow-md transition-all duration-200">
                        <div class="p-6 flex flex-col md:flex-row gap-6 items-start md:items-center">
                            {{-- Job Details --}}
                            <div class="flex items-center flex-grow">
                                <div
                                    class="h-12 w-12 rounded-lg bg-neutral-50 flex items-center justify-center overflow-hidden mr-4 border border-neutral-100">
                                    @if ($application->job->images)
                                        <img src="{{ asset('storage/' . $application->job->images) }}"
                                            alt="{{ $application->job->slug }}" class="h-full w-full object-cover">
                                    @else
                                        <x-icon name="briefcase-2" class="h-6 w-6 text-secondary/70" />
                                    @endif
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-neutral-800 font-tertiary">
                                        {{ $application->job->title }}
                                    </h2>
                                    <p class="text-sm text-tertiary font-main mt-1">
                                        Applied: <x-date :date="$application->created_at" format="M d, Y" /> &bull;
                                        Archived: <x-date :date="$application->updated_at" format="M d, Y" />
                                    </p>
                                </div>
                            </div>

                            {{-- Status and Actions --}}
                            <div class="flex flex-col md:flex-row items-start md:items-center gap-3">
                                @php
                                    $statusClasses = [
                                        'hired' => 'bg-green-50 text-green-700',
                                        'rejected' => 'bg-red-50 text-red-700',
                                        'withdrawn' => 'bg-neutral-100 text-neutral-700',
                                        'default' => 'bg-tertiary/10 text-tertiary',
                                    ];
                                    $statusClass = $statusClasses[$application->status->value] ?? $statusClasses['default'];
                                @endphp

                                <span
                                    class="px-3 py-1 inline-flex items-center text-sm font-medium rounded-full {{ $statusClass }}">
                                    {{ $application->status->label() }}
                                </span>

                                <div class="flex space-x-2">
                                    <a href="{{ route('applications.show', ['job' => $application->job->slug]) }}"
                                        class="p-2 text-tertiary hover:text-primary bg-neutral-50 hover:bg-neutral-100 rounded-full transition-colors"
                                        title="View Application">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>

                                    <form action="{{ route('applications.restore', $application) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="p-2 text-secondary hover:text-secondary/80 bg-neutral-50 hover:bg-neutral-100 rounded-full transition-colors"
                                            title="Restore Application">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                            </svg>
                                        </button>
                                    </form>

                                    <form action="{{ route('applications.destroy', $application) }}" method="POST"
                                        onsubmit="return confirm('Are you sure you want to permanently delete this application?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="p-2 text-red-600 hover:text-red-800 bg-neutral-50 hover:bg-neutral-100 rounded-full transition-colors"
                                            title="Delete Application">
                                            <x-icon name="trash" class="h-5 w-5" />
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </x-card>
                @empty
                    <x-card class="p-12 text-center">
                        <div class="max-w-md mx-auto">
                            <x-icon name="clipboard-check" class="h-16 w-16 mx-auto text-neutral-400 mb-4" />
                            <h3 class="text-xl font-bold text-neutral-800 mb-2 font-tertiary">No Archived Applications
                            </h3>
                            <p class="text-tertiary mb-6 font-main">You haven't archived any job applications yet.</p>
                            <a href="{{ route('applications.my') }}"
                                class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-primary hover:bg-primary/90 transition-colors duration-200 font-main">
                                View Active Applications
                            </a>
                        </div>
                    </x-card>
                @endforelse
            </div>
            {{-- Pagination --}}
            @if ($applications->hasPages())
                <div class="mt-8">
                    {{ $applications->links() }}
                </div>
            @endif
        </div>
    </section>
</x-app-layout>
