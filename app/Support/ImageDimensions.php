<?php

namespace App\Support;

/**
 * Reads intrinsic pixel dimensions of a locally-stored public image, given
 * either its raw storage-relative path ("blog/2026/x.jpg") or a full URL
 * that embeds a "/storage/" segment. Never fetches over HTTP — returns null
 * for external images so callers don't pay a remote round-trip per pageview.
 */
class ImageDimensions
{
    public static function resolve(?string $pathOrUrl): ?array
    {
        if (blank($pathOrUrl)) {
            return null;
        }

        $relative = $pathOrUrl;

        if (str_contains($pathOrUrl, '://')) {
            $marker = '/storage/';
            $pos = strpos($pathOrUrl, $marker);

            if ($pos === false) {
                return null;
            }

            $relative = substr($pathOrUrl, $pos + strlen($marker));
        }

        $fullPath = storage_path('app/public/'.ltrim($relative, '/'));

        if (! file_exists($fullPath)) {
            return null;
        }

        $size = @getimagesize($fullPath);

        if (! $size) {
            return null;
        }

        return ['width' => $size[0], 'height' => $size[1]];
    }
}
