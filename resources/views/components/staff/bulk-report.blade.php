@props(['result'])

{{-- What a bulk action just did: how many items it changed, and each one it skipped with the reason. Shown once, at the top of the page. --}}
@php
    $done = count($result['done']);
    $skipped = $result['skipped'];
@endphp
<div x-data="{ open: true }" x-show="open" role="status" @class(['mb-6 rounded-xl border p-4', 'border-green-200 bg-green-50' => $done > 0 && ! $skipped, 'border-amber-200 bg-amber-50' => $skipped && $done > 0, 'border-red-200 bg-red-50' => $done === 0])>
    <div class="flex items-start justify-between gap-3">
        <p @class(['text-sm font-semibold', 'text-green-900' => $done > 0 && ! $skipped, 'text-amber-900' => $skipped && $done > 0, 'text-red-900' => $done === 0])>
            @if ($done > 0){{ ucfirst(\App\Support\Staff\BulkActions::count($result['noun'], $done)) }} {{ $result['past'] }}.@else{{ __('Nothing was changed.') }}@endif
            @if ($skipped){{ ' '.__(':n skipped:', ['n' => count($skipped)]) }}@endif
        </p>
        <button type="button" @click="open = false" class="rounded p-0.5 text-neutral-500 hover:text-neutral-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40" aria-label="{{ __('Dismiss') }}"><x-icon name="x-mark" class="h-4 w-4" /></button>
    </div>
    @if ($skipped)
        <ul class="mt-2 space-y-1 text-sm text-neutral-800">
            @foreach ($skipped as $item)<li><span class="font-medium">{{ $item['name'] }}</span> <span class="text-neutral-600">— {{ $item['why'] }}</span></li>@endforeach
        </ul>
    @endif
</div>
