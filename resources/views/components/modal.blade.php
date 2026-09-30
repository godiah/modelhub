{{--
    Base modal. Open it three ways:
      - by name:   $dispatch('open-modal', 'name')  or  $dispatch('open-modal', { name: 'name', ...payload })
                   (the payload is available inside the slot as `payload`)
      - by state:  bind="someAlpineVar" ties it to a variable in an enclosing x-data
      - on load:   :show="true"
    Close with $dispatch('close') / $dispatch('close-modal', 'name') or dismiss() from inside the slot.
    on-close="..." runs an Alpine expression when the user dismisses it (backdrop, Esc, header X).
--}}
@props([
    'name' => null,
    'show' => false,
    'maxWidth' => '2xl',
    'bind' => null,
    'onClose' => null,
])

@php
    $maxWidthClass = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
    ][$maxWidth];
    $stateRef = $bind ?: 'this.isOpen';
@endphp

<div
    x-data="{
        isOpen: @js($show),
        payload: {},
        get show() { return {!! $stateRef !!} },
        set show(value) { {!! $stateRef !!} = value },
        dismiss() { this.show = false; {!! $onClose !!} },
        focusables() {
            let selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])'
            return [...$el.querySelectorAll(selector)].filter(el => ! el.hasAttribute('disabled'))
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) - 1 },
    }"
    x-init="$watch('show', value => {
        if (value) {
            document.body.classList.add('overflow-y-hidden');
            {{ $attributes->has('focusable') ? 'setTimeout(() => firstFocusable()?.focus(), 100)' : '' }}
        } else {
            document.body.classList.remove('overflow-y-hidden');
        }
    })"
    @if ($name)
        x-on:open-modal.window="if (($event.detail?.name ?? $event.detail) === '{{ $name }}') { payload = typeof $event.detail === 'object' ? $event.detail : {}; show = true }"
        x-on:close-modal.window="if (($event.detail?.name ?? $event.detail) === '{{ $name }}') show = false"
    @endif
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="if (show) dismiss()"
    x-on:keydown.tab.prevent="if (show) { $event.shiftKey || nextFocusable().focus() }"
    x-on:keydown.shift.tab.prevent="if (show) prevFocusable().focus()"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: {{ $show ? 'block' : 'none' }};"
    role="dialog"
    aria-modal="true"
>
    <div
        x-show="show"
        class="fixed inset-0 bg-neutral-900/50 backdrop-blur-sm"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    <div class="relative flex min-h-full items-center justify-center p-4" x-on:click.self="dismiss()">
        <div
            x-show="show"
            class="relative w-full {{ $maxWidthClass }} transform overflow-hidden rounded-xl bg-white shadow-xl transition-all"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
        >
            {{ $slot }}
        </div>
    </div>
</div>
