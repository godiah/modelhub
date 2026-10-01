@use('App\Support\Staff\Masking')
@php $statuses = \App\Http\Controllers\Admin\AdminMemberController::STATUSES; $activities = \App\Http\Controllers\Admin\AdminMemberController::ACTIVITY; @endphp
<x-staff-layout :title="__('Members')">
    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="mb-6">
            <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('Members') }}</h1>
            <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ __('Everyone with an account on the platform: buyers, sellers, clients and freelancers.') }}@unless ($canSeeContact) {{ __('Email addresses and phone numbers are masked for your role.') }}@endunless</p>
        </div>

        <nav aria-label="{{ __('Filter by status') }}" class="-mx-4 mb-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            <ul class="flex min-w-max items-center gap-2">
                @foreach ($statuses as $key => $label)
                    @php $on = $status === $key; @endphp
                    <li><a href="{{ route('admin.members.index', array_filter(['status' => $key, 'activity' => $activity !== 'all' ? $activity : null, 'q' => $term])) }}" @if ($on) aria-current="true" @endif
                        @class(['inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40', 'border-teal-600 bg-teal-600 text-white' => $on, 'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300 hover:bg-neutral-50' => ! $on])>
                        {{ __($label) }}<span @class(['text-xs tabular-nums', 'text-teal-100' => $on, 'text-tertiary' => ! $on])>{{ number_format($counts[$key]) }}</span></a></li>
                @endforeach
            </ul>
        </nav>

        <form method="GET" action="{{ route('admin.members.index') }}" role="search" class="mb-5 flex flex-wrap items-end gap-3">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="min-w-[14rem] flex-1">
                <label for="q" class="sr-only">{{ __('Search members') }}</label>
                <input id="q" type="search" name="q" value="{{ $term }}" placeholder="{{ $canSeeContact ? __('Search by name or email') : __('Search by name') }}" class="block w-full rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
            </div>
            <div>
                <label for="activity" class="sr-only">{{ __('Activity') }}</label>
                <select id="activity" name="activity" onchange="this.form.requestSubmit()" class="rounded-xl border border-neutral-300 bg-white px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">
                    @foreach ($activities as $key => $label)<option value="{{ $key }}" @selected($activity === $key)>{{ __($label) }}</option>@endforeach
                </select>
            </div>
            <x-btn type="submit" variant="secondary">{{ __('Search') }}</x-btn>
        </form>

        @if ($members->isEmpty())
            <x-empty-state icon="user-group" :title="__('Nobody here')" :description="__('No members match this search.')" />
        @else
            <x-card clip>
                <ul class="divide-y divide-neutral-100">
                    @foreach ($members as $member)
                        <li>
                            <a href="{{ route('admin.members.show', $member) }}" class="flex flex-wrap items-center gap-4 px-5 py-4 hover:bg-neutral-50 focus:outline-none focus-visible:bg-neutral-50">
                                <x-user-avatar :user="$member" size="h-10 w-10" />
                                <div class="min-w-0 flex-1">
                                    <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-neutral-900">{{ $member->name }}
                                        @if ($member->isSuspended())<x-badge tone="red" class="px-2 py-0.5 text-xs font-medium">{{ __('Suspended') }}</x-badge>@endif
                                        @unless ($member->email_verified_at)<x-badge tone="amber" class="px-2 py-0.5 text-xs font-medium">{{ __('Unverified') }}</x-badge>@endunless
                                        @if ($member->sellerProfile?->status->value === 'approved')<x-badge tone="blue" class="px-2 py-0.5 text-xs font-medium">{{ __('Seller') }}</x-badge>@endif
                                    </p>
                                    <p class="truncate text-xs text-tertiary">{{ Masking::email($member->email, $canSeeContact) }}</p>
                                </div>
                                <p class="hidden gap-4 text-xs tabular-nums text-tertiary md:flex">
                                    <span title="{{ __('Projects posted') }}">{{ $member->jobs_count }} {{ __('projects') }}</span>
                                    <span title="{{ __('Applications sent') }}">{{ $member->job_applications_count }} {{ __('applications') }}</span>
                                    <span title="{{ __('Models listed') }}">{{ $member->products_count }} {{ __('models') }}</span>
                                </p>
                                <p class="w-36 shrink-0 text-right text-xs text-tertiary">
                                    {{ __('Joined :date', ['date' => $member->created_at->format('M j, Y')]) }}<br>
                                    {{ $member->last_login_at ? __('Seen :when', ['when' => $member->last_login_at->diffForHumans()]) : __('Never signed in') }}
                                </p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-card>
            <x-pager :paginator="$members" />
        @endif
    </div>
</x-staff-layout>
