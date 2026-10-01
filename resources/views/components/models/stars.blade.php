@props(['rating' => null, 'count' => null, 'size' => 'h-4 w-4', 'showNumber' => false])

{{--
    A read-only star rating, filled to the fraction (4.3 fills four stars and 30% of the fifth). With `count`
    it adds "(12)"; with `showNumber` the average in digits. No rating shows nothing, so callers decide the
    "No reviews yet" wording.
--}}
@if ($rating !== null)
    @php $fill = max(0, min(5, (float) $rating)) / 5 * 100; @endphp
    <span {{ $attributes->class('inline-flex items-center gap-1.5') }} @if ($count !== null) title="{{ trans_choice(':rating out of 5, :count review|:rating out of 5, :count reviews', $count, ['rating' => number_format((float) $rating, 1), 'count' => $count]) }}" @endif>
        <span class="relative inline-flex" role="img" aria-label="{{ __(':rating out of 5 stars', ['rating' => number_format((float) $rating, 1)]) }}">
            <span class="flex text-neutral-300" aria-hidden="true">@for ($i = 0; $i < 5; $i++)<x-icon name="star-solid" class="{{ $size }} shrink-0" />@endfor</span>
            <span class="absolute inset-y-0 left-0 flex overflow-hidden text-amber-400" style="width: {{ $fill }}%" aria-hidden="true">@for ($i = 0; $i < 5; $i++)<x-icon name="star-solid" class="{{ $size }} shrink-0" />@endfor</span>
        </span>
        @if ($showNumber)<span class="text-sm font-semibold tabular-nums text-neutral-900">{{ number_format((float) $rating, 1) }}</span>@endif
        @if ($count !== null)<span class="text-xs tabular-nums text-tertiary">({{ number_format($count) }})</span>@endif
    </span>
@endif
