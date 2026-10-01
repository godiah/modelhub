@props(['user' => null, 'size' => 'h-9 w-9'])

{{-- A member's avatar picture (they pick one; new members get a random one). Decorative: the name is shown beside it. --}}
<img src="{{ $user ? $user->avatarUrl() : \App\Support\Avatars::url(\App\Support\Avatars::PEOPLE, null, 0) }}" alt="" loading="lazy"
    {{ $attributes->class([$size, 'shrink-0 rounded-full border border-neutral-200 bg-white object-cover']) }}>
