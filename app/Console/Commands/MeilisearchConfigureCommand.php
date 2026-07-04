<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Meilisearch\Client as MeilisearchClient;
use Throwable;

class MeilisearchConfigureCommand extends Command
{
    protected $signature = 'meilisearch:configure';

    protected $description = 'Configure Meilisearch index settings (searchable, filterable, sortable attributes)';

    public function handle(MeilisearchClient $client): int
    {
        // Single source of truth — scout.meilisearch.index-settings, the same
        // config scout:sync-index-settings reads. Keys already carry the prefix.
        $indexes = config('scout.meilisearch.index-settings', []);

        if (empty($indexes)) {
            $this->error('No index settings found in scout.meilisearch.index-settings.');

            return self::FAILURE;
        }

        foreach ($indexes as $indexName => $settings) {
            $this->line("Configuring index: <info>{$indexName}</info>");

            try {
                $index = $client->index($indexName);
                $index->updateSearchableAttributes($settings['searchableAttributes']);
                $index->updateFilterableAttributes($settings['filterableAttributes']);
                $index->updateSortableAttributes($settings['sortableAttributes']);
                $this->line('  <comment>✓</comment> Settings applied.');
            } catch (Throwable $e) {
                $this->error("  Failed: {$e->getMessage()}");

                return self::FAILURE;
            }
        }

        $this->info('Meilisearch indexes configured successfully.');

        return self::SUCCESS;
    }
}
