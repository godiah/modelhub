@php
    $pills = ['reported' => __('Reported'), 'hidden' => __('Hidden'), 'all' => __('All')];
    $reasons = \App\Models\ProductReview::REPORT_REASONS;
@endphp
<x-staff-layout title="Review moderation">
    <div class="container mx-auto max-w-5xl px-4 py-8">
        <div class="mb-6">
            <h1 class="font-tertiary text-2xl font-semibold text-neutral-900">{{ __('Review moderation') }}</h1>
            <p class="mt-1 max-w-2xl text-sm text-tertiary">{{ __('Buyers report reviews that look like spam, abuse or fakes. Hide one that breaks the rules (it stops counting towards the rating and its author is told why), or dismiss the reports and leave it up.') }}</p>
        </div>

        <nav aria-label="{{ __('Filter reviews') }}" class="-mx-4 mb-5 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            <ul class="flex min-w-max items-center gap-2">
                @foreach ($pills as $key => $label)
                    @php $active = $status === $key; @endphp
                    <li>
                        <a href="{{ route('admin.reviews.index', ['status' => $key]) }}" @if ($active) aria-current="true" @endif
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

        @if ($reviews->isEmpty())
            <x-empty-state icon="flag" :title="__('Nothing here')" :description="$status === 'reported' ? __('No reviews are waiting on a report.') : __('No reviews match this filter.')" />
        @else
            <div class="space-y-4">
                @foreach ($reviews as $review)
                    <article x-data="{ hiding: false }" class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <x-models.stars :rating="$review->rating" />
                                    <span class="text-sm font-medium text-neutral-900">{{ $review->author->name }}</span>
                                    <x-badge :tone="$review->isVisible() ? 'green' : 'red'" class="px-2.5 py-0.5 text-xs font-medium">{{ $review->isVisible() ? __('Visible') : __('Hidden') }}</x-badge>
                                    @if ($review->open_reports_count > 0)<x-badge tone="amber" class="px-2.5 py-0.5 text-xs font-medium">{{ trans_choice(':count open report|:count open reports', $review->open_reports_count, ['count' => $review->open_reports_count]) }}</x-badge>@endif
                                </div>
                                <p class="mt-1 text-sm text-tertiary">{{ __('On') }} <a href="{{ route('models.show', $review->product) }}#review-{{ $review->id }}" target="_blank" class="font-medium text-teal-700 hover:underline">{{ $review->product->title }}</a> · {{ $review->product->sellerProfile?->display_name }} · {{ $review->created_at->format('M j, Y') }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($review->isVisible())
                                    @if ($review->open_reports_count > 0)
                                        <form method="POST" action="{{ route('admin.reviews.dismiss', $review) }}">@csrf<x-btn size="sm" variant="secondary" type="submit">{{ __('Dismiss reports') }}</x-btn></form>
                                    @endif
                                    <x-btn size="sm" variant="danger-outline" type="button" @click="hiding = true">{{ __('Hide review') }}</x-btn>
                                @else
                                    <form method="POST" action="{{ route('admin.reviews.restore', $review) }}">@csrf<x-btn size="sm" variant="secondary" type="submit">{{ __('Restore') }}</x-btn></form>
                                @endif
                            </div>
                        </div>

                        <p class="mt-3 whitespace-pre-line break-words text-sm text-neutral-800">{{ $review->comment }}</p>

                        @if (filled($review->seller_reply))
                            <div class="mt-3 flex items-start justify-between gap-3 rounded-xl bg-neutral-50 px-4 py-3">
                                <p class="min-w-0 break-words text-sm text-neutral-700"><span class="font-medium text-neutral-900">{{ __('Seller reply') }}:</span> {{ $review->seller_reply }}</p>
                                <form method="POST" action="{{ route('admin.reviews.reply.remove', $review) }}" class="shrink-0">@csrf @method('DELETE')<button type="submit" class="rounded px-2 py-0.5 text-xs font-medium text-red-600 hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40">{{ __('Remove reply') }}</button></form>
                            </div>
                        @endif

                        @if ($review->reports->isNotEmpty())
                            <div class="mt-4 rounded-xl border border-neutral-200">
                                <p class="border-b border-neutral-100 px-4 py-2 text-xs font-medium uppercase tracking-wide text-tertiary">{{ __('Reports') }}</p>
                                <ul class="divide-y divide-neutral-100">
                                    @foreach ($review->reports as $report)
                                        <li class="px-4 py-2 text-sm">
                                            <span class="font-medium text-neutral-900">{{ __($reasons[$report->reason] ?? $report->reason) }}</span>
                                            <span class="text-tertiary">· {{ $report->reporter->name }} · {{ $report->created_at->format('M j') }}@if ($report->status === 'resolved') · {{ __('resolved') }}@endif</span>
                                            @if ($report->details)<p class="mt-0.5 text-neutral-700">{{ $report->details }}</p>@endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @unless ($review->isVisible())
                            <p class="mt-3 text-xs text-tertiary">{{ __('Hidden :date by :name', ['date' => $review->hidden_at?->format('M j, Y'), 'name' => $review->hiddenBy?->name ?? __('a reviewer')]) }} · <span class="text-neutral-700">{{ $review->hidden_reason }}</span></p>
                        @endunless

                        <x-confirm-dialog bind="hiding" title="Hide this review" confirm-label="Hide review" state="reason: ''" disabledWhen="reason.trim().length < 5"
                            :action="route('admin.reviews.hide', $review)" message="It stops counting towards the rating and disappears from the model page. The author is shown your reason.">
                            <label for="hide-{{ $review->id }}" class="sr-only">{{ __('Reason') }}</label>
                            <textarea id="hide-{{ $review->id }}" name="reason" x-model="reason" rows="3" maxlength="500" required placeholder="{{ __('Why it is being hidden') }}"
                                class="mt-3 block w-full resize-none rounded-xl border border-neutral-300 px-3 py-2 text-sm focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/25"></textarea>
                        </x-confirm-dialog>
                    </article>
                @endforeach
            </div>
            <x-pager :paginator="$reviews" />
        @endif
    </div>
</x-staff-layout>
