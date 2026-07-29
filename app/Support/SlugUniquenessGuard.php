<?php

namespace App\Support;

/**
 * Checks whether a candidate (locale, slug) pair is already used by a
 * DIFFERENT parent record on a translation table with a unique(locale, slug)
 * constraint (product_translations, category_translations,
 * blog_post_translations, blog_category_translations).
 *
 * Without this, MCP write paths that insert/update a translation slug hit the
 * DB unique constraint directly on a genuine collision — an uncaught
 * QueryException that surfaces as an opaque 500 instead of a clean 422.
 */
class SlugUniquenessGuard
{
    /**
     * @param  class-string  $translationModelClass
     */
    public static function takenByOther(
        string $translationModelClass,
        string $parentForeignKey,
        mixed $parentId,
        string $locale,
        string $slug,
    ): bool {
        return $translationModelClass::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where($parentForeignKey, '!=', $parentId)
            ->exists();
    }
}
