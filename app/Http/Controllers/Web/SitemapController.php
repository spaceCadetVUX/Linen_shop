<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Seo\SitemapIndex;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class SitemapController extends Controller
{
    // https://www.sitemaps.org/protocol.html — hard cap per sitemap file.
    private const MAX_URLS_PER_SITEMAP = 50000;

    /**
     * Serve the master sitemap index (sitemap.xml).
     * Lists all 8 active child sitemaps (vi/en × 4 types).
     */
    public function index(): Response
    {
        $indexes = SitemapIndex::where('is_active', true)
            ->orderBy('name')
            ->get();

        // sitemap-static.xml has no sitemap_indexes row (hardcoded pages, not
        // DB-driven) — inject it manually so home/about/shop/blog-index stay
        // discoverable through the master index crawlers actually read.
        $staticUrl = url('sitemap-static.xml');

        return response()
            ->view('sitemap.index', compact('indexes', 'staticUrl'))
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Serve static pages sitemap (sitemap-static.xml).
     * Hardcoded list of pages not tied to any DB model.
     */
    public function static(): Response
    {
        $base = rtrim(config('app.url'), '/');

        $pages = [
            // Home
            ['vi' => "$base/vi", 'en' => "$base/en", 'priority' => '1.0', 'changefreq' => 'daily'],
            // About
            ['vi' => "$base/vi/gioi-thieu", 'en' => "$base/en/about", 'priority' => '0.7', 'changefreq' => 'monthly'],
            // Shop
            ['vi' => "$base/vi/cua-hang", 'en' => "$base/en/shop", 'priority' => '0.8', 'changefreq' => 'daily'],
            // Blog index
            ['vi' => "$base/vi/bai-viet", 'en' => "$base/en/blog", 'priority' => '0.7', 'changefreq' => 'daily'],
        ];

        return response()
            ->view('sitemap.static', compact('pages'))
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    /**
     * Serve a child sitemap (sitemap-{locale}-{type}.xml).
     * Renders live from sitemap_entries with hreflang xlinks.
     */
    public function child(string $locale, string $type): Response
    {
        $index = SitemapIndex::where('name', "{$locale}-{$type}")
            ->where('is_active', true)
            ->firstOrFail();

        // +1 sentinel row so we can detect an over-limit sitemap without
        // pulling every row into memory when the table is large.
        $entries = $index->entries()
            ->where('is_active', true)
            ->orderBy('url')
            ->limit(self::MAX_URLS_PER_SITEMAP + 1)
            ->get();

        // Sitemap protocol hard limit: 50,000 URLs per file. Truncate + log
        // rather than ship a file crawlers may reject outright — splitting
        // into multiple numbered sitemaps is real work with no need at this
        // catalog's current scale, so this is a safety net, not a fix.
        if ($entries->count() > self::MAX_URLS_PER_SITEMAP) {
            // $entries is already capped at MAX+1 by the query above — a plain
            // COUNT (no rows fetched) is needed to log the real total.
            $trueCount = $index->entries()->where('is_active', true)->count();

            Log::warning("Sitemap '{$index->name}' exceeds the ".self::MAX_URLS_PER_SITEMAP.'-URL protocol limit — truncating.', [
                'index_id' => $index->id,
                'entry_count' => $trueCount,
            ]);

            $entries = $entries->take(self::MAX_URLS_PER_SITEMAP);
        }

        return response()
            ->view('sitemap.child', compact('entries', 'locale', 'type'))
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
