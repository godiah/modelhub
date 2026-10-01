@props(['product', 'saved' => false, 'variant' => 'icon', 'count' => null])

{{--
    Save a model to the wishlist. Signed-in members get a form that toggles in place (and still works without
    JavaScript); guests get a link to sign in. variant: icon (a heart on a card) | button (the model page).
--}}
@php
    $icon = $variant === 'icon';
    $base = $icon
        ? 'flex h-9 w-9 items-center justify-center rounded-full bg-white/95 text-neutral-500 shadow-sm ring-1 ring-black/5 transition hover:text-red-500 hover:shadow focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/60'
        : 'inline-flex w-full items-center justify-center gap-2 rounded-xl border border-neutral-300 bg-white px-4 py-2.5 text-sm font-semibold text-neutral-800 transition-colors hover:border-neutral-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40';
@endphp
@auth
    <form method="POST" action="{{ route('models.wishlist.toggle', $product) }}" @submit.prevent="toggle()" {{ $attributes }}
        x-data="{
            saved: @js((bool) $saved),
            count: @js($count),
            busy: false,
            async toggle() {
                if (this.busy) return;
                this.busy = true;
                const was = this.saved;
                this.saved = ! was;
                if (this.count !== null) this.count += was ? -1 : 1;
                try {
                    const response = await fetch(this.$el.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content, 'Accept': 'application/json' } });
                    if (! response.ok) throw new Error(response.status);
                    const data = await response.json();
                    this.saved = data.saved;
                    if (this.count !== null) this.count = data.count;
                } catch (e) {
                    this.saved = was;
                    if (this.count !== null) this.count += was ? 1 : -1;
                } finally { this.busy = false; }
            },
        }">
        @csrf
        <button type="submit" :aria-pressed="saved.toString()" :aria-label="saved ? @js(__('Remove from wishlist')) : @js(__('Save to wishlist'))" class="{{ $base }}" :class="saved && '{{ $icon ? 'text-red-500' : 'border-red-200 text-red-600' }}'">
            <x-icon name="heart" x-show="! saved" class="h-5 w-5" />
            <x-icon name="heart-solid" x-show="saved" x-cloak class="h-5 w-5 text-red-500" />
            @unless ($icon)
                <span x-text="saved ? @js(__('Saved to wishlist')) : @js(__('Save to wishlist'))">{{ $saved ? __('Saved to wishlist') : __('Save to wishlist') }}</span>
                @if ($count !== null)<span class="text-xs font-normal text-tertiary" x-text="'· ' + count">· {{ $count }}</span>@endif
            @endunless
        </button>
    </form>
@else
    <a href="{{ route('login') }}" {{ $attributes->class([$base]) }} title="{{ __('Sign in to save models') }}" aria-label="{{ __('Sign in to save to your wishlist') }}">
        <x-icon name="heart" class="h-5 w-5" />
        @unless ($icon)<span>{{ __('Save to wishlist') }}</span>@if ($count !== null)<span class="text-xs font-normal text-tertiary">· {{ $count }}</span>@endif @endunless
    </a>
@endauth
