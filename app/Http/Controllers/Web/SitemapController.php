<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Seo\SitemapIndex;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
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

        $entries = $index->entries()
            ->where('is_active', true)
            ->orderBy('url')
            ->get();

        return response()
            ->view('sitemap.child', compact('entries', 'locale', 'type'))
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
