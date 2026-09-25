@props(['content', 'secondaryFont' => false])

<div @class([
    "text-neutral-700 [&>h1]:text-xl [&>h1]:font-tertiary [&>h1]:font-bold [&>h1]:text-primary [&>h1]:mt-6 [&>h1]:mb-4
        [&>h2]:text-lg [&>h2]:font-tertiary [&>h2]:font-semibold [&>h2]:text-primary/90 [&>h2]:mt-5 [&>h2]:mb-3
        [&>h3]:text-base [&>h3]:font-tertiary [&>h3]:font-medium [&>h3]:text-neutral-800 [&>h3]:mt-4 [&>h3]:mb-2
        [&>p]:text-base [&>p]:leading-relaxed [&>p]:text-neutral-700 [&>p]:mb-4
        [&>ul]:list-disc [&>ul]:pl-5 [&>ul]:mb-4 [&>ul]:text-neutral-700
        [&>ol]:list-decimal [&>ol]:pl-5 [&>ol]:mb-4 [&>ol]:text-neutral-700
        [&>li]:mb-2 [&>a]:text-secondary [&>a]:underline [&>a]:font-medium [&>p]:text-justify",
    '[&>p]:font-secondary [&>ul]:font-secondary [&>ol]:font-secondary' => $secondaryFont,
])>
    {!! Str::markdown($content) !!}
</div>
