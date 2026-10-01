@php $field = 'block w-full rounded-xl border border-neutral-300 bg-white px-4 py-2.5 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25'; @endphp
<x-staff-layout :title="__('Invite staff')">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <a href="{{ route('admin.staff.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('All staff') }}</a>
        <x-staff.header class="!mb-0" :title="__('Invite staff')">{{ __('They get an email with a link to set their own password. Nothing is shared with them over chat or email.') }}</x-staff.header>

        <form method="POST" action="{{ route('admin.staff.store') }}" class="mt-6 space-y-6">
            @csrf
            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-2">
            <x-panel :title="__('Who')">
                <div class="space-y-4">
                    <div><label for="name" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Full name') }}</label><input id="name" name="name" value="{{ old('name') }}" required class="{{ $field }}">@error('name')<p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror</div>
                    <div><label for="email" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Email address') }}</label><input id="email" type="email" name="email" value="{{ old('email') }}" required class="{{ $field }}">@error('email')<p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror</div>
                </div>
            </x-panel>
            <x-panel :title="__('Roles')" :description="__('What they can do. You can change this at any time.')">
                @include('admin.staff.partials.role-checkboxes', ['roles' => $roles, 'held' => old('roles', [])])
            </x-panel>
            </div>
            <div class="flex gap-3"><x-btn type="submit">{{ __('Send invitation') }}</x-btn><x-btn variant="secondary" href="{{ route('admin.staff.index') }}">{{ __('Cancel') }}</x-btn></div>
        </form>
    </div>
</x-staff-layout>
