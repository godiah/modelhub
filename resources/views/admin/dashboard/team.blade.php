<section aria-labelledby="team-heading">
    <x-card clip>
        <div class="border-b border-neutral-100 px-5 py-4"><h2 id="team-heading" class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Team and security') }}</h2></div>
        <div class="grid grid-cols-1 gap-6 p-5 md:grid-cols-3">
            @if ($team['canLog'])
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('Decisions this week') }}</h3>
                    @forelse ($team['decisions'] as $row)
                        <p class="mt-2 flex items-center gap-2.5 text-sm"><x-user-avatar :user="$row['staff']" size="h-7 w-7" /><span class="min-w-0 flex-1 truncate text-neutral-900">{{ $row['staff']->name }}</span><span class="font-semibold tabular-nums text-neutral-900">{{ $row['total'] }}</span></p>
                    @empty<p class="mt-2 text-sm text-tertiary">{{ __('Nobody has made a decision this week.') }}</p>@endforelse
                </div>
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('Active today') }}</h3>
                    @forelse ($team['active_today'] as $person)
                        <p class="mt-2 flex items-center gap-2.5 text-sm"><x-user-avatar :user="$person" size="h-7 w-7" /><span class="min-w-0 flex-1 truncate text-neutral-900">{{ $person->name }}</span><span class="text-xs text-tertiary">{{ $person->last_login_at->diffForHumans() }}</span></p>
                    @empty<p class="mt-2 text-sm text-tertiary">{{ __('Nobody has signed in today.') }}</p>@endforelse
                </div>
            @endif
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('Security') }}</h3>
                <ul class="mt-2 space-y-1.5 text-sm text-neutral-700">
                    @if ($team['canLog'])
                        <li><a href="{{ route('admin.activity.index', ['area' => 'staff']) }}" class="hover:text-teal-700"><span class="font-semibold tabular-nums {{ $team['failed_signins'] > 0 ? 'text-red-700' : 'text-neutral-900' }}">{{ $team['failed_signins'] }}</span> {{ __('failed sign-in attempts in the last 24 hours') }}</a></li>
                    @endif
                    @if ($team['canStaff'])
                        <li><a href="{{ route('admin.staff.index') }}" class="hover:text-teal-700"><span class="font-semibold tabular-nums {{ $team['no_role'] > 0 ? 'text-amber-700' : 'text-neutral-900' }}">{{ $team['no_role'] }}</span> {{ __('active staff without a role') }}</a></li>
                        <li><a href="{{ route('admin.staff.index') }}" class="hover:text-teal-700"><span class="font-semibold tabular-nums text-neutral-900">{{ $team['never_signed_in'] }}</span> {{ __('invited staff who never signed in') }}</a></li>
                        <li><a href="{{ route('admin.staff.index', ['status' => 'inactive']) }}" class="hover:text-teal-700"><span class="font-semibold tabular-nums text-neutral-900">{{ $team['deactivated'] }}</span> {{ __('deactivated accounts') }}</a></li>
                    @endif
                </ul>
            </div>
        </div>
    </x-card>
</section>
