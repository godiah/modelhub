<?php

/**
 * EmailCssInlinerHelper
 *
 * Inlines an email's own <style> block into each element's style="" attribute, so
 * mail clients that strip <head><style> content (some webmail proxies, older Outlook)
 * still render it correctly. Deliberately does NOT pass Laravel's own default markdown
 * mail theme CSS into the inliner — verified (2026-09-25) that doing so silently
 * overrides this app's own emails.layouts.master colors/fonts with Laravel's bundled
 * theme defaults, which is not what any of these emails want.
 *
 * A <style data-embed> block is left exactly as written (media queries cannot be inlined).
 */

namespace App\Helpers;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class EmailCssInlinerHelper
{
    public static function inline(?string $html): ?string
    {
        if (! $html || ! str_contains($html, '<style')) {
            return $html;
        }

        // <style data-embed> blocks (media queries) cannot be inlined; set them aside and put them back untouched.
        $kept = [];
        $html = preg_replace_callback('/<style\b[^>]*\bdata-embed\b[^>]*>.*?<\/style>/is', function ($match) use (&$kept) {
            $kept[] = $match[0];

            return '<!--keep-style-'.(count($kept) - 1).'-->';
        }, $html);

        $html = (new CssToInlineStyles)->convert($html);

        foreach ($kept as $index => $block) {
            $html = str_replace('<!--keep-style-'.$index.'-->', $block, $html);
        }

        return $html;
    }
}
