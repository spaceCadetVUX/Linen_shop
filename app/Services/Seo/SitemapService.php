<?php

namespace App\Services\Seo;

use App\Enums\SitemapChangefreq;
use App\Models\BlogPost;
use App\Models\Seo\SitemapEntry;
use App\Models\Seo\SitemapIndex;
use App\Support\LocaleUrl;
use App\Support\SeoVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class SitemapService
{
    /**
     * Morph alias → SEO defaults for sitemap entries.
     * URL generation uses LocaleUrl::for() — not route() — so paths match config/localeurl.php.
     *
     * 'brand' / 'manufacturer' deliberately NOT listed here: BrandObserver/
     * ManufacturerObserver already dispatch SyncSitemapEntry on save (per
     * HasSitemapEntry trait), but there is no Web route rendering a brand/
     * manufacturer detail page yet (only Api\V1\Catalog\*Controller exists) —
     * config/localeurl.php's 'brand'/'manufacturer' paths are reserved for
     * that future page. Adding them here now would publish 404 URLs into
     * the sitemap. Add once the Web show page ships.
     */
    private const MODEL_CONFIG = [
        'product' => ['changefreq' => SitemapChangefreq::Daily,  'priority' => 0.8],
        'blog_post' => ['changefreq' => SitemapChangefreq::Weekly, 'priority' => 0.6],
        'category' => ['changefreq' => SitemapChangefreq::Weekly, 'priority' => 0.7],
        'blog_category' => ['changefreq' => SitemapChangefreq::Weekly, 'priority' => 0.5],
    ];

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Refresh entry_count and last_generated_at from active sitemap_entries.
     *
     * The sitemap is served live from the DB (SitemapController::child()
     * renders resources/views/sitemap/child.blade.php on every request), so
     * this does NOT write an XML file to disk — a prior version did, but
     * nothing ever read that file (the controller queries the DB directly),
     * making the write pure overhead on every entry upsert. Kept as the one
     * place that recomputes these two stat columns, called by
     * `sitemap:generate`, the Filament "Regenerate" action, and upsertEntry().
     */
    public function generateChild(SitemapIndex $index): void
    {
        $index->update([
            'entry_count' => SitemapEntry::where('sitemap_index_id', $index->id)
                ->where('is_active', true)
                ->count(),
            'last_generated_at' => now(),
        ]);
    }

    /**
     * Upsert a single sitemap_entries row for a model + locale.
     * Finds the correct SitemapIndex by model class + locale.
     * Skips if no translation exists for the given locale.
     * Builds alternate_urls jsonb for hreflang xlinks.
     */
    public function upsertEntry(Model $model, ?SitemapIndex $index = null, string $locale = 'vi'): void
    {
        $morphAlias = $model->getMorphClass();
        $config = self::MODEL_CONFIG[$morphAlias] ?? null;

        if ($config === null) {
            return;
        }

        $index ??= SitemapIndex::where('model_type', $morphAlias)
            ->where('locale', $locale)
            ->first();

        if ($index === null) {
            Log::warning('SitemapService: no active sitemap_indexes row for model_type/locale — entry not synced.', [
                'model_type' => $morphAlias,
                'locale' => $locale,
                'model_id' => $model->getKey(),
            ]);

            return;
        }

        // For translated models: require a locale translation — missing = remove stale entry.
        // For non-translated models (Brand, Manufacturer): fall through using model slug directly.
        $hasTranslations = method_exists($model, 'translation');
        $translation = $hasTranslations ? $model->translation($locale) : null;

        if ($hasTranslations && $translation === null) {
            SitemapEntry::where('sitemap_index_id', $index->id)
                ->where('model_type', $morphAlias)
                ->where('model_id', $model->getKey())
                ->delete();

            return;
        }

        $slug = (string) ($translation?->slug ?? $model->getAttribute('slug') ?? '');

        if ($slug === '') {
            return;
        }

        $isBlogPost = $morphAlias === 'blog_post' && $model instanceof BlogPost;
        $url = $isBlogPost
            ? LocaleUrl::forBlogPost($model, $locale)
            : LocaleUrl::for($morphAlias, $slug, $locale);

        if ($url === '') {
            return;
        }

        // Build alternate_urls for hreflang xlinks.
        $alternateUrls = [];
        foreach (config('app.supported_locales') as $altLocale) {
            if ($isBlogPost) {
                $altUrl = LocaleUrl::forBlogPost($model, $altLocale);
                if ($altUrl !== '') {
                    $alternateUrls[$altLocale] = $altUrl;
                }
            } elseif ($hasTranslations) {
                $altTranslation = $model->translation($altLocale);
                if ($altTranslation) {
                    $alternateUrls[$altLocale] = LocaleUrl::for($morphAlias, $altTranslation->slug, $altLocale);
                }
            } else {
                $alternateUrls[$altLocale] = LocaleUrl::for($morphAlias, $slug, $altLocale);
            }
        }

        $isActive = SeoVisibility::isActive($model);

        SitemapEntry::updateOrCreate(
            [
                'sitemap_index_id' => $index->id,
                'model_type' => $morphAlias,
                'model_id' => $model->getKey(),
            ],
            [
                'locale' => $locale,
                'url' => $url,
                'alternate_urls' => $alternateUrls ?: null,
                'changefreq' => $config['changefreq'],
                'priority' => $config['priority'],
                'last_modified' => $model->updated_at ?? now(),
                'is_active' => $isActive,
            ]
        );

        $this->generateChild($index);
    }
}
