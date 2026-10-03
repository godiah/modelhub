@php
    $labels = collect($sections)->map(fn ($section) => $section['title'])->all();
@endphp
<x-app-layout :title="$title">
    <x-legal.document :kicker="__('Policy')" :title="$title" :sections="$labels"
        :intro="__('What things cost, when money is released, how refunds work, and what to do when something goes wrong, for everyone who buys, sells, hires or freelances on :app.', ['app' => config('app.name')])"
        :updated="$updated">
        @if ($intro !== '')
            <div class="space-y-4 pb-6 text-sm leading-relaxed text-neutral-700">{!! $intro !!}</div>
        @endif

        @foreach ($sections as $id => $section)
            <x-policy.section :id="$id" :number="$loop->iteration" :title="$section['title']">
                {!! $section['html'] !!}
            </x-policy.section>
        @endforeach
    </x-legal.document>
</x-app-layout>
