@php $canReview = auth()->user()->can('review sellers'); @endphp
<x-staff-layout :title="$store->display_name">
    <div class="container mx-auto max-w-6xl space-y-6 px-4 py-8" x-data="{ suspending: false, reinstating: false }">
        <a href="{{ route('admin.stores.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:underline"><x-icon name="arrow-left" class="h-4 w-4" />{{ __('All stores') }}</a>

        <x-card class="rounded-2xl">
            <div class="flex flex-wrap items-center gap-5 p-6">
                <x-store-avatar :store="$store" size="h-16 w-16" rounded="rounded-2xl" />
                <div class="min-w-0 flex-1">
                    <h1 class="flex flex-wrap items-center gap-2 font-tertiary text-2xl font-semibold text-neutral-900">{{ $store->display_name }}<x-badge :tone="match ($store->status->value) { 'approved' => 'green', 'pending' => 'amber', 'rejected' => 'neutral', default => 'red' }" class="px-2.5 py-0.5 text-xs font-medium">{{ ucfirst($store->status->value) }}</x-badge></h1>
                    <p class="mt-1 text-sm text-tertiary">{{ $store->tagline }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($store->isApproved())<x-btn variant="secondary" href="{{ route('sellers.show', $store->slug) }}" target="_blank">{{ __('Public storefront') }}</x-btn>@endif
                    @if ($canReview && $store->status === \App\Enums\SellerStatus::Approved)<x-btn variant="danger-outline" type="button" @click="suspending = true">{{ __('Suspend store') }}</x-btn>@endif
                    @if ($canReview && $store->status === \App\Enums\SellerStatus::Suspended)<x-btn variant="secondary" type="button" @click="reinstating = true">{{ __('Reinstate store') }}</x-btn>@endif
                </div>
            </div>
        </x-card>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div class="space-y-6">
                <x-panel :title="__('About')"><p class="whitespace-pre-line break-words text-sm leading-relaxed text-neutral-700">{{ $store->bio }}</p>@if ($store->focus)<p class="mt-3 text-sm text-neutral-600"><span class="font-medium text-neutral-800">{{ __('What they make:') }}</span> {{ $store->focus }}</p>@endif</x-panel>
                <x-panel :title="__('Models')">
                    @forelse ($models as $model)
                        <a href="{{ route('admin.catalogue.show', $model) }}" class="flex items-center gap-3 border-b border-neutral-100 py-2.5 text-sm last:border-0 hover:text-teal-700">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-neutral-100">@if ($cover = $model->images->first())<img src="{{ $cover->url() }}" alt="" loading="lazy" class="h-full w-full object-cover">@else<x-icon name="cube" class="h-5 w-5 text-neutral-300" />@endif</span>
                            <span class="min-w-0 flex-1 truncate font-medium text-neutral-900">{{ $model->title }}</span>
                            <x-badge :tone="$model->status->tone()" class="px-2 py-0.5 text-xs font-medium">{{ __($model->status->label()) }}</x-badge>
                        </a>
                    @empty<p class="text-sm text-tertiary">{{ __('No models listed.') }}</p>@endforelse
                </x-panel>
            </div>
            <div class="space-y-6">
                <x-panel :title="__('Store')">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Rating') }}</dt><dd class="text-neutral-900">@if ($store->rating_count){{ number_format($store->rating_avg, 1) }} ★ ({{ $store->rating_count }})@else—@endif</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Applied') }}</dt><dd class="text-neutral-900">{{ ($store->submitted_at ?? $store->created_at)->format('M j, Y') }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Last decision') }}</dt><dd class="text-right text-neutral-900">{{ $store->reviewed_at ? $store->reviewed_at->format('M j, Y').' · '.($store->reviewer?->name ?? __('a reviewer')) : '—' }}</dd></div>
                        @if ($store->website_url)<div class="flex justify-between gap-4"><dt class="text-tertiary">{{ __('Website') }}</dt><dd class="min-w-0 truncate text-teal-700"><a href="{{ $store->website_url }}" target="_blank" rel="nofollow noopener" class="hover:underline">{{ preg_replace('#^https?://(www\.)?#', '', rtrim($store->website_url, '/')) }}</a></dd></div>@endif
                    </dl>
                    @if (filled($store->review_notes))<p class="mt-3 rounded-xl bg-neutral-50 px-3 py-2 text-sm text-neutral-700">{{ $store->review_notes }}</p>@endif
                </x-panel>
                @if ($store->user)
                    <x-panel :title="__('The seller')">
                        <div class="flex items-center gap-3"><x-user-avatar :user="$store->user" size="h-10 w-10" /><div class="min-w-0"><p class="truncate text-sm font-semibold text-neutral-900">{{ $store->user->name }}</p>@can('view members')<a href="{{ route('admin.members.show', $store->user) }}" class="text-xs font-medium text-teal-700 hover:underline">{{ __('Open the member') }}</a>@endcan</div></div>
                        @if ($store->user->isSuspended())<p class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-800">{{ __('This member is suspended, so their models are hidden.') }}</p>@endif
                    </x-panel>
                @endif
            </div>
        </div>

        @if ($canReview)
            <x-confirm-dialog bind="suspending" title="Suspend this store" confirm-label="Suspend" method="PATCH" state="notes: ''" disabledWhen="notes.trim().length < 5" :action="route('admin.sellers.review', [$store, 'suspend'])" message="Their models are hidden from the catalogue until you reinstate them. They are told why.">
                <label for="suspend-notes" class="sr-only">{{ __('Reason') }}</label>
                <textarea id="suspend-notes" name="notes" x-model="notes" rows="3" maxlength="1000" required placeholder="{{ __('The reason they will be shown') }}" class="mt-3 block w-full resize-none rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25"></textarea>
            </x-confirm-dialog>
            <x-confirm-dialog bind="reinstating" title="Reinstate this store" icon="check" tone="success" confirm-label="Reinstate" method="PATCH" :action="route('admin.sellers.review', [$store, 'approve'])" message="Their models come back, and they are told." />
        @endif
    </div>
</x-staff-layout>
