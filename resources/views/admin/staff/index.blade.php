@php $pills = ['active' => __('Active'), 'inactive' => __('Deactivated'), 'all' => __('All')]; @endphp
<x-staff-layout :title="__('Staff')">
    <div class="container mx-auto max-w-5xl px-4 py-8">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('Staff') }}</h1>
                <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ __('Everyone with access to this portal. Staff have their own accounts, separate from members, and what they can do comes from their roles.') }}</p>
            </div>
            <x-btn href="{{ route('admin.staff.create') }}"><x-icon name="plus" class="h-4 w-4" />{{ __('Invite staff') }}</x-btn>
        </div>

        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <nav aria-label="{{ __('Filter by status') }}" class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                <ul class="flex min-w-max items-center gap-2">
                    @foreach ($pills as $key => $label)
                        @php $on = $status === $key; @endphp
                        <li><a href="{{ route('admin.staff.index', array_filter(['status' => $key, 'q' => $term])) }}" @if ($on) aria-current="true" @endif
                            @class(['inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40', 'border-teal-600 bg-teal-600 text-white' => $on, 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300 hover:bg-neutral-50' => ! $on])>
                            {{ $label }}<span @class(['text-xs tabular-nums', 'text-teal-100' => $on, 'text-tertiary' => ! $on])>{{ $counts[$key] }}</span></a></li>
                    @endforeach
                </ul>
            </nav>
            <form method="GET" action="{{ route('admin.staff.index') }}" role="search" class="flex gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <label for="q" class="sr-only">{{ __('Search staff') }}</label>
                <input id="q" type="search" name="q" value="{{ $term }}" placeholder="{{ __('Name or email') }}" class="w-56 rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
                <x-btn type="submit" variant="secondary" size="sm">{{ __('Search') }}</x-btn>
            </form>
        </div>

        @if ($members->isEmpty())
            <x-empty-state icon="users" :title="__('Nobody here')" :description="$term !== '' ? __('No staff match that search.') : __('No staff accounts match this filter.')" />
        @else
            <x-card clip>
                <ul class="divide-y divide-neutral-100">
                    @foreach ($members as $member)
                        <li>
                            <a href="{{ route('admin.staff.edit', $member) }}" class="flex flex-wrap items-center gap-4 px-5 py-4 hover:bg-neutral-50 focus:outline-none focus-visible:bg-neutral-50">
                                <x-user-avatar :user="$member" size="h-10 w-10" />
                                <div class="min-w-0 flex-1">
                                    <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-neutral-900">{{ $member->name }}@unless ($member->is_active)<x-badge tone="red" class="px-2 py-0.5 text-xs font-medium">{{ __('Deactivated') }}</x-badge>@endunless</p>
                                    <p class="truncate text-xs text-tertiary">{{ $member->email }}</p>
                                </div>
                                <p class="flex flex-wrap gap-1.5">@forelse ($member->roles as $role)<span class="rounded-full bg-neutral-100 px-2.5 py-0.5 text-xs font-medium text-neutral-700">{{ $role->name }}</span>@empty<span class="text-xs text-tertiary">{{ __('No role') }}</span>@endforelse</p>
                                <p class="w-32 shrink-0 text-right text-xs text-tertiary">{{ $member->last_login_at ? __('Signed in :when', ['when' => $member->last_login_at->diffForHumans()]) : __('Never signed in') }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-card>
            <x-pager :paginator="$members" />
        @endif
    </div>
</x-staff-layout>
