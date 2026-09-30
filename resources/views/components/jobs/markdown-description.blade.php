@props(['content', 'secondaryFont' => false])

{{-- Poster-written Markdown, shown to every visitor: raw HTML is stripped and unsafe links dropped. --}}
<x-jobs.prose :secondary-font="$secondaryFont" {{ $attributes }}>
    {!! \Illuminate\Support\Str::markdown((string) $content, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
</x-jobs.prose>
