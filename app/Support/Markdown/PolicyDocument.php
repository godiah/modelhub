<?php

namespace App\Support\Markdown;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Turns a markdown guide into what the policy pages show: a title, an introduction and numbered sections, in their look. Used for the member
 * payments guide and the staff handbook; the markdown file is the single source of the text.
 */
final class PolicyDocument
{
    /**
     * Markdown to the same look as the policy pages: the part before the first "##" is the introduction, each "##" is a numbered section, and the
     * elements inside are given the policy pages' classes here (the markdown has none), so nothing new has to be added to the stylesheet.
     *
     * @return array{title: string, intro: string, sections: array<string, array{title: string, html: string}>}
     */
    public static function render(string $markdown, bool $showNotes = true): array
    {
        // Drafting notes ("[CONFIRM: ...]") are for the people reviewing a document; they are left out of what the public reads
        if (! $showNotes) {
            $markdown = preg_replace('/\s*\*\*\[CONFIRM[^\]]*\]\*\*|\s*\[CONFIRM[^\]]*\]/', '', $markdown);
        }

        $environment = new Environment(['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);

        $dom = new \DOMDocument;
        $dom->loadHTML('<?xml encoding="utf-8" ?><div id="doc">'.(new MarkdownConverter($environment))->convert($markdown).'</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($dom);

        $style = function (string $query, string $classes) use ($xpath) {
            foreach ($xpath->query($query) as $element) {
                $element->setAttribute('class', $classes);
            }
        };

        $style('//strong', 'font-semibold text-neutral-900');
        $style('//a', 'font-medium text-teal-700 underline');
        $style('//code', 'rounded bg-neutral-100 px-1 py-0.5 text-xs');
        $style('//h3', 'pt-2 font-semibold text-neutral-900');
        $style('//blockquote', 'rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900');
        $style('//th', 'bg-neutral-50 px-3 py-2 font-semibold text-neutral-900');
        $style('//td', 'border-t border-neutral-100 px-3 py-2 align-top');
        $style('//table', 'w-full text-left text-sm');

        foreach ($xpath->query('//table') as $table) {
            $wrap = $dom->createElement('div');
            $wrap->setAttribute('class', 'overflow-x-auto rounded-xl border border-neutral-200');
            $table->parentNode->replaceChild($wrap, $table);
            $wrap->appendChild($table);
        }

        $title = '';
        foreach ($xpath->query('//h1') as $h1) {
            $title = $title ?: trim($h1->textContent);
            $h1->parentNode->removeChild($h1);
        }

        // Cut the document at its "##" headings
        $intro = '';
        $sections = [];
        $current = null;
        $used = [];

        foreach (iterator_to_array($dom->getElementById('doc')->childNodes) as $node) {
            if ($node instanceof \DOMElement && $node->tagName === 'h2') {
                $label = trim(preg_replace('/^\d+\.\s*/', '', $node->textContent));
                $base = Str::slug($label) ?: 'section';
                $used[$base] = ($used[$base] ?? 0) + 1;
                $current = $used[$base] > 1 ? "{$base}-{$used[$base]}" : $base;
                $sections[$current] = ['title' => $label, 'html' => ''];

                continue;
            }

            if ($node instanceof \DOMElement && $node->tagName === 'hr') {
                continue;
            }

            $html = $node instanceof \DOMElement && in_array($node->tagName, ['ul', 'ol'], true) ? self::list($dom, $node) : $dom->saveHTML($node);

            if ($current === null) {
                $intro .= $html;
            } else {
                $sections[$current]['html'] .= $html;
            }
        }

        if (! $showNotes) {
            $intro = preg_replace('#^\s*<blockquote[^>]*>.*?</blockquote>#s', '', $intro, 1);
        }

        return ['title' => $title, 'intro' => trim($intro), 'sections' => $sections];
    }

    /** A markdown list in the policy pages' look: ticks for bullets, numbered badges for steps. Nested lists are indented. */
    private static function list(\DOMDocument $dom, \DOMElement $list, bool $nested = false): string
    {
        $ordered = $list->tagName === 'ol';
        $tick = Blade::render('<x-icon name="check-circle-2" class="mt-0.5 h-4 w-4 shrink-0 text-teal-600" />');
        $html = '<'.$list->tagName.' class="'.($nested ? 'mt-2.5 ' : '').'space-y-2.5">';
        $number = 0;

        foreach ($list->childNodes as $item) {
            if (! $item instanceof \DOMElement || $item->tagName !== 'li') {
                continue;
            }

            $inline = '';
            $nestedHtml = '';

            foreach ($item->childNodes as $child) {
                if ($child instanceof \DOMElement && in_array($child->tagName, ['ul', 'ol'], true)) {
                    $nestedHtml .= self::list($dom, $child, true);
                } elseif ($child instanceof \DOMElement && $child->tagName === 'p') {
                    foreach ($child->childNodes as $grandchild) {
                        $inline .= $dom->saveHTML($grandchild);
                    }
                    $inline .= ' ';
                } else {
                    $inline .= $dom->saveHTML($child);
                }
            }

            $marker = $ordered
                ? '<span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-50 text-xs font-semibold tabular-nums text-teal-700">'.(++$number).'</span>'
                : $tick;

            $html .= '<li class="flex items-start gap-2.5">'.$marker.'<span class="min-w-0">'.trim($inline).$nestedHtml.'</span></li>';
        }

        return $html.'</'.$list->tagName.'>';
    }
}
