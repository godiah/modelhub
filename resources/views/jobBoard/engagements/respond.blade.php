@use('App\Enums\EngagementStatus')
@php
    $job = $application->job;
    $pending = $engagement->status === EngagementStatus::EmployerAccepted;
    $accepted = $engagement->status === EngagementStatus::Active;
    $answeredAt = $accepted ? $engagement->started_at : $engagement->cancelled_at;
@endphp
<x-app-layout crumb="Respond to offer">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <!-- Header -->
        <x-card class="mb-6 rounded-2xl">
            <div class="flex flex-col gap-4 p-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Job offer') }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-3">
                        <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ $job->title }}</h1>
                        @if ($pending)
                            <x-badge tone="amber" class="px-2.5 py-0.5 text-xs font-medium">{{ __('Awaiting your response') }}</x-badge>
                        @elseif ($accepted)
                            <x-badge tone="green" class="px-2.5 py-0.5 text-xs font-medium">{{ __('Accepted') }}</x-badge>
                        @else
                            <x-badge tone="neutral" class="px-2.5 py-0.5 text-xs font-medium">{{ __('Declined') }}</x-badge>
                        @endif
                    </div>
                    <p class="mt-1.5 text-sm text-tertiary">
                        {{ __('From :name', ['name' => $application->poster->name]) }}
                        · {{ __('You applied :date', ['date' => $application->created_at->format('M j, Y')]) }}
                    </p>
                </div>
                <x-btn variant="secondary" size="sm" href="{{ $accepted ? route('engagements.show', $engagement) : route('engagements.index') }}" class="shrink-0 self-start">
                    <x-icon name="arrow-left" class="h-4 w-4" />
                    {{ $accepted ? __('Open engagement') : __('Back to engagements') }}
                </x-btn>
            </div>
        </x-card>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div class="space-y-6">
                <x-panel :title="__('Offer terms')">
                    <dl class="divide-y divide-neutral-100 text-sm">
                        <div class="flex items-center justify-between gap-4 py-2.5 first:pt-0">
                            <dt class="text-tertiary">{{ __('Offer amount') }}</dt>
                            <dd class="font-medium tabular-nums text-neutral-900"><x-money :amount="$engagement->agreed_amount" /></dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 py-2.5">
                            <dt class="text-tertiary">{{ __('Service fee') }}</dt>
                            <dd class="font-medium tabular-nums text-neutral-900">− <x-money :amount="$engagement->service_fee" /></dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 py-2.5 last:pb-0">
                            <dt class="font-medium text-neutral-900">{{ __('You receive') }}</dt>
                            <dd class="font-tertiary text-lg font-semibold tabular-nums text-teal-700"><x-money :amount="$engagement->net_amount" /></dd>
                        </div>
                    </dl>
                    <p class="mt-4 border-t border-neutral-100 pt-4 text-xs text-tertiary">{{ __('Payment is held in escrow and released as your work is approved.') }}</p>
                </x-panel>

                <x-panel :title="__('Deliverables')" :description="$engagement->deliverables->isNotEmpty() ? __('What the client expects you to deliver, and when.') : null">
                    @forelse ($engagement->deliverables as $deliverable)
                        @if ($loop->first)
                            <ul class="divide-y divide-neutral-100">
                        @endif
                        <li class="py-4 first:pt-0 last:pb-0">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold text-neutral-900">{{ $deliverable->title }}</h3>
                                <span class="text-xs text-tertiary">
                                    {{ $deliverable->due_date ? __('Due :date', ['date' => $deliverable->due_date->format('M j, Y')]) : __('No due date') }}
                                </span>
                            </div>
                            @if ($deliverable->description)
                                <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-neutral-700">{{ $deliverable->description }}</p>
                            @endif
                        </li>
                        @if ($loop->last)
                            </ul>
                        @endif
                    @empty
                        <p class="text-sm text-tertiary">{{ __('The client has not listed any deliverables yet. You can agree them with the client once you accept.') }}</p>
                    @endforelse
                </x-panel>
            </div>

            <!-- Response -->
            <div class="lg:sticky lg:top-24">
                <x-panel :title="__('Your response')">
                    @unless ($pending)
                        <div class="mb-5 flex items-start gap-3 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-700" role="status">
                            <x-icon name="information-circle" class="mt-0.5 h-5 w-5 shrink-0 text-neutral-400" />
                            <p>
                                {{ $accepted ? __('You accepted this offer') : __('You declined this offer') }}@if ($answeredAt) {{ __('on :date', ['date' => $answeredAt->format('M j, Y')]) }}@endif.
                            </p>
                        </div>
                    @endunless

                    <form method="POST" action="{{ route('engagements.respond', ['engagement' => $engagement]) }}" x-data="{ choice: '' }" class="space-y-5">
                        @csrf

                        <div role="radiogroup" aria-label="{{ __('Accept or decline the offer') }}" class="space-y-3">
                            <label @class([
                                'relative flex items-start gap-3 rounded-xl border p-4 transition-colors has-[:checked]:border-teal-600 has-[:checked]:ring-1 has-[:checked]:ring-teal-600',
                                'cursor-pointer border-neutral-200 bg-white hover:border-neutral-300' => $pending,
                                'border-neutral-200 bg-neutral-50 opacity-70' => ! $pending,
                            ])>
                                <input type="radio" name="response" value="accepted" x-model="choice" class="mt-1 border-neutral-300 text-teal-600 focus:ring-0 focus:ring-offset-0"
                                    @checked($accepted) @disabled(! $pending)>
                                <span>
                                    <span class="block text-sm font-medium text-neutral-900">{{ __('Accept offer') }}</span>
                                    <span class="block text-xs text-tertiary">{{ config('marketplace.jobs_escrow_enabled') ? __('Work starts once the client has funded the job.') : __('Start work on the agreed terms.') }}</span>
                                </span>
                            </label>

                            <label @class([
                                'relative flex items-start gap-3 rounded-xl border p-4 transition-colors has-[:checked]:border-red-500 has-[:checked]:ring-1 has-[:checked]:ring-red-500',
                                'cursor-pointer border-neutral-200 bg-white hover:border-neutral-300' => $pending,
                                'border-neutral-200 bg-neutral-50 opacity-70' => ! $pending,
                            ])>
                                <input type="radio" name="response" value="declined" x-model="choice" class="mt-1 border-neutral-300 text-red-600 focus:ring-0 focus:ring-offset-0"
                                    @checked($engagement->status === EngagementStatus::Cancelled) @disabled(! $pending)>
                                <span>
                                    <span class="block text-sm font-medium text-neutral-900">{{ __('Decline offer') }}</span>
                                    <span class="block text-xs text-tertiary">{{ __('This project is not right for me.') }}</span>
                                </span>
                            </label>
                        </div>

                        <x-field type="textarea" name="notes" error="notes" rows="4" maxlength="1000"
                            :label="$pending ? __('Message to the client (optional)') : __('Your message')"
                            :placeholder="$pending ? __('Anything the client should know…') : ''"
                            :readonly="! $pending">{{ old('notes', $engagement->notes ?? '') }}</x-field>

                        @if ($pending)
                            <p class="text-xs text-tertiary">{{ __('Your response is final and the client is notified straight away.') }}</p>

                            <div class="flex flex-wrap gap-2">
                                <x-btn type="submit" block x-show="choice !== 'declined'" ::disabled="!choice">
                                    <x-icon name="check" class="h-4 w-4" />
                                    <span x-text="choice === 'accepted' ? @js(__('Accept offer')) : @js(__('Submit response'))"></span>
                                </x-btn>
                                <x-btn type="submit" variant="danger" block x-show="choice === 'declined'" x-cloak>
                                    <x-icon name="x-mark" class="h-4 w-4" />
                                    {{ __('Decline offer') }}
                                </x-btn>
                            </div>
                        @endif
                    </form>
                </x-panel>
            </div>
        </div>
    </div>
</x-app-layout>
