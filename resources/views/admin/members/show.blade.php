@use('App\Support\Staff\Masking')
@php
    $staff = auth()->user();
    $canManage = $staff->can('manage members');
    $seller = $member->sellerProfile;
@endphp
<x-staff-layout :title="$member->name">
    <div class="container mx-auto max-w-6xl space-y-6 px-4 py-8" x-data="{ suspending: false, reinstating: false }">
        <a href="{{ route('admin.members.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('All members') }}</a>

        @if ($member->isSuspended())
            <div class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-900" role="status">
                <x-icon name="exclamation-triangle" class="mt-0.5 h-5 w-5 shrink-0 text-red-600" />
                <p><span class="font-semibold">{{ __('Suspended :when', ['when' => $member->suspended_at->diffForHumans()]) }}@if ($member->suspendedBy) {{ __('by :name', ['name' => $member->suspendedBy->name]) }}@endif.</span> {{ $member->suspended_reason }}</p>
            </div>
        @endif

        <x-card class="rounded-2xl">
            <div class="flex flex-wrap items-center gap-5 p-6">
                <x-user-avatar :user="$member" size="h-16 w-16" />
                <div class="min-w-0 flex-1">
                    <h1 class="flex flex-wrap items-center gap-2 font-tertiary text-2xl font-semibold text-neutral-900">{{ $member->name }}
                        @if ($member->isSuspended())<x-badge tone="red" class="px-2.5 py-0.5 text-xs font-medium">{{ __('Suspended') }}</x-badge>@endif
                        @if ($seller?->isApproved())<x-badge tone="blue" class="px-2.5 py-0.5 text-xs font-medium">{{ __('Seller') }}</x-badge>@endif
                    </h1>
                    <p class="mt-1 text-sm text-tertiary">{{ __('Member #:id · joined :date · :seen', ['id' => $member->id, 'date' => $member->created_at->format('M j, Y'), 'seen' => $member->last_login_at ? __('last seen :when', ['when' => $member->last_login_at->diffForHumans()]) : __('never signed in')]) }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($canManage)
                        @if ($member->isSuspended())
                            <x-btn type="button" variant="secondary" @click="reinstating = true">{{ __('Reinstate') }}</x-btn>
                        @else
                            <x-btn type="button" variant="danger-outline" @click="suspending = true">{{ __('Suspend') }}</x-btn>
                        @endif
                    @endif
                </div>
            </div>
        </x-card>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div class="space-y-6">
                <x-panel :title="__('Activity')">
                    <dl class="grid grid-cols-2 gap-4 sm:grid-cols-5">
                        @foreach ([['Projects posted', $counts['projects']], ['Applications', $counts['applications']], ['Models', $counts['models']], ['Reviews given', $counts['reviews_given']], ['Reviews received', $counts['reviews_received']]] as [$label, $value])
                            <div><dt class="text-xs text-tertiary">{{ __($label) }}</dt><dd class="mt-1 font-tertiary text-2xl font-bold tabular-nums text-neutral-900">{{ $value }}</dd></div>
                        @endforeach
                    </dl>
                </x-panel>

                <x-panel :title="__('Projects posted')">
                    @forelse ($projects as $project)
                        @php [$stateLabel, $stateTone] = \App\Services\Admin\ProjectDirectoryService::state($project); @endphp
                        <a href="{{ route('admin.projects.show', $project) }}" class="flex items-center justify-between gap-3 border-b border-neutral-100 py-2.5 text-sm last:border-0 hover:text-teal-700">
                            <span class="min-w-0 truncate font-medium text-neutral-900">{{ $project->title }}</span>
                            <span class="flex shrink-0 items-center gap-3 text-xs text-tertiary">{{ trans_choice(':count applicant|:count applicants', $project->applications_count, ['count' => $project->applications_count]) }}<x-badge :tone="$stateTone" class="px-2 py-0.5 text-xs font-medium">{{ __($stateLabel) }}</x-badge></span>
                        </a>
                    @empty<p class="text-sm text-tertiary">{{ __('No projects posted.') }}</p>@endforelse
                </x-panel>

                <x-panel :title="__('Applications sent')">
                    @forelse ($applications as $application)
                        <p class="flex items-center justify-between gap-3 border-b border-neutral-100 py-2.5 text-sm last:border-0"><span class="min-w-0 truncate text-neutral-900">{{ $application->job?->title ?? __('A deleted project') }}</span><span class="shrink-0 text-xs text-tertiary">{{ $application->status->label() }} · {{ $application->created_at->format('M j') }}</span></p>
                    @empty<p class="text-sm text-tertiary">{{ __('No applications sent.') }}</p>@endforelse
                </x-panel>

                <x-panel :title="__('Hires')">
                    @forelse ($engagements as $engagement)
                        <a href="{{ route('admin.engagements.show', $engagement) }}" class="flex items-center justify-between gap-3 border-b border-neutral-100 py-2.5 text-sm last:border-0 hover:text-teal-700">
                            <span class="min-w-0 truncate text-neutral-900">{{ $engagement->application->job->title }} <span class="text-xs text-tertiary">· {{ $engagement->application->poster_id === $member->id ? __('as client') : __('as freelancer') }}</span></span>
                            <span class="shrink-0 text-xs text-tertiary">{{ $engagement->status->label() }}</span>
                        </a>
                    @empty<p class="text-sm text-tertiary">{{ __('No hires yet.') }}</p>@endforelse
                </x-panel>

                <x-panel :title="__('Models listed')">
                    @forelse ($models as $model)
                        <a href="{{ route('admin.catalogue.show', $model) }}" class="flex items-center justify-between gap-3 border-b border-neutral-100 py-2.5 text-sm last:border-0 hover:text-teal-700"><span class="min-w-0 truncate font-medium text-neutral-900">{{ $model->title }}</span><x-badge :tone="$model->status->tone()" class="px-2 py-0.5 text-xs font-medium">{{ __($model->status->label()) }}</x-badge></a>
                    @empty<p class="text-sm text-tertiary">{{ __('No models listed.') }}</p>@endforelse
                </x-panel>
            </div>

            <div class="space-y-6">
                <x-panel :title="__('Contact')">
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-xs text-tertiary">{{ __('Email') }}</dt><dd class="mt-0.5 break-all font-medium text-neutral-900">{{ Masking::email($member->email, $canSeeContact) }}</dd>
                            <dd class="text-xs {{ $member->email_verified_at ? 'text-green-700' : 'text-amber-700' }}">{{ $member->email_verified_at ? __('Verified :date', ['date' => $member->email_verified_at->format('M j, Y')]) : __('Not verified') }}</dd></div>
                        <div><dt class="text-xs text-tertiary">{{ __('Phone') }}</dt><dd class="mt-0.5 font-medium text-neutral-900">{{ Masking::phone($member->profile?->telephone_number, $canSeeContact) }}</dd></div>
                        <div><dt class="text-xs text-tertiary">{{ __('Location') }}</dt><dd class="mt-0.5 text-neutral-900">{{ $member->profile?->location ?: '—' }}</dd></div>
                        <div><dt class="text-xs text-tertiary">{{ __('Two-factor sign-in') }}</dt><dd class="mt-0.5 text-neutral-900">{{ $member->hasTwoFactorEnabled() ? __('On') : __('Off') }}</dd></div>
                    </dl>
                    @unless ($canSeeContact)<p class="mt-4 rounded-lg bg-neutral-50 px-3 py-2 text-xs text-tertiary">{{ __('Full contact details are hidden for your role.') }}</p>@endunless
                </x-panel>

                @if ($seller)
                    <x-panel :title="__('Store')">
                        <p class="text-sm font-semibold text-neutral-900">{{ $seller->display_name }}</p>
                        <p class="mt-0.5 text-xs text-tertiary">{{ ucfirst($seller->status->value) }}@if ($seller->rating_count) · {{ number_format($seller->rating_avg, 1) }} ★ ({{ $seller->rating_count }})@endif</p>
                        @can('view sellers')<a href="{{ route('admin.stores.show', $seller) }}" class="mt-3 inline-block text-sm font-medium text-teal-700 hover:underline">{{ __('Open the store') }}</a>@endcan
                    </x-panel>
                @endif

                @if ($canManage)
                    <x-panel :title="__('Help this member')">
                        <div class="space-y-2">
                            <form method="POST" action="{{ route('admin.members.password-reset', $member) }}">@csrf<x-btn type="submit" variant="secondary" block>{{ __('Send a password reset link') }}</x-btn></form>
                            @if ($member->hasAuthenticator() || $member->hasTwoFactorEnabled())<form method="POST" action="{{ route('admin.members.two-factor-reset', $member) }}">@csrf<x-btn type="submit" variant="secondary" block>{{ __('Reset two-step sign-in') }}</x-btn></form>@endif
                            @unless ($member->email_verified_at)<form method="POST" action="{{ route('admin.members.verification', $member) }}">@csrf<x-btn type="submit" variant="secondary" block>{{ __('Resend the verification email') }}</x-btn></form>@endunless
                        </div>
                        <p class="mt-3 text-xs text-tertiary">{{ __('The member chooses their own password. Staff never see or set one. Resetting two-step sign-in removes their authenticator app and emails them.') }}</p>
                    </x-panel>
                @endif

                <x-panel :title="__('Staff notes')" :description="__('Private to staff. The member never sees these.')">
                    @if ($canManage)
                        <form method="POST" action="{{ route('admin.members.notes.store', $member) }}" class="mb-4">
                            @csrf
                            <label for="body" class="sr-only">{{ __('New note') }}</label>
                            <textarea id="body" name="body" rows="3" maxlength="2000" required placeholder="{{ __('Add a note…') }}" class="block w-full resize-none rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25">{{ old('body') }}</textarea>
                            @error('body')<p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                            <x-btn type="submit" size="sm" class="mt-2">{{ __('Add note') }}</x-btn>
                        </form>
                    @endif
                    <ul class="space-y-3">
                        @forelse ($member->notes as $note)
                            <li class="rounded-xl bg-neutral-50 px-3 py-2.5"><p class="whitespace-pre-line break-words text-sm text-neutral-800">{{ $note->body }}</p><p class="mt-1 text-xs text-tertiary">{{ $note->author?->name ?? __('Former staff') }} · {{ $note->created_at->diffForHumans() }}</p></li>
                        @empty<li class="text-sm text-tertiary">{{ __('No notes yet.') }}</li>@endforelse
                    </ul>
                </x-panel>
            </div>
        </div>

        @if ($canManage)
            <x-confirm-dialog bind="suspending" title="Suspend this account" confirm-label="Suspend" state="reason: ''" disabledWhen="reason.trim().length < 5" :action="route('admin.members.suspend', $member)"
                message="They are signed out, cannot sign in, and their projects and models are hidden. They are emailed the reason, and you can reinstate them at any time.">
                <label for="suspend-reason" class="sr-only">{{ __('Reason') }}</label>
                <textarea id="suspend-reason" name="reason" x-model="reason" rows="3" maxlength="500" required placeholder="{{ __('The reason they will be shown') }}" class="mt-3 block w-full resize-none rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25"></textarea>
            </x-confirm-dialog>
            <x-confirm-dialog bind="reinstating" title="Reinstate this account" icon="check" tone="success" confirm-label="Reinstate" :action="route('admin.members.reinstate', $member)" message="They can sign in again, their projects and models come back, and they are told." />
        @endif
    </div>
</x-staff-layout>
