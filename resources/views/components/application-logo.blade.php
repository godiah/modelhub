@props(['variant' => 'lockup'])

{{-- 'lockup' = symbol + wordmark (stacked); 'mark' = symbol only. Both are transparent PNGs meant for light backgrounds. --}}
<img src="{{ asset($variant === 'mark' ? 'images/brand/logo-mark.png' : 'images/brand/logo.png') }}"
    alt="{{ config('app.name', 'ModelHub') }}" {{ $attributes }}>
