@use('App\Enums\SellerStatus')
@php
    $pills = ['pending' => __('Pending'), 'approved' => __('Approved'), 'rejected' => __('Not approved'), 'suspended' => __('Suspended'), 'all' => __('All')];
@endphp
<x-staff-layout title="Seller applications">
    <div class="container mx-auto max-w-7xl px-4 py-8">
        <x-staff.header :title="__('Seller applications')">{{ __('Members who want to sell 3D models. Approved sellers can list models; whoever you reject or suspend is shown your reason.') }}</x-staff.header>

        <nav aria-label="{{ __('Filter by status') }}" class="-mx-4 mb-5 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            <ul class="flex min-w-max items-center gap-2">
                @foreach ($pills as $key => $label)
                    @php $active = $status === $key; @endphp
                    <li>
                        <a href="{{ route('admin.sellers.index', ['status' => $key]) }}" @if ($active) aria-current="true" @endif
                            @class([
                                'inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40',
                                'border-teal-600 bg-teal-600 text-white' => $active,
                                'border-neutral-200 bg-white text-neutral-700 hover:border-neutral-300 hover:bg-neutral-50' => ! $active,
                            ])>
                            {{ $label }}
                            <span @class(['text-xs tabular-nums', 'text-teal-100' => $active, 'text-tertiary' => ! $active])>{{ $counts[$key] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @if ($sellers->isEmpty())
            <x-empty-state icon="cube" :title="__('Nothing here')" :description="$status === 'pending' ? __('No applications are waiting for review.') : __('No sellers match this filter.')" />
        @else
            <div class="space-y-4">
                @foreach ($sellers as $seller)
                    <article x-data="{ approving: false, rejecting: false, suspending: false }" class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="flex min-w-0 items-start gap-3">
                                <x-store-avatar :store="$seller" size="h-12 w-12" />
                                <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="font-tertiary text-lg font-semibold text-neutral-900">{{ $seller->display_name }}</h2>
                                    <x-badge :tone="$seller->status->tone()" class="px-2.5 py-0.5 text-xs font-medium">{{ __($seller->status->label()) }}</x-badge>
                                </div>
                                <p class="mt-1 text-sm text-tertiary">
                                    {{ $seller->user->name }} · {{ $seller->user->email }} · {{ __('applied :date', ['date' => $seller->submitted_at?->format('M j, Y')]) }}
                                </p>
                                @if ($seller->tagline)<p class="mt-0.5 text-sm text-neutral-700">{{ $seller->tagline }}</p>@endif
                                @if ($seller->name_changed_at)<p class="mt-0.5 text-xs text-tertiary">{{ __('Renamed :date', ['date' => $seller->name_changed_at->format('M j, Y')]) }}</p>@endif
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                @if (in_array($seller->status, [SellerStatus::Pending, SellerStatus::Rejected, SellerStatus::Suspended], true))
                                    <x-btn size="sm" type="button" @click="approving = true">
                                        <x-icon name="check" class="h-4 w-4" />
                                        {{ $seller->status === SellerStatus::Suspended ? __('Reinstate') : __('Approve') }}
                                    </x-btn>
                                @endif
                                @if ($seller->status === SellerStatus::Pending)
                                    <x-btn size="sm" variant="danger-outline" type="button" @click="rejecting = true">{{ __('Reject') }}</x-btn>
                                @endif
                                @if ($seller->status === SellerStatus::Approved)
                                    <x-btn size="sm" variant="secondary" href="{{ route('sellers.show', $seller->slug) }}" target="_blank">{{ __('Storefront') }}</x-btn>
                                    <x-btn size="sm" variant="danger-outline" type="button" @click="suspending = true">{{ __('Suspend') }}</x-btn>
                                @endif
                            </div>
                        </div>

                        <dl class="mt-4 grid gap-4 border-t border-neutral-100 pt-4 text-sm lg:grid-cols-2">
                            <div>
                                <dt class="text-xs text-tertiary">{{ __('About them') }}</dt>
                                <dd class="mt-1 whitespace-pre-line break-words text-neutral-800">{{ $seller->bio }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-tertiary">{{ __('What they plan to sell') }}</dt>
                                <dd class="mt-1 whitespace-pre-line break-words text-neutral-800">{{ $seller->focus }}</dd>
                                @if ($seller->website_url)
                                    <dt class="mt-3 text-xs text-tertiary">{{ __('Public website') }}</dt>
                                    <dd class="mt-1 break-all"><a href="{{ $seller->website_url }}" target="_blank" rel="noopener nofollow" class="text-teal-700 hover:underline">{{ $seller->website_url }}</a></dd>
                                @endif
                                @if ($seller->portfolio_url)
                                    <dt class="mt-3 text-xs text-tertiary">{{ __('Portfolio (private)') }}</dt>
                                    <dd class="mt-1 break-all"><a href="{{ $seller->portfolio_url }}" target="_blank" rel="noopener nofollow" class="text-teal-700 hover:underline">{{ $seller->portfolio_url }}</a></dd>
                                @endif
                            </div>
                        </dl>

                        @if ($seller->reviewed_at)
                            <p class="mt-4 border-t border-neutral-100 pt-4 text-xs text-tertiary">
                                {{ __('Last decision :date by :name', ['date' => $seller->reviewed_at->format('M j, Y'), 'name' => $seller->reviewer?->name ?? __('a reviewer')]) }}
                                @if ($seller->review_notes) · <span class="text-neutral-700">{{ $seller->review_notes }}</span>@endif
                            </p>
                        @endif

                        <x-confirm-dialog bind="approving" :title="$seller->status === SellerStatus::Suspended ? 'Reinstate seller' : 'Approve seller'" icon="check" tone="success" confirm-label="Approve" method="PATCH"
                            :action="route('admin.sellers.review', [$seller, 'approve'])"
                            :message="'This lets '.$seller->display_name.' list models once the marketplace opens, and tells them.'" />
                        <x-confirm-dialog bind="rejecting" title="Reject application" confirm-label="Reject" method="PATCH" state="notes: ''" disabledWhen="notes.trim().length < 5"
                            :action="route('admin.sellers.review', [$seller, 'reject'])" message="Tell them why. They will see this and can improve and apply again.">
                            <label for="reject-{{ $seller->id }}" class="sr-only">{{ __('Reason') }}</label>
                            <textarea id="reject-{{ $seller->id }}" name="notes" x-model="notes" rows="3" maxlength="1000" required placeholder="{{ __('Reason shown to the applicant') }}"
                                class="mt-3 block w-full resize-none rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25"></textarea>
                        </x-confirm-dialog>
                        <x-confirm-dialog bind="suspending" title="Suspend seller" confirm-label="Suspend" method="PATCH" state="notes: ''" disabledWhen="notes.trim().length < 5"
                            :action="route('admin.sellers.review', [$seller, 'suspend'])" message="They will not be able to list or sell models. They will see your reason.">
                            <label for="suspend-{{ $seller->id }}" class="sr-only">{{ __('Reason') }}</label>
                            <textarea id="suspend-{{ $seller->id }}" name="notes" x-model="notes" rows="3" maxlength="1000" required placeholder="{{ __('Reason shown to the seller') }}"
                                class="mt-3 block w-full resize-none rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25"></textarea>
                        </x-confirm-dialog>
                    </article>
                @endforeach
            </div>

            <x-pager :paginator="$sellers" />
        @endif
    </div>
</x-staff-layout>
