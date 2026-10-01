@php $field = 'block w-full rounded-xl border border-neutral-300 bg-white px-4 py-2.5 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25'; @endphp
<x-staff-layout :title="__('Your account')">
    <div class="container mx-auto max-w-3xl space-y-6 px-4 py-8">
        <div>
            <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('Your account') }}</h1>
            <p class="mt-1 text-sm text-tertiary">{{ __('Your staff account is separate from any member account you may have.') }}</p>
        </div>

        <x-panel :title="__('Avatar')" :description="__('Shown beside what you do in the activity log.')">
            <x-avatar-picker kind="people" :current="$staff->avatar" :fallback="$staff->id" :action="route('admin.account.avatar')" />
        </x-panel>

        <x-panel :title="__('Details')">
            <form method="POST" action="{{ route('admin.account.update') }}" class="space-y-4">
                @csrf @method('PATCH')
                <div><label for="name" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Name') }}</label><input id="name" name="name" value="{{ old('name', $staff->name) }}" required class="{{ $field }}">@error('name')<p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror</div>
                <div><label class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Email') }}</label><p class="rounded-xl bg-neutral-50 px-4 py-2.5 text-sm text-neutral-700">{{ $staff->email }}</p><p class="mt-1 text-xs text-tertiary">{{ __('A Super admin changes staff email addresses.') }}</p></div>
                <div><label class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Roles') }}</label><p class="flex flex-wrap gap-1.5">@forelse ($staff->roles as $role)<span class="rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs font-medium text-neutral-700">{{ $role->name }}</span>@empty<span class="text-sm text-tertiary">{{ __('None yet') }}</span>@endforelse</p></div>
                <x-btn type="submit">{{ __('Save') }}</x-btn>
            </form>
        </x-panel>

        <x-panel :title="__('Password')" :description="\App\Support\Auth\PasswordPolicy::describe(staff: true)">
            <form method="POST" action="{{ route('admin.account.password') }}" class="space-y-4">
                @csrf @method('PUT')
                <div><label for="current_password" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Current password') }}</label><input id="current_password" type="password" name="current_password" required autocomplete="current-password" class="{{ $field }}">@error('current_password')<p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror</div>
                <div><label for="password" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('New password') }}</label><input id="password" type="password" name="password" required autocomplete="new-password" class="{{ $field }}">@error('password')<p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror</div>
                <div><label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Confirm new password') }}</label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="{{ $field }}"></div>
                <x-btn type="submit">{{ __('Change password') }}</x-btn>
            </form>
        </x-panel>

        <x-authenticator-card :account="$staff" prefix="admin.account.authenticator" />
        @if (\App\Support\Settings\PlatformSettings::bool('security.otp_staff_required'))
            <p class="rounded-xl bg-neutral-50 px-4 py-3 text-sm text-neutral-700">{{ __('A sign-in code is required for all staff. Without an authenticator app, a code is emailed to you at every sign-in.') }}</p>
        @endif
    </div>
</x-staff-layout>
