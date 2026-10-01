@php
    $pills = ['active' => __('Active'), 'inactive' => __('Deactivated'), 'all' => __('All')];
    $tabs = collect($pills)->map(fn ($label, $key) => ['label' => $label, 'count' => $counts[$key], 'on' => $status === $key, 'url' => route('admin.staff.index', array_filter(['status' => $key, 'q' => $term, 'sort' => request('sort'), 'dir' => request('dir')]))])->values()->all();
    $chips = [$term !== '' ? ['label' => __('Search: :term', ['term' => $term]), 'remove' => ['q']] : null];
    $filtered = $term !== '' || $status !== 'active';
    $columns = [
        ['key' => 'name', 'label' => 'Name', 'sort' => 'name'],
        ['key' => 'roles', 'label' => 'Roles', 'class' => 'hidden sm:table-cell'],
        ['key' => 'seen', 'label' => 'Last signed in', 'sort' => 'seen', 'first' => 'desc', 'align' => 'right'],
    ];
@endphp
<x-staff-layout :title="__('Staff')">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <x-staff.header :title="__('Staff')" :description="__('Everyone with access to this portal. Staff have their own accounts, separate from members, and what they can do comes from their roles.')">
            <x-slot:actions><x-btn href="{{ route('admin.staff.create') }}" wire:navigate><x-icon name="plus" class="h-4 w-4" />{{ __('Invite staff') }}</x-btn></x-slot:actions>
        </x-staff.header>

        <x-staff.toolbar :tabs="$tabs" :search="$term" :placeholder="__('Name or email')" :chips="$chips" :action="route('admin.staff.index')" />

        @if ($members->isEmpty())
            <x-empty-state icon="users" :title="$filtered ? __('No staff match') : __('No staff yet')" :description="$term !== '' ? __('No staff match that search.') : __('No staff accounts match this filter.')">
                @if ($filtered)<x-btn variant="secondary" :href="route('admin.staff.index')" wire:navigate>{{ __('Clear filters') }}</x-btn>@endif
            </x-empty-state>
        @else
            <x-staff.table :columns="$columns" :sort="$sort" :dir="$dir" :paginator="$members" :summary="trans_choice(':count account|:count accounts', $members->total(), ['count' => number_format($members->total())])">
                @foreach ($members as $member)
                    <x-staff.row :href="route('admin.staff.edit', $member)">
                        <td class="px-4">
                            <a href="{{ route('admin.staff.edit', $member) }}" wire:navigate class="flex items-center gap-3 focus:outline-none focus-visible:underline">
                                <x-user-avatar :user="$member" size="h-9 w-9" />
                                <span class="min-w-0">
                                    <span class="flex flex-wrap items-center gap-2 font-semibold text-neutral-900">{{ $member->name }}@unless ($member->is_active)<x-badge tone="red" class="px-2 py-0.5 text-xs font-medium">{{ __('Deactivated') }}</x-badge>@endunless</span>
                                    <span class="block truncate text-xs font-normal text-tertiary">{{ $member->email }}</span>
                                </span>
                            </a>
                        </td>
                        <td class="hidden px-4 sm:table-cell"><span class="flex flex-wrap gap-1.5">@forelse ($member->roles as $role)<span class="rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs font-medium text-neutral-700">{{ $role->name }}</span>@empty<span class="text-xs text-tertiary">{{ __('No role') }}</span>@endforelse</span></td>
                        <td class="whitespace-nowrap px-4 text-right text-neutral-600">{{ $member->last_login_at ? $member->last_login_at->diffForHumans() : __('Never') }}</td>
                    </x-staff.row>
                @endforeach
            </x-staff.table>
        @endif
    </div>
</x-staff-layout>
