<x-guest-layout>
    <x-auth-layout :title="__('Two-step verification')" :subtitle="$method === 'authenticator' ? __('Confirm it is you with your authenticator app.') : __('Confirm it is you with the code we emailed.')">
        <x-auth-session-status class="mb-6" :status="session('status')" />
        <x-two-factor-form :method="$method" :email="$email" :routes="$routes" :can-trust="$canTrust" :trust-days="$trustDays" />
    </x-auth-layout>
</x-guest-layout>
