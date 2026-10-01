@use('App\Support\Staff\Masking')
@php
    $statuses = \App\Http\Controllers\Admin\AdminMemberController::STATUSES;
    $activities = \App\Http\Controllers\Admin\AdminMemberController::ACTIVITY;
    $tabs = collect($statuses)->map(fn ($label, $key) => ['label' => $label, 'count' => $counts[$key], 'on' => $status === $key, 'url' => route('admin.members.index', array_filter(['status' => $key, 'activity' => $activity !== 'all' ? $activity : null, 'q' => $term, 'sort' => request('sort'), 'dir' => request('dir')]))])->values()->all();
    $chips = [$term !== '' ? ['label' => __('Search: :term', ['term' => $term]), 'remove' => ['q']] : null, $activity !== 'all' ? ['label' => __($activities[$activity]), 'remove' => ['activity']] : null];
    $filtered = $term !== '' || $activity !== 'all' || $status !== 'all';
    $columns = [
        ['key' => 'name', 'label' => 'Member', 'sort' => 'name'],
        ['key' => 'projects', 'label' => 'Projects', 'sort' => 'projects', 'first' => 'desc', 'align' => 'right', 'class' => 'hidden lg:table-cell'],
        ['key' => 'applications', 'label' => 'Applications', 'sort' => 'applications', 'first' => 'desc', 'align' => 'right', 'class' => 'hidden lg:table-cell'],
        ['key' => 'models', 'label' => 'Models', 'sort' => 'models', 'first' => 'desc', 'align' => 'right', 'class' => 'hidden lg:table-cell'],
        ['key' => 'joined', 'label' => 'Joined', 'sort' => 'joined', 'first' => 'desc', 'class' => 'hidden sm:table-cell'],
        ['key' => 'seen', 'label' => 'Last seen', 'sort' => 'seen', 'first' => 'desc', 'align' => 'right'],
    ];
@endphp
<x-staff-layout :title="__('Members')">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <x-staff.header :title="__('Members')" :description="__('Everyone with an account on the platform: buyers, sellers, clients and freelancers.').($canSeeContact ? '' : ' '.__('Email addresses and phone numbers are masked for your role.'))" />

        <x-staff.toolbar :tabs="$tabs" :search="$term" :placeholder="$canSeeContact ? __('Search by name or email') : __('Search by name')" :chips="$chips" :action="route('admin.members.index')">
            <label for="activity" class="sr-only">{{ __('Activity') }}</label>
            <select id="activity" name="activity" onchange="this.form.requestSubmit()" class="rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
                @foreach ($activities as $key => $label)<option value="{{ $key }}" @selected($activity === $key)>{{ __($label) }}</option>@endforeach
            </select>
        </x-staff.toolbar>

        @if ($members->isEmpty())
            <x-empty-state icon="user-group" :title="$filtered ? __('No members match') : __('No members yet')" :description="$filtered ? __('Try a different search or clear the filters.') : __('People appear here as they sign up.')">
                @if ($filtered)<x-btn variant="secondary" :href="route('admin.members.index')" wire:navigate>{{ __('Clear filters') }}</x-btn>@endif
            </x-empty-state>
        @else
            <x-staff.table :columns="$columns" :sort="$sort" :dir="$dir" :paginator="$members" :summary="trans_choice(':count member|:count members', $members->total(), ['count' => number_format($members->total())])">
                @foreach ($members as $member)
                    <x-staff.row :href="route('admin.members.show', $member)">
                        <td class="px-4">
                            <a href="{{ route('admin.members.show', $member) }}" wire:navigate class="flex items-center gap-3 focus:outline-none focus-visible:underline">
                                <x-user-avatar :user="$member" size="h-9 w-9" />
                                <span class="min-w-0">
                                    <span class="flex flex-wrap items-center gap-2 font-semibold text-neutral-900">{{ $member->name }}
                                        @if ($member->isSuspended())<x-badge tone="red" class="px-2 py-0.5 text-xs font-medium">{{ __('Suspended') }}</x-badge>@endif
                                        @unless ($member->email_verified_at)<x-badge tone="amber" class="px-2 py-0.5 text-xs font-medium">{{ __('Unverified') }}</x-badge>@endunless
                                        @if ($member->sellerProfile?->status->value === 'approved')<x-badge tone="blue" class="px-2 py-0.5 text-xs font-medium">{{ __('Seller') }}</x-badge>@endif
                                    </span>
                                    <span class="block truncate text-xs font-normal text-tertiary">{{ Masking::email($member->email, $canSeeContact) }}</span>
                                </span>
                            </a>
                        </td>
                        <td class="hidden px-4 text-right tabular-nums text-neutral-700 lg:table-cell">{{ $member->jobs_count }}</td>
                        <td class="hidden px-4 text-right tabular-nums text-neutral-700 lg:table-cell">{{ $member->job_applications_count }}</td>
                        <td class="hidden px-4 text-right tabular-nums text-neutral-700 lg:table-cell">{{ $member->products_count }}</td>
                        <td class="hidden whitespace-nowrap px-4 text-neutral-600 sm:table-cell">{{ $member->created_at->format('M j, Y') }}</td>
                        <td class="whitespace-nowrap px-4 text-right text-neutral-600">{{ $member->last_login_at ? $member->last_login_at->diffForHumans() : __('Never') }}</td>
                    </x-staff.row>
                @endforeach
            </x-staff.table>
        @endif
    </div>
</x-staff-layout>
