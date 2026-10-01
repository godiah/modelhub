@use('App\Support\Staff\StaffAccess')
<x-staff-layout :title="__('Roles')">
    <div class="container mx-auto max-w-7xl px-4 py-8" x-data="{ deleting: false, target: { name: '', url: '' } }" @delete-role.window="target = $event.detail; deleting = true">
        <x-staff.header :title="__('Roles')">{{ __('A role is a set of permissions. Give staff one or several; what they can do is everything their roles allow.') }}
            <x-slot:actions><x-btn href="{{ route('admin.roles.create') }}"><x-icon name="plus" class="h-4 w-4" />{{ __('New role') }}</x-btn></x-slot:actions>
        </x-staff.header>

        <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-2">
            @foreach ($roles as $role)
                @php $locked = $role->name === StaffAccess::SUPER_ADMIN; @endphp
                <x-card class="h-full rounded-2xl">
                    <div class="flex flex-wrap items-center gap-4 p-5">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-50 text-teal-700"><x-icon :name="$locked ? 'shield-check' : 'users'" class="h-5 w-5" /></span>
                        <div class="min-w-0 flex-1">
                            <h2 class="flex flex-wrap items-center gap-2 text-base font-semibold text-neutral-900">{{ $role->name }}@if ($locked)<x-badge tone="neutral" class="px-2 py-0.5 text-xs font-medium">{{ __('Locked') }}</x-badge>@endif</h2>
                            <p class="text-sm text-tertiary">{{ $role->description ?: __('No description.') }}</p>
                            <p class="mt-1 text-xs text-tertiary">{{ trans_choice(':count permission|:count permissions', $role->permissions_count, ['count' => $role->permissions_count]) }} · {{ trans_choice(':count person|:count people', $role->users_count, ['count' => $role->users_count]) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-btn size="sm" variant="secondary" href="{{ route('admin.roles.edit', $role) }}">{{ $locked ? __('View') : __('Edit') }}</x-btn>
                            @unless ($locked)
                                <x-btn size="sm" variant="danger-outline" type="button" @click="$dispatch('delete-role', {{ \Illuminate\Support\Js::from(['name' => $role->name, 'url' => route('admin.roles.destroy', $role)]) }})">{{ __('Delete') }}</x-btn>
                            @endunless
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>

        <x-confirm-dialog bind="deleting" title="Delete this role" confirm-label="Delete" method="DELETE" action-bind="target.url" message="Staff who hold it must have it taken off first. This cannot be undone." />
    </div>
</x-staff-layout>
