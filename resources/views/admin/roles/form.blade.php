@use('App\Support\Staff\StaffAccess')
@php
    $locked = $role && $role->name === StaffAccess::SUPER_ADMIN;
    $field = 'block w-full rounded-xl border border-neutral-300 bg-white px-4 py-2.5 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25 disabled:bg-neutral-50 disabled:text-neutral-500';
    $held = old('permissions', $granted);
@endphp
<x-staff-layout :title="$role ? $role->name : __('New role')">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <a href="{{ route('admin.roles.index') }}" class="mb-4 inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('All roles') }}</a>
        <x-staff.header class="!mb-0" :title="$role ? $role->name : __('New role')">@if ($locked){{ __('Super admin always holds every permission, including ones added later, so it cannot be edited or deleted.') }}@endif</x-staff.header>

        <form method="POST" action="{{ $role ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="mt-6 space-y-6">
            @csrf
            @if ($role) @method('PATCH') @endif

            <div class="grid grid-cols-1 items-start gap-6 md:grid-cols-2 xl:grid-cols-3">
            <x-panel :title="__('About the role')">
                <div class="space-y-4">
                    <div><label for="name" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('Name') }}</label><input id="name" name="name" value="{{ old('name', $role?->name) }}" required maxlength="60" @disabled($locked) class="{{ $field }}">@error('name')<p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror</div>
                    <div><label for="description" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ __('What it is for') }}</label><input id="description" name="description" value="{{ old('description', $role?->description) }}" maxlength="255" @disabled($locked) class="{{ $field }}">@error('description')<p class="mt-1.5 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror</div>
                </div>
            </x-panel>

            @foreach ($catalogue as $area => $permissions)
                <x-panel :title="__($area)">
                    <ul class="space-y-3">
                        @foreach ($permissions as $name => $info)
                            <li>
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-neutral-200 p-3.5 transition-colors hover:bg-neutral-50 has-[:checked]:border-teal-600 has-[:checked]:bg-teal-50/40 {{ $locked ? 'cursor-default' : '' }}">
                                    <input type="checkbox" name="permissions[]" value="{{ $name }}" @checked($locked || in_array($name, $held, true)) @disabled($locked) class="mt-0.5 h-4 w-4 rounded border-neutral-300 text-teal-600 focus:ring-teal-600/30">
                                    <span class="min-w-0"><span class="block text-sm font-semibold text-neutral-900">{{ __($info['label']) }}</span><span class="block text-xs text-tertiary">{{ __($info['description']) }}</span></span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </x-panel>
            @endforeach
            </div>
            @error('permissions.*')<p class="text-sm text-red-600" role="alert">{{ $message }}</p>@enderror

            @unless ($locked)
                <div class="flex gap-3"><x-btn type="submit">{{ $role ? __('Save role') : __('Create role') }}</x-btn><x-btn variant="secondary" href="{{ route('admin.roles.index') }}">{{ __('Cancel') }}</x-btn></div>
            @endunless
        </form>
    </div>
</x-staff-layout>
