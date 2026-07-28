<?php

namespace App\Support;

use Tiptap\Editor;
use Tiptap\Extensions\StarterKit;
use Tiptap\Nodes\Image as TiptapImage;
use Tiptap\Nodes\Table;
use Tiptap\Nodes\TableCell;
use Tiptap\Nodes\TableHeader;
use Tiptap\Nodes\TableRow;

class RichContentHtml
{
    /**
     * Convert plain HTML into the Tiptap JSON node-tree that
     * category_translations.rich_content actually stores (an 'array' cast
     * column, rendered back to HTML via the same extension set in
     * CategoryController — kept identical here so the round-trip stays
     * consistent). Shared by the Filament admin save path and MCP so
     * neither one hand-writes Tiptap JSON or silently stores raw HTML.
     */
    public static function toTiptapJson(string $html): array
    {
        return (new Editor(['extensions' => [
            new StarterKit,
            new TiptapImage,
            new Table,
            new TableRow,
            new TableHeader,
            new TableCell,
        ]]))->setContent($html)->getDocument();
    }

    /**
     * Convert a stored rich_content value back to displayable/editable HTML.
     *
     * Accepts the Tiptap JSON node-tree (the normal shape after the
     * HTML->JSON conversion fix, and what CategorySeeder writes directly)
     * and renders it through the same Tiptap PHP Editor used everywhere
     * else. Also tolerates a plain string — rows saved before that fix
     * exist stored rich_content as a JSON-encoded HTML string (the 'array'
     * cast round-trips it back to a string, not an array); since that
     * string already *is* HTML, it's returned as-is rather than treated as
     * empty, so previously-invisible content on the public page recovers
     * instead of staying silently blank.
     */
    public static function toHtml(mixed $rawContent): ?string
    {
        if (empty($rawContent)) {
            return null;
        }

        if (is_string($rawContent)) {
            return $rawContent;
        }

        if (! is_array($rawContent)) {
            return null;
        }

        try {
            $html = (new Editor(['extensions' => [
                new StarterKit,
                new TiptapImage,
                new Table,
                new TableRow,
                new TableHeader,
                new TableCell,
            ]]))->setContent($rawContent)->getHTML();
        } catch (\Throwable) {
            return null;
        }

        return trim(strip_tags($html)) === '' ? null : $html;
    }

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
