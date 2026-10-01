@props(['method', 'email' => null, 'routes', 'canTrust' => false, 'trustDays' => 30, 'staff' => false])

{{-- The sign-in code step: one field for the 6-digit code (from the app, or emailed) or a recovery code, an optional "trust this device", and resend/cancel. --}}
@php
    $input = $staff
        ? 'block w-full rounded-xl border border-neutral-300 px-4 py-3 text-center text-xl font-semibold tracking-[0.35em] focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25'
        : 'block w-full rounded-xl border bg-neutral-50 px-4 py-3 text-center text-xl font-semibold tracking-[0.35em] focus:bg-white focus:outline-none focus:ring-2 focus:ring-secondary/30 '.($errors->has('code') ? 'border-red-400' : 'border-neutral-200 focus:border-secondary');
    $masked = $email ? \App\Support\Staff\Masking::email($email, false) : null;
@endphp

<form method="POST" action="{{ route($routes['verify']) }}" class="space-y-5">
    @csrf
    <div>
        <label for="code" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ $method === 'authenticator' ? __('Authenticator code') : __('Verification code') }}</label>
        <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" autofocus required maxlength="32" placeholder="000000" aria-describedby="code-hint {{ $errors->has('code') ? 'code-error' : '' }}" class="{{ $input }}">
        @error('code')<p id="code-error" class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
        <p id="code-hint" class="mt-1.5 text-xs text-tertiary">
            @if ($method === 'authenticator')
                {{ __('Open your authenticator app and enter the 6-digit code for :app. Lost your phone? Enter one of your recovery codes instead.', ['app' => config('app.name')]) }}
            @else
                {{ __('We emailed a 6-digit code to :email. It expires in 5 minutes.', ['email' => $masked]) }}
            @endif
        </p>
    </div>

    @if ($canTrust)
        <label class="flex items-center gap-3 text-sm text-neutral-700"><input type="checkbox" name="trust_device" value="1" class="h-4 w-4 rounded border-neutral-300 text-teal-600 focus:ring-secondary/40">{{ __('Trust this device for :days days', ['days' => $trustDays]) }}</label>
    @endif

    <x-btn type="submit" block size="lg">{{ __('Verify and sign in') }}</x-btn>
</form>

<div class="mt-3 grid {{ $method === 'email' ? 'grid-cols-2' : 'grid-cols-1' }} gap-3">
    @if ($method === 'email')
        <form method="POST" action="{{ route($routes['resend']) }}">@csrf<x-btn type="submit" block size="lg" variant="secondary">{{ __('Resend code') }}</x-btn></form>
    @endif
    <form method="POST" action="{{ route($routes['cancel']) }}">@csrf<x-btn type="submit" block size="lg" variant="secondary">{{ __('Cancel') }}</x-btn></form>
</div>
