@props([
    'name',
    'label',
    'type' => 'text',
    'hint' => null,
    // Error bag key; defaults to the wire:model property so it rarely needs setting.
    'error' => null,
])

@php
    $id = $attributes->get('id', $name);
    $errorKey = $error ?? $attributes->wire('model')->value();
    $messages = $errorKey ? $errors->get($errorKey) : [];
    $hasError = count($messages) > 0;
    $isPassword = $type === 'password';
    $describedBy = trim(($hasError ? "$id-error " : '') . ($hint ? "$id-hint" : ''));
@endphp

<div @if ($isPassword) x-data="{ show: false }" @endif>
    <div class="mb-1.5 flex items-center justify-between gap-3">
        <label for="{{ $id }}" class="text-sm font-medium text-neutral-700">{{ $label }}</label>
        @isset($action)
            <span class="text-sm">{{ $action }}</span>
        @endisset
    </div>

    <div class="relative">
        <input {{ $attributes->merge([
            'id' => $id,
            'name' => $name,
            'type' => $type,
            'aria-invalid' => $hasError ? 'true' : null,
            'aria-describedby' => $describedBy ?: null,
        ])->class([
            'block w-full rounded-xl border bg-neutral-50 px-4 py-3 text-base text-neutral-900 placeholder-neutral-400 shadow-none transition-colors duration-200 focus:bg-white focus:outline-none focus:ring-2',
            'border-neutral-200 focus:border-secondary focus:ring-secondary/30' => !$hasError,
            'border-red-400 focus:border-red-500 focus:ring-red-200' => $hasError,
            'pr-12' => $isPassword,
        ]) }}
            @if ($isPassword) :type="show ? 'text' : 'password'" @endif>

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
