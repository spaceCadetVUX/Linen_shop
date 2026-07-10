<?php

namespace App\Support;

class RichContentHtml
{
    /**
     * Clamps heading tags in rendered rich-content HTML to a minimum level.
     *
     * Every public page already owns a single <h1> (title/banner) outside this
     * block, so admin-authored long-form content (category/blog rich_content,
     * blog post body) must never emit another <h1> — duplicate H1 is an
     * on-page SEO defect regardless of how the heading got there. Filament's
     * RichEditor toolbar only exposes h2/h3, but pasted HTML and MCP-authored
     * content can still carry an h1 straight through, so this is enforced at
     * render time rather than relying on the editor UI alone.
     */
    public static function capHeadingLevels(string $html, int $min = 2): string
    {
        return (string) preg_replace_callback(
            '/<(\/?)h([1-6])((?:\s[^>]*)?)>/i',
            fn (array $m) => '<'.$m[1].'h'.max((int) $m[2], $min).$m[3].'>',
            $html
        );
    }
}
