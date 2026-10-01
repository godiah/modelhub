@props(['account', 'prefix'])

{{-- Authenticator app setup for the signed-in account (member profile, staff account page). $prefix is the route-name prefix of the five authenticator routes. --}}
@php
    use App\Support\Auth\Totp;
    use App\Support\Settings\PlatformSettings;

    $allowed = PlatformSettings::bool('security.authenticator_allowed');
    $enrolled = $account->hasAuthenticator();
    $pending = $account->authenticatorSetupPending();
    $field = 'block w-full rounded-xl border border-neutral-300 bg-white px-4 py-2.5 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25';
    $recovery = session('recovery_codes');
@endphp

@if ($allowed || $enrolled)
    <x-panel :title="__('Authenticator app')" :description="__('Get your sign-in codes from an app such as Google Authenticator, Authy or 1Password instead of email.')" id="authenticator">
        <div class="space-y-5" x-data="{ confirming: null }">
            @if ($recovery)
                <div class="rounded-xl border border-amber-300 bg-amber-50 p-5" role="alert" x-data="{ copied: false }">
                    <p class="text-sm font-semibold text-amber-900">{{ __('Save your recovery codes') }}</p>
                    <p class="mt-1 text-sm text-amber-900">{{ __('Each code works once if you lose your phone. This is the only time they are shown, so store them somewhere safe.') }}</p>
                    <ul class="mt-3 grid grid-cols-2 gap-x-6 gap-y-1 rounded-lg bg-white px-4 py-3 font-mono text-sm text-neutral-900 sm:max-w-sm" id="recovery-codes">
                        @foreach ($recovery as $code)<li>{{ $code }}</li>@endforeach
                    </ul>
                    <button type="button" class="mt-3 text-sm font-medium text-amber-900 underline" x-on:click="navigator.clipboard.writeText(@js(implode("\n", $recovery))).then(() => copied = true)"><span x-show="!copied">{{ __('Copy codes') }}</span><span x-show="copied" x-cloak>{{ __('Copied') }}</span></button>
                </div>
            @endif

            @if ($enrolled)
                <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-teal-50 text-teal-700"><x-icon name="phone" class="h-6 w-6" /></span>
                        <div>
                            <p class="text-sm font-semibold text-neutral-900">{{ __('Authenticator app is on') }}</p>
                            <p class="text-sm text-tertiary">{{ trans_choice(':count recovery code left|:count recovery codes left', $account->recoveryCodesRemaining(), ['count' => $account->recoveryCodesRemaining()]) }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-btn type="button" variant="secondary" x-on:click="confirming = confirming === 'recovery' ? null : 'recovery'">{{ __('New recovery codes') }}</x-btn>
                        <x-btn type="button" variant="danger-outline" x-on:click="confirming = confirming === 'remove' ? null : 'remove'">{{ __('Remove') }}</x-btn>
                    </div>
                </div>

                @unless ($allowed)
                    <p class="rounded-xl bg-neutral-50 px-4 py-3 text-sm text-neutral-700">{{ __('The platform is not using authenticator apps right now, so sign-in codes are emailed to you. Your app is kept in case that changes.') }}</p>
                @endunless

                <form method="POST" action="{{ route($prefix.'.recovery') }}" x-show="confirming === 'recovery'" x-cloak class="space-y-3 rounded-xl border border-neutral-200 bg-neutral-50/60 p-5">
                    @csrf
                    <p class="text-sm text-neutral-700">{{ __('This replaces your old recovery codes. Confirm your password to continue.') }}</p>
                    <div><label for="recovery_password" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Password') }}</label><input id="recovery_password" type="password" name="password" required autocomplete="current-password" class="{{ $field }}"></div>
                    <x-btn type="submit">{{ __('Make new codes') }}</x-btn>
                </form>

                <form method="POST" action="{{ route($prefix.'.destroy') }}" x-show="confirming === 'remove'" x-cloak class="space-y-3 rounded-xl border border-red-200 bg-red-50/40 p-5">
                    @csrf @method('DELETE')
                    <p class="text-sm text-red-900">{{ __('You will go back to emailed codes (or none, if the platform does not require them). Confirm your password to remove the app.') }}</p>
                    <div><label for="remove_password" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Password') }}</label><input id="remove_password" type="password" name="password" required autocomplete="current-password" class="{{ $field }}"></div>
                    <x-btn type="submit" variant="danger">{{ __('Remove authenticator app') }}</x-btn>
                </form>
            @elseif ($pending)
                @php $uri = Totp::uri($account->two_factor_secret, $account->email, config('app.name')); @endphp
                <div class="space-y-4 rounded-xl border border-neutral-200 bg-neutral-50/60 p-5">
                    <p class="text-sm font-semibold text-neutral-900">{{ __('Scan this with your app') }}</p>
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                        <div class="w-fit rounded-xl border border-neutral-200 bg-white p-2 [&>svg]:h-44 [&>svg]:w-44" role="img" aria-label="{{ __('QR code for your authenticator app') }}">{!! Totp::qrSvg($uri) !!}</div>
                        <div class="min-w-0 text-sm text-neutral-700">
                            <p>{{ __("Can't scan it? Enter this key in your app instead:") }}</p>
                            <p class="mt-1 break-all rounded-lg bg-white px-3 py-2 font-mono text-sm tracking-wider text-neutral-900">{{ Totp::formatSecret($account->two_factor_secret) }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route($prefix.'.confirm') }}" class="space-y-3">
                        @csrf
                        <div>
                            <label for="authenticator_code" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Then enter the 6-digit code it shows') }}</label>
                            <input id="authenticator_code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="7" required placeholder="000000" class="{{ $field }} max-w-[12rem] text-center text-lg font-semibold tracking-[0.3em]">
                            @error('code')<p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <x-btn type="submit">{{ __('Turn on') }}</x-btn>
                            <x-btn type="submit" variant="secondary" form="cancel-authenticator">{{ __('Cancel') }}</x-btn>
                        </div>
                    </form>
                    <form id="cancel-authenticator" method="POST" action="{{ route($prefix.'.cancel') }}" class="hidden">@csrf @method('DELETE')</form>
                </div>
            @else
                <div class="flex flex-col gap-4 rounded-xl border border-neutral-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-neutral-500"><x-icon name="phone" class="h-6 w-6" /></span>
                        <div><p class="text-sm font-semibold text-neutral-900">{{ __('Not set up') }}</p><p class="text-sm text-tertiary">{{ __('Codes come from the app and work without a signal.') }}</p></div>
                    </div>
                    <x-btn type="button" x-on:click="confirming = confirming === 'start' ? null : 'start'">{{ __('Set up') }}</x-btn>
                </div>

                <form method="POST" action="{{ route($prefix.'.start') }}" x-show="confirming === 'start'" x-cloak class="space-y-3 rounded-xl border border-neutral-200 bg-neutral-50/60 p-5">
                    @csrf
                    <p class="text-sm text-neutral-700">{{ __('Confirm your password, then scan a QR code with your app.') }}</p>
                    <div><label for="start_password" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Password') }}</label><input id="start_password" type="password" name="password" required autocomplete="current-password" class="{{ $field }}"></div>
                    <x-btn type="submit">{{ __('Continue') }}</x-btn>
                </form>
            @endif

            @error('password')<p class="text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
        </div>
    </x-panel>
@endif
