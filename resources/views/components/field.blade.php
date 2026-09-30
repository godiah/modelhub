@props([
    'name',
    'label' => null,
    'type' => 'text', // text|email|password|tel|textarea|select|...
    'hint' => null,
    'icon' => null, // registry icon shown inside the field
    'size' => 'md', // md = compact settings field; lg = roomy filled field used on the auth pages
    // Error bag key; defaults to the wire:model property so it rarely needs setting.
    'error' => null,
])

{{--
    The one text-field component: label above, optional inside icon, hint, error state, password show/hide and textarea.
    Slot `action` renders on the label row (e.g. a "Forgot password?" link).
--}}
@php
    $id = $attributes->get('id', $name);
    $errorKey = $error ?? $attributes->wire('model')->value();
    $messages = $errorKey ? $errors->get($errorKey) : [];
    $hasError = count($messages) > 0;
    $isPassword = $type === 'password';
    $isTextarea = $type === 'textarea';
    $isSelect = $type === 'select';
    $describedBy = trim(($hasError ? "$id-error " : '') . ($hint ? "$id-hint" : ''));

    $large = $size === 'lg';

    $classes = [
        'block w-full rounded-xl border text-neutral-900 placeholder-neutral-400 shadow-none transition-colors duration-200 focus:outline-none focus:ring-2',
        'bg-white px-4 py-2.5 text-sm' => !$large,
        'bg-neutral-50 px-4 py-3 text-base focus:bg-white' => $large,
        'border-neutral-300 hover:border-neutral-400 focus:border-secondary focus:ring-secondary/25' => !$hasError && !$large,
        'border-neutral-200 focus:border-secondary focus:ring-secondary/30' => !$hasError && $large,
        'border-red-400 focus:border-red-500 focus:ring-red-200' => $hasError,
        'pl-10' => $icon,
        'pr-12' => $isPassword,
        'pr-10' => $isSelect,
    ];

    $bag = $attributes->merge([
        'id' => $id,
        'name' => $name,
        'aria-invalid' => $hasError ? 'true' : null,
        'aria-describedby' => $describedBy ?: null,
    ]);
@endphp

<div @if ($isPassword) x-data="{ show: false }" @endif>
    @if ($label || isset($action))
        <div class="mb-1.5 flex items-center justify-between gap-3">
            @if ($label)
                <label for="{{ $id }}" class="text-sm font-medium text-neutral-800">{{ $label }}</label>
            @endif
            @isset($action)
                <span class="text-sm">{{ $action }}</span>
            @endisset
        </div>
    @endif

    <div class="relative">
        @if ($icon)
            <span @class(['pointer-events-none absolute left-3 text-neutral-400', 'top-2.5' => !$large, 'top-3.5' => $large])>
                <x-icon :name="$icon" class="h-5 w-5" />
            </span>
        @endif

        @if ($isTextarea)
            <textarea {{ $bag->class($classes) }}>{{ $slot }}</textarea>
        @elseif ($isSelect)
            <select {{ $bag->class($classes) }}>{{ $slot }}</select>
        @else
            <input {{ $bag->merge(['type' => $type])->class($classes) }}
                @if ($isPassword) :type="show ? 'text' : 'password'" @endif>
        @endif

        @if ($isPassword)
            <button type="button" @click="show = !show"
                :aria-label="show ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
                class="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-xl text-neutral-400 transition-colors hover:text-neutral-700 focus:outline-none focus-visible:text-secondary">
                <x-icon name="eye" class="h-5 w-5" x-show="!show" />
                <x-icon name="eye-slash" class="h-5 w-5" x-show="show" x-cloak />
            </button>
        @endif
    </div>

    @if ($hint && !$hasError)
        <p id="{{ $id }}-hint" class="mt-1.5 text-xs text-tertiary">{{ $hint }}</p>
    @endif

    @if ($hasError)
        <x-input-error :messages="$messages" id="{{ $id }}-error" class="mt-1.5" />
    @endif
</div>
