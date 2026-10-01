<section aria-labelledby="feed-members">
    <x-card clip>
        <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4"><h2 id="feed-members" class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Members') }}</h2><a href="{{ route('admin.members.index') }}" class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('Open directory') }}</a></div>
        <div class="p-5">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('Newest sign-ups') }}</h3>
            <ul class="mt-2 divide-y divide-neutral-100">
                @foreach ($feed['newest'] as $member)
                    <li><a href="{{ route('admin.members.show', $member) }}" class="flex items-center gap-3 py-2.5 text-sm hover:text-teal-700"><x-user-avatar :user="$member" size="h-8 w-8" /><span class="min-w-0 flex-1 truncate font-medium text-neutral-900">{{ $member->name }}</span>@unless ($member->email_verified_at)<x-badge tone="amber" class="px-2 py-0.5 text-xs font-medium">{{ __('Unverified') }}</x-badge>@endunless<span class="shrink-0 text-xs text-tertiary">{{ $member->created_at->diffForHumans() }}</span></a></li>
                @endforeach
            </ul>
            @if ($feed['suspended']->isNotEmpty())
                <h3 class="mt-5 text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('Recently suspended') }}</h3>
                <ul class="mt-2 divide-y divide-neutral-100">
                    @foreach ($feed['suspended'] as $member)
                        <li><a href="{{ route('admin.members.show', $member) }}" class="flex items-center justify-between gap-3 py-2.5 text-sm hover:text-teal-700"><span class="min-w-0 truncate font-medium text-neutral-900">{{ $member->name }}</span><span class="shrink-0 text-xs text-tertiary">{{ $member->suspended_at->diffForHumans() }}@if ($member->suspendedBy) · {{ $member->suspendedBy->name }}@endif</span></a></li>
                    @endforeach
                </ul>
            @endif
            @if ($feed['unverified'] > 0)<p class="mt-4 text-xs text-tertiary">{{ trans_choice(':count member has not verified their email|:count members have not verified their email', $feed['unverified'], ['count' => $feed['unverified']]) }}</p>@endif
        </div>
    </x-card>
</section>
