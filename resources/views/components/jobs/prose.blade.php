@props(['secondaryFont' => false])

{{-- Typography for Markdown a poster wrote: shared by the published description and the editor's preview. --}}
<div {{ $attributes->class([
    'text-sm leading-relaxed text-neutral-700',
    '[&_p]:mb-4 [&_p:last-child]:mb-0',
    '[&_h1]:mb-3 [&_h1]:mt-6 [&_h1]:font-tertiary [&_h1]:text-lg [&_h1]:font-semibold [&_h1]:text-neutral-900',
    '[&_h2]:mb-2 [&_h2]:mt-5 [&_h2]:font-tertiary [&_h2]:text-base [&_h2]:font-semibold [&_h2]:text-neutral-900',
    '[&_h3]:mb-2 [&_h3]:mt-4 [&_h3]:font-tertiary [&_h3]:font-semibold [&_h3]:text-neutral-900',
    '[&_ul]:mb-4 [&_ul]:list-disc [&_ul]:pl-5 [&_ul>li]:mb-1.5',
    '[&_ol]:mb-4 [&_ol]:list-decimal [&_ol]:pl-5 [&_ol>li]:mb-1.5',
    '[&_a]:font-medium [&_a]:text-teal-700 [&_a]:underline',
    '[&_blockquote]:border-l-4 [&_blockquote]:border-neutral-200 [&_blockquote]:pl-4 [&_blockquote]:text-neutral-600',
    '[&_p]:font-secondary [&_ul]:font-secondary [&_ol]:font-secondary' => $secondaryFont,
]) }}>
    {{ $slot }}
</div>
