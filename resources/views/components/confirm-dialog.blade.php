{{--
    Confirmation dialog that submits a form. Open with $dispatch('open-modal', 'name') (or with a
    payload object, readable in the slot as `payload`, e.g. <input type="hidden" name="id" :value="payload.id">).
    Use action-bind (an Alpine expression) instead of action when the URL depends on the payload.
--}}
@props([
    'name' => null,
    'bind' => null,
    'title',
    'message' => null,
    'icon' => 'exclamation-triangle',
    'tone' => 'danger',
    'confirmLabel' => 'Confirm',
    'confirmIcon' => null,
    'disabledWhen' => 'false',
    'state' => null,
    'cancelLabel' => 'Cancel',
    'action' => null,
    'actionBind' => null,
    'method' => 'POST',
    'maxWidth' => 'md',
])

@php
    $iconClass = [
        'danger' => 'bg-red-100 text-red-600',
        'primary' => 'bg-primary/10 text-primary',
        'success' => 'bg-secondary/10 text-secondary',
    ][$tone];
    $buttonVariant = ['danger' => 'danger', 'primary' => 'primary', 'success' => 'primary'][$tone];
@endphp

<x-modal :name="$name" :bind="$bind" :max-width="$maxWidth" focusable>
    <form method="POST" @if ($action) action="{{ $action }}" @endif @if ($actionBind) x-bind:action="{{ $actionBind }}" @endif
        x-data="{ loading: false{{ $state ? ', '.$state : '' }} }" x-on:submit="loading = true">
        @csrf
        @if (strtoupper($method) !== 'POST')
            @method($method)
        @endif

        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 rounded-full p-3 {{ $iconClass }}">
                    <x-icon :name="$icon" class="h-6 w-6" />
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="font-tertiary text-lg font-semibold text-neutral-800">{{ $title }}</h3>
                    @if ($message)
                        <p class="mt-2 font-main text-sm text-neutral-600">{{ $message }}</p>
                    @endif
                    {{ $slot }}
                </div>
            </div>
        </div>

        <x-modal.footer>
            <x-btn type="button" variant="secondary" x-on:click="dismiss()">{{ $cancelLabel }}</x-btn>
            <x-btn type="submit" :variant="$buttonVariant" x-bind:disabled="loading || ({{ $disabledWhen }})" class="justify-center disabled:cursor-not-allowed disabled:opacity-60">
                <span x-show="! loading" class="inline-flex items-center">
                    @if ($confirmIcon)
                        <x-icon :name="$confirmIcon" class="h-4 w-4" />
                    @endif
                    {{ $confirmLabel }}
                </span>
                <span x-show="loading" x-cloak>Processing...</span>
            </x-btn>
        </x-modal.footer>
    </form>
</x-modal>
