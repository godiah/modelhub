@props(['title', 'subtitle' => null, 'variant' => 'primary'])

@php($danger = $variant === 'danger')

<!-- Header Section -->
<div @class(['border-b px-6 py-5', 'border-red-100' => $danger, 'border-neutral-100' => !$danger])>
    <div class="flex items-center justify-between gap-4">
        <div class="flex min-w-0 items-center gap-3">
            <div @class([
                'flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                'bg-red-50 text-red-700' => $danger,
                'bg-teal-50 text-teal-700' => !$danger,
            ])>
                {{ $icon }}
            </div>
            <div class="min-w-0">
                <h2 @class([
                    'font-tertiary text-lg font-semibold',
                    'text-red-800' => $danger,
                    'text-neutral-900' => !$danger,
                ])>
                    {{ $title }}
                </h2>
                @if ($subtitle)
                    <p class="mt-0.5 font-main text-sm text-tertiary">
                        {{ $subtitle }}
                    </p>
                @endif
            </div>
        </div>

        @isset($action)
            <div class="shrink-0">{{ $action }}</div>
        @endisset
    </div>
</div>
