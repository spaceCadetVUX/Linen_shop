<?php

namespace App\Support;

use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Model;

/**
 * Single source of truth for "should this model's public SEO surfaces
 * (sitemap, JSON-LD, LLMs) be active" — used by SitemapService, JsonldService,
 * and LlmsGeneratorService so the rule only needs to be correct in one place.
 *
 * Most models expose a plain `is_active` column. BlogPost is the one
 * schedulable exception: status=Published alone isn't enough, published_at
 * must have passed too (see BlogPost::isPubliclyVisible()).
 */
final class SeoVisibility
{
    public static function isActive(Model $model): bool
    {
        if ($model instanceof BlogPost) {
            return $model->isPubliclyVisible();
        }

        return (bool) ($model->getAttribute('is_active') ?? true);
    }
}
