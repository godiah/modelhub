<x-staff-guest>
    <h1 class="font-tertiary text-xl font-semibold text-neutral-900">{{ __('Two-step verification') }}</h1>
    <p class="mt-1 text-sm text-tertiary">{{ $method === 'authenticator' ? __('Confirm it is you with your authenticator app.') : __('Confirm it is you with the code we emailed.') }}</p>

    @if (session('status'))<p class="mt-4 rounded-xl bg-teal-50 px-4 py-3 text-sm text-teal-900" role="status">{{ session('status') }}</p>@endif

    <div class="mt-6">
        <x-two-factor-form :method="$method" :email="$email" :routes="$routes" :can-trust="$canTrust" :trust-days="$trustDays" staff />
    </div>
</x-staff-guest>
