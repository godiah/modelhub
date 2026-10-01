@php
    $max = max(1, collect($team['decisions'] ?? [])->max('total'));
    $checks = [];

    if ($team['canLog']) {
        $checks[] = ['tone' => $team['failed_signins'] > 0 ? 'red' : 'green', 'value' => $team['failed_signins'], 'text' => __('failed sign-in attempts in the last 24 hours'), 'url' => route('admin.activity.index', ['area' => 'staff'])];
    }
    if ($team['canStaff']) {
        $checks[] = ['tone' => $team['no_role'] > 0 ? 'amber' : 'green', 'value' => $team['no_role'], 'text' => __('active staff without a role'), 'url' => route('admin.staff.index')];
        $checks[] = ['tone' => $team['never_signed_in'] > 0 ? 'amber' : 'green', 'value' => $team['never_signed_in'], 'text' => __('invited staff who never signed in'), 'url' => route('admin.staff.index')];
        $checks[] = ['tone' => 'neutral', 'value' => $team['deactivated'], 'text' => __('deactivated accounts'), 'url' => route('admin.staff.index', ['status' => 'inactive'])];
        $checks[] = ['tone' => $team['with_app'] >= $team['active_staff'] && $team['active_staff'] > 0 ? 'green' : 'amber', 'value' => $team['with_app'].'/'.$team['active_staff'], 'text' => __('staff use an authenticator app'), 'url' => route('admin.staff.index')];
    }
@endphp
<section aria-labelledby="team-heading">
    <x-card clip>
        <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-5 py-4">
            <h2 id="team-heading" class="font-tertiary text-base font-semibold text-neutral-900">{{ __('Team and security') }}</h2>
            @if ($team['canStaff'])<a href="{{ route('admin.staff.index') }}" wire:navigate class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('Manage staff') }}</a>@endif
        </div>

        @if ($team['canLog'])
        <div class="grid grid-cols-1 gap-x-10 gap-y-6 p-5 lg:grid-cols-2">
                <div>
                    <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('Decisions this week') }}</h3>
                    @forelse ($team['decisions'] as $row)
                        <div class="mb-3 last:mb-0">
                            <div class="flex items-center gap-2.5 text-sm">
                                <x-user-avatar :user="$row['staff']" size="h-7 w-7" />
                                <span class="min-w-0 flex-1 truncate font-medium text-neutral-900">{{ $row['staff']->name }}</span>
                                <span class="font-tertiary font-bold tabular-nums text-neutral-900">{{ $row['total'] }}</span>
                            </div>
                            <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-neutral-100"><div @class(['h-full rounded-full', 'bg-teal-500' => $loop->first, 'bg-teal-300/80' => ! $loop->first]) style="width: {{ max(4, round($row['total'] / $max * 100)) }}%"></div></div>
                        </div>
                    @empty
                        <p class="text-sm text-tertiary">{{ __('Nobody has made a decision this week.') }}</p>
                    @endforelse
                </div>

                <div>
                    <h3 class="mb-3 flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-neutral-400"><span>{{ __('Active today') }}</span>@if ($team['canStaff'])<span class="font-medium normal-case tracking-normal text-tertiary">{{ $team['active_today']->count() }}/{{ $team['active_staff'] }}</span>@endif</h3>
                    <ul class="space-y-2.5">
                        @forelse ($team['active_today'] as $person)
                            <li class="flex items-center gap-2.5 text-sm">
                                <span class="relative shrink-0"><x-user-avatar :user="$person" size="h-7 w-7" />@if ($person->last_login_at->gte(now()->subMinutes(15)))<span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-green-500 ring-2 ring-white" title="{{ __('Active in the last 15 minutes') }}"></span>@endif</span>
                                <span class="min-w-0 flex-1 truncate font-medium text-neutral-900">{{ $person->name }}</span>
                                <span class="shrink-0 text-xs text-tertiary">{{ $person->last_login_at->diffForHumans() }}</span>
                            </li>
                        @empty
                            <li class="text-sm text-tertiary">{{ __('Nobody has signed in today.') }}</li>
                        @endforelse
                    </ul>
                </div>
        </div>
        @endif

        <div @class(['border-t border-neutral-100 p-5' => $team['canLog'], 'p-5' => ! $team['canLog']])>
            <div>
                <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('Security') }}</h3>
                <ul class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($checks as $check)
                        <li>
                            <a href="{{ $check['url'] }}" wire:navigate class="group flex items-center gap-3 rounded-xl border border-neutral-200 px-3 py-2 text-sm transition-colors hover:border-teal-300 hover:bg-teal-50/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">
                                <span @class(['flex h-8 w-8 shrink-0 items-center justify-center rounded-full', 'bg-green-50 text-green-700' => $check['tone'] === 'green', 'bg-amber-50 text-amber-700' => $check['tone'] === 'amber', 'bg-red-50 text-red-700' => $check['tone'] === 'red', 'bg-neutral-100 text-neutral-500' => $check['tone'] === 'neutral'])><x-icon :name="in_array($check['tone'], ['amber', 'red'], true) ? 'exclamation-triangle' : ($check['tone'] === 'green' ? 'check-circle' : 'users')" class="h-4 w-4" /></span>
                                <span class="min-w-0 flex-1 leading-snug text-neutral-700"><span class="font-tertiary font-bold tabular-nums text-neutral-900">{{ $check['value'] }}</span> {{ $check['text'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                @if ($team['posture'])
                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 rounded-xl bg-neutral-50 px-3.5 py-3">
                        <div class="flex items-center gap-3"><p class="text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ __('Platform rules') }}</p><a href="{{ route('admin.settings.security') }}" wire:navigate class="text-xs font-medium text-teal-700 hover:text-teal-800">{{ __('Manage') }}</a></div>
                        <ul class="flex flex-wrap gap-1.5">
                            @foreach ($team['posture'] as $rule)
                                <li @class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium', 'bg-green-50 text-green-800' => $rule['on'], 'bg-neutral-200/60 text-neutral-600' => ! $rule['on']])><span @class(['h-1.5 w-1.5 rounded-full', 'bg-green-500' => $rule['on'], 'bg-neutral-400' => ! $rule['on']])></span>{{ __($rule['label']) }}: {{ $rule['on'] ? __('on') : __('off') }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </x-card>
</section>
