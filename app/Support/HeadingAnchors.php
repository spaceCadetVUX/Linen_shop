<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Injects unique id="" anchors into <h2>/<h3> tags of rendered article HTML
 * so Google "jump to section" and AI answer engines can cite a specific
 * heading. Rewrites only the heading tags themselves via regex rather than
 * a full DOM reparse, so table/list/image markup produced by the Tiptap
 * converter is left byte-identical.
 */
class HeadingAnchors
{
    public static function inject(string $html): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $seen = [];

        return preg_replace_callback(
            '/<(h2|h3)([^>]*)>(.*?)<\/\1>/is',
            function (array $m) use (&$seen): string {
                [, $tag, $attrs, $inner] = $m;

                if (preg_match('/\bid\s*=/i', $attrs)) {
                    return $m[0];
                }

                $slug = Str::slug(trim(strip_tags($inner))) ?: 'section';
                $id = $slug;
                $i = 2;
                while (isset($seen[$id])) {
                    $id = $slug.'-'.$i++;
                }
                $seen[$id] = true;

                return "<{$tag}{$attrs} id=\"{$id}\">{$inner}</{$tag}>";
            },
            $html
        ) ?? $html;
    }
}
