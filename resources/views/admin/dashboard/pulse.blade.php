<section aria-labelledby="pulse-heading" class="space-y-4">
    <div class="flex items-center justify-between gap-3">
        <h2 id="pulse-heading" class="font-tertiary text-lg font-semibold text-neutral-900">{{ __('The platform') }}</h2>
        <a href="{{ route('admin.overview') }}" wire:navigate class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('Full overview') }}</a>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        @foreach ($pulse['tiles'] as $tile)
            <x-stat-tile :label="__($tile['label'])" :value="$tile['value']" :hint="$tile['hint']" :icon="$tile['icon']" :trend="$tile['trend']" :series="$tile['series'] ? collect($tile['series'])->pluck('count')->all() : null" @class(['col-span-2 lg:col-span-1' => $loop->last]) />
        @endforeach
    </div>

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-3">
        <x-card @class(['p-5', 'lg:col-span-2' => $activity, 'lg:col-span-3' => ! $activity])>
            <x-trend-chart :series="$pulse['chart']" :title="__('New this week, by week')" />
        </x-card>
        @if ($activity)@include('admin.dashboard.activity')@endif
    </div>
</section>
