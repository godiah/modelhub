@php($user = auth()->user())

<x-card>
    <div class="p-5">
        <div class="flex items-center gap-4">
            <x-user-avatar :user="$user" size="h-14 w-14" />
            <div class="min-w-0">
                <p class="truncate font-tertiary text-base font-semibold text-neutral-900">{{ $user->name }}</p>
                <p class="truncate text-sm text-tertiary">{{ $profile['location'] ?: $user->email }}</p>
            </div>
        </div>

        <!-- Reputation -->
        <div class="mt-4 flex items-center gap-2 text-sm">
            @if ($profile['rating'])
                <x-icon name="star-solid" class="h-5 w-5 text-accent" />
                <span class="font-semibold text-neutral-900">{{ number_format($profile['rating'], 1) }}</span>
                <span class="text-tertiary">{{ trans_choice('(:count review)|(:count reviews)', $profile['review_count'], ['count' => $profile['review_count']]) }}</span>
            @else
                <x-icon name="star" class="h-5 w-5 text-neutral-300" />
                <span class="text-tertiary">{{ __('No reviews yet') }}</span>
            @endif
        </div>

        <!-- Profile completeness -->
        <div class="mt-5">
            <div class="flex items-center justify-between text-sm">
                <span class="font-medium text-neutral-700">{{ __('Profile completeness') }}</span>
                <span class="font-semibold text-neutral-900">{{ $profile['completeness'] }}%</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-neutral-100" role="progressbar"
                aria-label="{{ __('Profile completeness') }}" aria-valuemin="0" aria-valuemax="100"
                aria-valuenow="{{ $profile['completeness'] }}">
                <div class="h-full rounded-full bg-teal-600" style="width: {{ $profile['completeness'] }}%"></div>
            </div>
            @if ($profile['next_step'])
                <a href="{{ route('profile') }}" wire:navigate
                    class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:text-teal-800">
                    {{ $profile['next_step'] }}
                    <x-icon name="chevron-right" class="h-4 w-4" />
                </a>
            @else
                <a href="{{ route('profile') }}" wire:navigate
                    class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:text-teal-800">
                    {{ __('Edit profile') }}
                    <x-icon name="chevron-right" class="h-4 w-4" />
                </a>
            @endif
        </div>

        @if ($profile['skills']->isNotEmpty())
            <ul class="mt-5 flex flex-wrap gap-2" aria-label="{{ __('Skills') }}">
                @foreach ($profile['skills'] as $skill)
                    <li><x-badge class="px-2.5 py-1 text-xs font-medium">{{ $skill->name }}</x-badge></li>
                @endforeach
            </ul>
        @endif
    </div>
</x-card>
