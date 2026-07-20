<?php

namespace App\Console\Commands;

use App\Models\Seo\SitemapIndex;
use App\Services\Seo\SitemapService;
use Illuminate\Console\Command;

class SitemapGenerateCommand extends Command
{
    protected $signature = 'sitemap:generate
                            {--index= : Specific sitemap index name to regenerate (e.g. products, blog, categories)}';

    // Sitemaps are served live from the DB (SitemapController::child()) — this
    // command does NOT write any XML file, it only refreshes the entry_count/
    // last_generated_at stats shown in the Filament sitemap_indexes list.
    protected $description = 'Refresh entry_count/last_generated_at stats on sitemap indexes';

    public function handle(SitemapService $service): int
    {
        $indexName = $this->option('index');

        if ($indexName) {
            $index = SitemapIndex::where('name', $indexName)->first();

            if ($index === null) {
                $this->error("Sitemap index '{$indexName}' not found.");

                return self::FAILURE;
            }

            $this->info("Refreshing stats: {$index->filename} ...");
            $service->generateChild($index);
            $index->refresh();
            $this->info("Done — {$index->entry_count} active entries for {$index->filename} (served live from DB).");

            return self::SUCCESS;
        }

        // Refresh stats for all active child sitemaps with per-index progress output.
        $indexes = SitemapIndex::where('is_active', true)->get();

        if ($indexes->isEmpty()) {
            $this->warn('No active sitemap indexes found.');

            return self::SUCCESS;
        }

        $this->info("Refreshing stats for {$indexes->count()} sitemap(s)...");

        foreach ($indexes as $index) {
            $this->line("  → {$index->filename}");
            $service->generateChild($index);
            $index->refresh();
            $this->line("    {$index->entry_count} entries, last_generated: {$index->last_generated_at->toDateTimeString()}");
        }

        $this->info('All sitemap stats refreshed.');

        return self::SUCCESS;
    }
}
