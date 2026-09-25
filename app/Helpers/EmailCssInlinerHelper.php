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

        return (new CssToInlineStyles)->convert($html);
    }
}
