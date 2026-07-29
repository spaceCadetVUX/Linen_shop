<?php

namespace App\Support;

use App\Models\Seo\Redirect;

/**
 * Guards against reusing a slug that is currently the `from_path` of an
 * active redirect pointing somewhere else — without this, HandleRedirects
 * middleware (which checks the redirects table before routing reaches the
 * real record) would silently 301 traffic away from the new record's own
 * canonical URL toward whatever the stale redirect still targets.
 */
class SlugRedirectGuard
{
    /**
     * Returns the conflicting active Redirect at $path, or null if the path
     * is free to use.
     *
     * $currentSlug — the record's own slug value before this edit — is
     * excluded from conflicting, so a record can reclaim a slug it
     * previously moved away from without tripping over its own old redirect.
     */
    public static function conflictAt(string $path, ?string $currentSlug = null): ?Redirect
    {
        $conflict = Redirect::query()
            ->where('from_path', $path)
            ->where('is_active', true)
            ->first();

        if (! $conflict) {
            return null;
        }

        if ($currentSlug && str_ends_with(rtrim($conflict->to_path, '/'), '/'.$currentSlug)) {
            return null;
        }

        return $conflict;
    }
}
