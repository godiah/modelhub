@props(['user', 'size' => 'h-9 w-9'])

@php
    $profile = $user->profile;
    $hasAvatar = $profile && $profile->avatar && file_exists(storage_path('app/public/' . $profile->avatar));
@endphp

@if ($hasAvatar)
    <img src="{{ asset('storage/' . $profile->avatar) }}" alt="{{ $user->name }}"
        {{ $attributes->class([$size, 'shrink-0 rounded-full object-cover border border-neutral-200']) }}>
@else
    <span aria-hidden="true"
        {{ $attributes->class([$size, 'flex shrink-0 items-center justify-center rounded-full border border-teal-700/15 bg-teal-50 font-main text-sm font-semibold text-teal-800']) }}>
        {{ $user->getInitials() }}
    </span>
@endif
