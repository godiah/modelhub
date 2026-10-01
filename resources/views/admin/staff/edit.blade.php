@php $isMe = $member->is(auth()->user()); @endphp
<x-staff-layout :title="$member->name">
    <div class="container mx-auto max-w-7xl space-y-6 px-4 py-8" x-data="{ deactivating: false }">
        <a href="{{ route('admin.staff.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('All staff') }}</a>

        <x-card class="rounded-2xl">
            <div class="flex flex-wrap items-center gap-4 p-6">
                <x-user-avatar :user="$member" size="h-14 w-14" />
                <div class="min-w-0 flex-1">
                    <h1 class="flex flex-wrap items-center gap-2 font-tertiary text-xl font-semibold text-neutral-900">{{ $member->name }}@if ($isMe)<x-badge tone="neutral" class="px-2 py-0.5 text-xs font-medium">{{ __('You') }}</x-badge>@endif @unless ($member->is_active)<x-badge tone="red" class="px-2 py-0.5 text-xs font-medium">{{ __('Deactivated') }}</x-badge>@endunless</h1>
                    <p class="text-sm text-tertiary">{{ $member->email }} · {{ $member->last_login_at ? __('signed in :when', ['when' => $member->last_login_at->diffForHumans()]) : __('has not signed in yet') }}</p>
                </div>
            </div>
        </x-card>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('admin.staff.update', $member) }}">
            @csrf @method('PATCH')
            <x-panel :title="__('Roles')" :description="__('What this person can do. Changes take effect straight away.')">
                @include('admin.staff.partials.role-checkboxes', ['roles' => $roles, 'held' => $member->roles->pluck('name')->all()])
                <x-slot:footer><x-btn type="submit">{{ __('Save roles') }}</x-btn></x-slot:footer>
            </x-panel>
        </form>

        <x-panel :title="__('Account')">
            <div class="flex flex-wrap gap-3">
                @if ($member->is_active)
                    <form method="POST" action="{{ route('admin.staff.invite', $member) }}">@csrf<x-btn type="submit" variant="secondary">{{ __('Send a new password link') }}</x-btn></form>
                    @if (! $isMe && $member->hasAuthenticator())<form method="POST" action="{{ route('admin.staff.two-factor-reset', $member) }}">@csrf<x-btn type="submit" variant="secondary">{{ __('Reset two-step sign-in') }}</x-btn></form>@endif
                    @unless ($isMe)<x-btn type="button" variant="danger-outline" @click="deactivating = true">{{ __('Deactivate account') }}</x-btn>@endunless
                @else
                    <form method="POST" action="{{ route('admin.staff.reactivate', $member) }}">@csrf<x-btn type="submit">{{ __('Reactivate account') }}</x-btn></form>
                @endif
            </div>
            @if ($member->is_active && ! $isMe)<p class="mt-3 text-xs text-tertiary">{{ __('Deactivating signs them out and stops them signing in. Their history stays in the activity log.') }}</p>@endif
        </x-panel>

        </div>

        <x-confirm-dialog bind="deactivating" title="Deactivate this account" confirm-label="Deactivate" :action="route('admin.staff.deactivate', $member)" message="They are signed out and can no longer sign in. You can reactivate the account later." />
    </div>
</x-staff-layout>
