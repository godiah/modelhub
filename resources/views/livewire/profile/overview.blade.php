<?php

use App\Services\Profile\ProfileOverviewService;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component {
    /**
     * Re-render whenever one of the edit forms saves, so this summary is never stale.
     */
    #[On(['profile-info-updated', 'profile-details-updated', 'social-links-updated'])]
    public function refresh(): void {}

    public function with(): array
    {
        return ['overview' => app(ProfileOverviewService::class)->overview(auth()->user())];
    }
}; ?>

@php
    $user = $overview['user'];
    $profile = $overview['profile'];
    $ratings = $overview['ratings'];
    $stats = $overview['stats'];
    $checklist = $overview['completeness'];
    $maxVotes = max(1, max($ratings['distribution']));
    $safeColor = fn (?string $color) => preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $color) ? $color : '#6B7280';
@endphp

<div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[20rem_minmax(0,1fr)]">
    <!-- Left rail: identity, completeness, contact -->
    <aside class="space-y-6 lg:sticky lg:top-24">
        <x-card class="rounded-2xl">
            <div class="flex flex-col items-center px-6 pb-6 pt-8 text-center">
                <x-user-avatar :user="$user" size="h-28 w-28" class="!text-3xl ring-4 ring-teal-50" />
                <p class="mt-4 font-tertiary text-xl font-bold text-neutral-900">{{ $user->name }}</p>
                <p class="mt-1 text-sm text-tertiary">
                    {{ $profile?->location ?: __('Add your location') }}
                </p>
                <p class="text-xs text-tertiary">{{ __('Member since :date', ['date' => $user->created_at->format('M Y')]) }}</p>

                <!-- Social icons -->
                <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
                    @forelse ($overview['socialLinks'] as $link)
                        <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer"
                            title="{{ $link->socialNetwork->name }}" aria-label="{{ $link->socialNetwork->name }}"
                            class="flex h-10 w-10 items-center justify-center rounded-full border border-neutral-200 bg-white transition-colors duration-150 hover:bg-neutral-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                            <i class="{{ $link->socialNetwork->icon }} text-base" style="color: {{ $safeColor($link->socialNetwork->color) }}" aria-hidden="true"></i>
                        </a>
                    @empty
                        <button type="button" @click="show('profile')"
                            class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('Add your links') }}</button>
                    @endforelse
                </div>

                <x-btn type="button" @click="show('profile')" class="mt-6" block>
                    <x-icon name="pencil-square" class="h-4 w-4" />
                    {{ __('Edit profile') }}
                </x-btn>
            </div>
        </x-card>

        <!-- Completeness -->
        <x-card class="rounded-2xl">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <h3 class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Profile strength') }}</h3>
                    <span class="text-sm font-semibold tabular-nums text-neutral-900">{{ $checklist['percent'] }}%</span>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-neutral-100" role="progressbar"
                    aria-label="{{ __('Profile strength') }}" aria-valuemin="0" aria-valuemax="100"
                    aria-valuenow="{{ $checklist['percent'] }}">
                    <div class="h-full rounded-full bg-teal-600" style="width: {{ $checklist['percent'] }}%"></div>
                </div>
                <ul class="mt-4 space-y-2.5">
                    @foreach ($checklist['items'] as $label => $done)
                        <li class="flex items-center gap-2.5 text-sm">
                            @if ($done)
                                <x-icon name="check-circle-solid" class="h-5 w-5 shrink-0 text-teal-700" />
                                <span class="text-tertiary line-through decoration-neutral-300">{{ $label }}</span>
                            @else
                                <span class="h-5 w-5 shrink-0 rounded-full border-2 border-neutral-300"></span>
                                <button type="button" @click="show('profile')"
                                    class="text-left font-medium text-neutral-800 hover:text-teal-700">{{ $label }}</button>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </x-card>

        <!-- Contact -->
        <x-card class="rounded-2xl">
            <div class="p-6">
                <h3 class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Contact') }}</h3>
                <ul class="mt-3 space-y-3 text-sm">
                    <li class="flex items-center gap-3 text-neutral-700">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-neutral-500"><x-icon name="envelope" class="h-4 w-4" /></span>
                        <span class="truncate">{{ $user->email }}</span>
                    </li>
                    <li class="flex items-center gap-3 text-neutral-700">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-neutral-500"><x-icon name="phone" class="h-4 w-4" /></span>
                        @if ($profile?->telephone_number)
                            <span>{{ $profile->telephone_number }}</span>
                        @else
                            <span class="text-tertiary">{{ __('No phone number added') }}</span>
                        @endif
                    </li>
                </ul>
            </div>
        </x-card>
    </aside>

    <!-- Main column -->
    <div class="min-w-0 space-y-6">
        <!-- Stat tiles -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-card class="rounded-2xl">
                <div class="flex items-center gap-4 p-5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-teal-50 text-teal-700"><x-icon name="banknotes" class="h-5 w-5" /></span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-tertiary">{{ __('Total earned') }}</p>
                        <p class="truncate font-tertiary text-2xl font-bold tabular-nums text-neutral-900">{{ \App\Support\Money::format($stats['earned'], 0) }}</p>
                    </div>
                </div>
            </x-card>
            <x-card class="rounded-2xl">
                <div class="flex items-center gap-4 p-5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-teal-50 text-teal-700"><x-icon name="briefcase" class="h-5 w-5" /></span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-tertiary">{{ __('Projects completed') }}</p>
                        <p class="font-tertiary text-2xl font-bold tabular-nums text-neutral-900">{{ $stats['completed'] }}
                            @if ($stats['active'] > 0)
                                <span class="text-xs font-medium text-tertiary">· {{ __(':count in progress', ['count' => $stats['active']]) }}</span>
                            @endif
                        </p>
                    </div>
                </div>
            </x-card>
            <x-card class="rounded-2xl">
                <div class="flex items-center gap-4 p-5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600"><x-icon name="star-solid" class="h-5 w-5" /></span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-tertiary">{{ __('Rating') }}</p>
                        @if ($ratings['average'])
                            <p class="font-tertiary text-2xl font-bold tabular-nums text-neutral-900">{{ number_format($ratings['average'], 1) }}
                                <span class="text-xs font-medium text-tertiary">· {{ trans_choice(':count review|:count reviews', $ratings['count'], ['count' => $ratings['count']]) }}</span>
                            </p>
                        @else
                            <p class="text-sm font-medium text-tertiary">{{ __('No reviews yet') }}</p>
                        @endif
                    </div>
                </div>
            </x-card>
        </div>

        <!-- About -->
        <x-card class="rounded-2xl">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <h3 class="font-tertiary text-base font-semibold text-neutral-900">{{ __('About') }}</h3>
                    <button type="button" @click="show('profile')" class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('Edit') }}</button>
                </div>
                @if ($profile?->professional_info)
                    <p class="mt-3 whitespace-pre-line text-sm leading-7 text-neutral-700">{{ $profile->professional_info }}</p>
                @else
                    <p class="mt-3 text-sm text-tertiary">{{ __("You haven't described your professional background yet.") }}
                        <button type="button" @click="show('profile')" class="font-medium text-teal-700 hover:text-teal-800">{{ __('Add a bio') }}</button>
                    </p>
                @endif
            </div>
        </x-card>

        <!-- Skills and tools -->
        <x-card class="rounded-2xl">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <h3 class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Skills and tools') }}</h3>
                    <button type="button" @click="show('profile')" class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('Edit') }}</button>
                </div>

                <div class="mt-4 grid gap-6 sm:grid-cols-2">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Skills') }}</p>
                        @if ($overview['skills']->isNotEmpty())
                            <ul class="mt-3 flex flex-wrap gap-2">
                                @foreach ($overview['skills'] as $skill)
                                    <li class="rounded-full bg-teal-50 px-3 py-1 text-xs font-medium text-teal-800 ring-1 ring-inset ring-teal-600/15">{{ $skill->name }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-3 text-sm text-tertiary">{{ __('No skills added yet.') }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Software') }}</p>
                        @if ($overview['software']->isNotEmpty())
                            <ul class="mt-3 flex flex-wrap gap-2">
                                @foreach ($overview['software'] as $tool)
                                    <li class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium text-neutral-800 ring-1 ring-inset ring-neutral-500/15">{{ $tool->name }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-3 text-sm text-tertiary">{{ __('No software added yet.') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </x-card>

        <!-- Work history -->
        <x-card class="rounded-2xl">
            <div class="flex items-center justify-between px-6 pt-6">
                <h3 class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Work history') }}</h3>
                <a href="{{ route('engagements.index') }}" wire:navigate class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('View engagements') }}</a>
            </div>
            @if (count($overview['history']) === 0)
                <p class="px-6 pb-6 pt-3 text-sm text-tertiary">{{ __('Completed projects will show up here as you deliver them.') }}</p>
            @else
                <ul class="mt-2 divide-y divide-neutral-100 px-6 pb-2">
                    @foreach ($overview['history'] as $item)
                        <li class="flex items-center gap-4 py-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-500"><x-icon name="briefcase" class="h-5 w-5" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-neutral-900">{{ $item['title'] }}</p>
                                <p class="truncate text-xs text-tertiary">
                                    @if ($item['client']){{ __('for :name', ['name' => $item['client']]) }} · @endif{{ $item['completed_at']->format('M Y') }}
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-semibold tabular-nums text-neutral-900">{{ \App\Support\Money::format($item['amount'], 0) }}</p>
                                @if ($item['rating'])
                                    <p class="flex items-center justify-end gap-1 text-xs text-tertiary"><x-icon name="star-solid" class="h-3.5 w-3.5 text-accent" />{{ $item['rating'] }}.0</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <!-- Reviews -->
        <x-card class="rounded-2xl">
            <div class="p-6">
                <h3 class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Reviews') }}</h3>

                @if ($ratings['count'] === 0)
                    <p class="mt-3 text-sm text-tertiary">{{ __('Your reviews will appear here once clients share their experience working with you.') }}</p>
                @else
                    <div class="mt-4 flex flex-col gap-6 sm:flex-row sm:items-center">
                        <div class="shrink-0 sm:w-40">
                            <p class="font-tertiary text-4xl font-bold tabular-nums text-neutral-900">{{ number_format($ratings['average'], 1) }}</p>
                            <p class="mt-1 text-sm text-tertiary">{{ trans_choice(':count review|:count reviews', $ratings['count'], ['count' => $ratings['count']]) }}</p>
                        </div>
                        <ul class="flex-1 space-y-1.5" aria-label="{{ __('Rating breakdown') }}">
                            @foreach ($ratings['distribution'] as $stars => $votes)
                                <li class="flex items-center gap-3 text-xs text-tertiary">
                                    <span class="w-10 shrink-0">{{ $stars }} {{ __('star') }}</span>
                                    <span class="h-2 flex-1 overflow-hidden rounded-full bg-neutral-100">
                                        <span class="block h-full rounded-full bg-accent" style="width: {{ round($votes / $maxVotes * 100) }}%"></span>
                                    </span>
                                    <span class="w-6 shrink-0 text-right tabular-nums">{{ $votes }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <ul class="mt-6 divide-y divide-neutral-100 border-t border-neutral-100">
                        @foreach ($overview['reviews'] as $review)
                            <li class="flex gap-4 py-4">
                                <x-user-avatar :user="$review->reviewer" size="h-10 w-10" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <p class="text-sm font-semibold text-neutral-900">{{ $review->reviewer->name }}</p>
                                        <span class="flex items-center gap-0.5" aria-label="{{ __(':rating out of 5', ['rating' => $review->rating]) }}">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <x-icon name="star-solid" class="h-4 w-4 {{ $i <= $review->rating ? 'text-accent' : 'text-neutral-300' }}" />
                                            @endfor
                                        </span>
                                        <span class="text-xs text-tertiary">{{ $review->created_at->diffForHumans() }}</span>
                                    </div>
                                    @if ($review->engagement?->job)
                                        <p class="mt-0.5 truncate text-xs text-tertiary">{{ $review->engagement->job->title }}</p>
                                    @endif
                                    @if ($review->review)
                                        <p class="mt-2 text-sm leading-relaxed text-neutral-700">{{ $review->review }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </x-card>
    </div>
</div>
