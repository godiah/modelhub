@php
    $labels = collect($sections)->map(fn ($section) => $section['title'])->all();
@endphp
<x-staff-layout :title="$title">
    {{-- The same document layout as the member-facing policy pages: header card, a contents list that follows the reader, numbered sections --}}
    <x-legal.document :kicker="__('Staff handbook')" :title="$title" :sections="$labels"
        :intro="__('An internal guide for the people who run payments on :app. It is not shown to members.', ['app' => config('app.name')])"
        :updated="$updated->format('F j, Y')">
        @if ($intro !== '')
            <div class="mb-2 space-y-4 pb-6 text-sm leading-relaxed text-neutral-700">{!! $intro !!}</div>
        @endif

        @foreach ($sections as $id => $section)
            <x-policy.section :id="$id" :number="$loop->iteration" :title="$section['title']">
                {!! $section['html'] !!}
            </x-policy.section>
        @endforeach
    </x-legal.document>
</x-staff-layout>
