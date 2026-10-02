@use('App\Enums\EngagementStatus')
@use('App\Support\Money')
<x-app-layout :crumb="$job->title">
    @php
        $status = $engagement->status;
        $tabs = [
            'overview' => ['label' => __('Overview'), 'icon' => 'squares-2x2'],
            'deliverables' => ['label' => __('Deliverables'), 'icon' => 'clipboard-check', 'count' => $summary['total']],
            'messages' => ['label' => __('Messages'), 'icon' => 'chat-bubble-left-right', 'unread' => $summary['unread_messages']],
            'activity' => ['label' => __('Activity'), 'icon' => 'clock'],
        ];
        if (!$actions['can_message']) {
            unset($tabs['messages']);
        }

        // An offer still waiting for funding is not escrowed, whatever an older bookkeeping flag says
        $escrowed = $engagement->escrow_minor > 0 || (! $engagement->isAwaitingFunding() && $engagement->isPaymentEscrowed());
        $released = $engagement->payment_released_at !== null;
    @endphp

    {{-- Tab state lives in the URL hash (like the profile page) so a tab can be linked to and survives a reload. --}}
    <div class="container mx-auto max-w-7xl px-4 py-8" @hashchange.window="sync()" x-data="{
        tab: 'overview',
        tabs: @js(array_keys($tabs)),
        key: 'engagement-tab-{{ $engagement->id }}',
        init() {
            // A redirect back from a form post drops the hash, so fall back to the tab this session last used.
            try { this.tab = this.tabs.includes(sessionStorage.getItem(this.key)) ? sessionStorage.getItem(this.key) : this.tab; } catch (e) {}
            this.sync();
        },
        sync() {
            const hash = window.location.hash.slice(1);
            if (this.tabs.includes(hash)) { this.tab = hash; this.remember(); }
        },
        remember() { try { sessionStorage.setItem(this.key, this.tab); } catch (e) {} },
        show(tab) {
            this.tab = tab;
            history.replaceState(null, '', '#' + tab);
            this.remember();
        },
        move(step) {
            const next = this.tabs[(this.tabs.indexOf(this.tab) + step + this.tabs.length) % this.tabs.length];
            this.tab = next;
            history.replaceState(null, '', '#' + next);
            this.remember();
            this.$nextTick(() => this.$refs['tab-' + next].focus());
        },
    }">
        <!-- Header -->
        <x-card class="mb-6 rounded-2xl">
            <div class="flex flex-col gap-5 p-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ $job->title }}</h1>
                        <x-engagement.status-badge :status="$status" class="px-2.5 py-0.5 text-xs font-medium" icon-class="w-3 h-3 mr-1" />
                    </div>
                    <p class="mt-1.5 text-sm text-tertiary">
                        <span class="font-medium text-neutral-700">{{ $summary['role'] === 'freelancer' ? __('Freelancer') : __('Client') }}</span>
                        @if ($summary['counterpart'])
                            · {{ __('with :name', ['name' => $summary['counterpart']]) }}
                        @endif
                        @if ($engagement->started_at && ! $engagement->isAwaitingFunding())
                            · {{ __('Started :date', ['date' => $engagement->started_at->format('M j, Y')]) }}
                        @endif
                    </p>
                </div>

                <x-engagement.actions :engagement="$engagement" :summary="$summary" :actions="$actions" :current="true" />
            </div>

            <dl class="grid grid-cols-1 gap-x-8 gap-y-4 border-t border-neutral-100 px-6 py-5 sm:grid-cols-3">
                <div>
                    <dt class="text-xs text-tertiary">{{ __('Progress') }}</dt>
                    <dd class="mt-1.5">
                        <div class="h-2 overflow-hidden rounded-full bg-neutral-100" role="progressbar"
                            aria-label="{{ __('Deliverables approved') }}" aria-valuemin="0" aria-valuemax="100"
                            aria-valuenow="{{ $summary['percent'] }}">
                            <div class="h-full rounded-full bg-teal-600" style="width: {{ $summary['percent'] }}%"></div>
                        </div>
                        <p class="mt-1.5 flex items-center justify-between gap-2 text-xs text-tertiary">
                            <span>
                                {{ $summary['total'] > 0 ? __(':approved of :total approved', ['approved' => $summary['approved'], 'total' => $summary['total']]) : __('No deliverables yet') }}
                            </span>
                            @if ($summary['next_due'])
                                <span @class(['font-medium', 'text-red-600' => $summary['overdue']])>
                                    {{ $summary['overdue'] ? __('Was due') : __('Next due') }} {{ $summary['next_due']->format('M j') }}
                                </span>
                            @endif
                        </p>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-tertiary">{{ __($summary['amount_label']) }}</dt>
                    <dd class="mt-1 font-tertiary text-lg font-semibold tabular-nums text-neutral-900"><x-money :amount="$summary['amount']" /></dd>
                </div>
                <div>
                    <dt class="text-xs text-tertiary">{{ __('Payment') }}</dt>
                    <dd class="mt-1 flex items-center gap-2 text-sm font-medium text-neutral-800">
                        @if ($released)
                            <x-badge tone="green" class="px-2.5 py-0.5 text-xs font-medium">{{ __('Released') }}</x-badge>
                        @elseif ($escrowed)
                            <x-badge tone="blue" class="px-2.5 py-0.5 text-xs font-medium">{{ __('Held in escrow') }}</x-badge>
                        @else
                            <x-badge tone="neutral" class="px-2.5 py-0.5 text-xs font-medium">{{ $engagement->isAwaitingFunding() ? __('Awaiting funding') : __('Not yet escrowed') }}</x-badge>
                        @endif
                    </dd>
                    @if ($engagement->escrow_minor > 0)
                        <p class="mt-1.5 text-xs text-tertiary">{{ __(':released released · :held in escrow', ['released' => Money::formatMinor($engagement->released_net_minor + $engagement->released_fee_minor, 0), 'held' => Money::formatMinor(max(0, $engagement->escrow_minor - $engagement->released_net_minor - $engagement->released_fee_minor - $engagement->refunded_minor), 0)]) }}</p>
                    @endif
                </div>
            </dl>
        </x-card>

        <!-- Funding: work starts once the client has put the money into escrow -->
        @if ($engagement->isAwaitingFunding())
            @if ($actions['is_poster'])
                <x-card class="mb-6 rounded-2xl border-amber-200 bg-amber-50/60" aria-labelledby="fund-title">
                    <form method="POST" action="{{ route('engagements.fund', $engagement) }}" class="flex flex-col gap-5 p-6 lg:flex-row lg:items-end lg:justify-between" x-data="{ loading: false }" x-on:submit="loading = true">
                        @csrf
                        <div class="min-w-0 max-w-xl">
                            <h2 id="fund-title" class="font-tertiary text-lg font-semibold text-neutral-900">{{ __('Fund this job to start work') }}</h2>
                            <p class="mt-1 text-sm text-neutral-700">{{ __(':freelancer accepted your offer. Pay :amount by M-Pesa to start. It is held in escrow and released to them only as you approve each deliverable.', ['freelancer' => $summary['counterpart'], 'amount' => Money::format($engagement->agreed_amount)]) }}</p>
                        </div>
                        <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-end lg:w-auto">
                            <div class="sm:w-60">
                                <label for="fund-phone" class="mb-1 block text-xs font-medium text-neutral-700">{{ __('Your M-Pesa number') }}</label>
                                <input id="fund-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" required value="{{ old('phone') }}" placeholder="0712 345 678" class="block w-full rounded-xl border border-neutral-300 px-3 py-2.5 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25 @error('phone') border-red-400 @enderror">
                                @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <x-btn type="submit" x-bind:disabled="loading">{{ __('Pay :amount', ['amount' => Money::format($engagement->agreed_amount)]) }}</x-btn>
                        </div>
                    </form>
                </x-card>
            @else
                <div class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
                    <x-icon name="clock" class="mt-0.5 h-5 w-5 shrink-0" />
                    <p>{{ __('Waiting for the client to put :amount into escrow. You can start work as soon as they have, and we will tell you.', ['amount' => Money::format($engagement->agreed_amount)]) }}</p>
                </div>
            @endif
        @endif

        <!-- What is waiting on you -->
        @if ($summary['to_review'] > 0)
            <div class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
                <x-icon name="document-text" class="mt-0.5 h-5 w-5 shrink-0" />
                <p>{{ trans_choice(':count deliverable is waiting for your review.|:count deliverables are waiting for your review.', $summary['to_review'], ['count' => $summary['to_review']]) }}</p>
            </div>
        @elseif ($summary['to_revise'] > 0)
            <div class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="status">
                <x-icon name="pencil-square" class="mt-0.5 h-5 w-5 shrink-0" />
                <p>{{ trans_choice('The client asked for changes on :count deliverable.|The client asked for changes on :count deliverables.', $summary['to_revise'], ['count' => $summary['to_revise']]) }}</p>
            </div>
        @elseif ($actions['is_poster'] && $status === EngagementStatus::EmployerAccepted)
            <div class="mb-6 flex items-start gap-3 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-700" role="status">
                <x-icon name="clock" class="mt-0.5 h-5 w-5 shrink-0" />
                <p>{{ __('Waiting for the freelancer to respond to your offer.') }}</p>
            </div>
        @endif

        <!-- Tab bar -->
        <div class="-mx-4 mb-6 overflow-x-auto px-4 [scrollbar-width:none] sm:mx-0 sm:px-0 [&::-webkit-scrollbar]:hidden">
            <div role="tablist" aria-label="{{ __('Engagement sections') }}"
                class="inline-flex min-w-full gap-1 border-b border-neutral-200 sm:flex"
                @keydown.arrow-right.prevent="move(1)" @keydown.arrow-left.prevent="move(-1)">
                @foreach ($tabs as $key => $tab)
                    <button type="button" role="tab" id="tab-{{ $key }}" x-ref="tab-{{ $key }}"
                        aria-controls="panel-{{ $key }}" :aria-selected="(tab === '{{ $key }}').toString()"
                        :tabindex="tab === '{{ $key }}' ? 0 : -1" @click="show('{{ $key }}')"
                        :class="tab === '{{ $key }}' ? 'border-teal-600 text-neutral-900' : 'border-transparent text-neutral-500 hover:text-neutral-800'"
                        class="-mb-px inline-flex shrink-0 items-center gap-2 whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors duration-150 focus:outline-none focus-visible:rounded-t-lg focus-visible:ring-2 focus-visible:ring-secondary/40">
                        <x-icon :name="$tab['icon']" class="h-4 w-4" />
                        {{ $tab['label'] }}
                        @if (!empty($tab['count']))
                            <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-xs font-semibold text-neutral-600">{{ $tab['count'] }}</span>
                        @endif
                        @if (!empty($tab['unread']))
                            <span data-unread-for="{{ $engagement->id }}" class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800">{{ $tab['unread'] }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        <div id="panel-overview" role="tabpanel" aria-labelledby="tab-overview" x-show="tab === 'overview'" x-cloak>
            @include('jobBoard.engagements.workspace.overview')
        </div>

        <div id="panel-deliverables" role="tabpanel" aria-labelledby="tab-deliverables" x-show="tab === 'deliverables'" x-cloak>
            @include('jobBoard.engagements.workspace.deliverables')
        </div>

        @if (isset($tabs['messages']))
            <div id="panel-messages" role="tabpanel" aria-labelledby="tab-messages" x-show="tab === 'messages'" x-cloak>
                @include('jobBoard.engagements.workspace.messages')
            </div>
        @endif

        <div id="panel-activity" role="tabpanel" aria-labelledby="tab-activity" x-show="tab === 'activity'" x-cloak>
            @include('jobBoard.engagements.workspace.activity')
        </div>

        {{-- Modals: deliverable actions by role, plus the engagement-level ones opened from the actions menu --}}
        @if ($actions['can_manage_deliverables'])
            @include('jobBoard.engagements.partials.components.modals.add')
            @include('jobBoard.engagements.partials.components.modals.edit')
            @include('jobBoard.engagements.partials.components.modals.delete')
            @include('jobBoard.engagements.partials.components.modals.approve')
            @include('jobBoard.engagements.partials.components.modals.reject')
        @endif
        @if ($actions['can_submit_deliverables'])
            @include('jobBoard.engagements.workspace.submit-modal')
        @endif
        @if ($actions['can_cancel'])
            @include('jobBoard.engagements.partials.components.modals.cancellation')
        @endif
        @if ($actions['is_finished'])
            @include('jobBoard.engagements.partials.components.modals.archive')
            @include('jobBoard.engagements.partials.components.modals.review')
        @endif
    </div>
</x-app-layout>
