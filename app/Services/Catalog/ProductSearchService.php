<?php

namespace App\Services\Catalog;

use App\Models\FilterGroup;
use App\Models\Product;
use App\Repositories\Eloquent\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PLP / category listing search: Meilisearch first, SQL fallback on failure.
 *
 * Meilisearch is a derived, read-only projection of Postgres (kept in sync via
 * Scout — see Product::toSearchableArray()). Postgres stays the source of
 * truth; this service never writes to Meilisearch, only reads.
 *
 * Circuit breaker: after any Meilisearch failure, a short-lived cache flag
 * skips straight to SQL for CIRCUIT_TTL_SECONDS so a downed Meilisearch
 * doesn't add a slow-timeout tax to every request during an outage. The flag
 * simply expires — the next request after that retries Meilisearch.
 */
class ProductSearchService
{
    private const CIRCUIT_KEY = 'search:meili:down';

    private const CIRCUIT_TTL_SECONDS = 30;

    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    /**
     * @param  EloquentCollection<int, FilterGroup>  $filterGroups  active groups with activeValues loaded
     * @param  array<string, array<int, string>>  $activeValueSlugs  [group_slug => [value_slug, ...]]
     */
    public function search(
        string $locale,
        string $keyword,
        EloquentCollection $filterGroups,
        array $activeValueSlugs,
        string $brandSlug,
        ?string $categoryId,
        int $perPage = 24,
        ?float $minPrice = null,
        ?float $maxPrice = null,
    ): LengthAwarePaginator {
        // Brand isn't indexed in Meilisearch yet — go straight to SQL rather
        // than silently ignoring the brand filter on the Meilisearch path.
        if ($brandSlug === '' && ! Cache::has(self::CIRCUIT_KEY)) {
            try {
                return $this->searchMeilisearch($locale, $keyword, $filterGroups, $activeValueSlugs, $categoryId, $perPage, $minPrice, $maxPrice);
            } catch (\Throwable $e) {
                Cache::put(self::CIRCUIT_KEY, true, now()->addSeconds(self::CIRCUIT_TTL_SECONDS));
                Log::warning('Meilisearch unavailable, falling back to SQL for product search', [
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $this->productRepository->searchWithFiltersSql(
            $locale, $keyword, $filterGroups, $activeValueSlugs, $brandSlug, $categoryId, $perPage, $minPrice, $maxPrice,
        );
    }

    /**
     * Min/max price bounds for the PLP slider — cached briefly since price
     * changes are infrequent relative to page views and a few minutes of
     * stale bounds don't affect filter correctness (the actual query still
     * runs against live data).
     */
    public function getPriceBounds(?string $categoryId): array
    {
        $key = $categoryId ? "products:price_bounds:category:{$categoryId}" : 'products:price_bounds:all';

        return Cache::remember($key, 300, fn () => $this->productRepository->getPriceBounds($categoryId));
    }

    private function searchMeilisearch(
        string $locale,
        string $keyword,
        EloquentCollection $filterGroups,
        array $activeValueSlugs,
        ?string $categoryId,
        int $perPage,
        ?float $minPrice = null,
        ?float $maxPrice = null,
    ): LengthAwarePaginator {
        $page = max(1, (int) request()->query('page', 1));
        $offset = ($page - 1) * $perPage;
        $filter = $this->buildFilterExpression($filterGroups, $activeValueSlugs, $categoryId, $minPrice, $maxPrice);

        $raw = Product::search($keyword, function ($meilisearch, $query, $options) use ($locale, $filter, $offset, $perPage) {
            $options['filter'] = $filter;
            $options['attributesToSearchOn'] = ["name_{$locale}", "short_description_{$locale}"];
            $options['offset'] = $offset;
            $options['limit'] = $perPage;

            return $meilisearch->search($query, $options);
        })->raw();

        $orderedIds = array_column($raw['hits'] ?? [], 'id');
        $items = $this->productRepository->translationsForProductIdsInOrder($orderedIds, $locale);

        return new Paginator(
            $items->values(),
            $raw['estimatedTotalHits'] ?? count($orderedIds),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    /**
     * AND-ed between groups, OR-ed within a group — mirrors
     * ProductRepository::searchWithFiltersSql()'s whereHas semantics.
     */
    private function buildFilterExpression(
        EloquentCollection $filterGroups,
        array $activeValueSlugs,
        ?string $categoryId,
        ?float $minPrice = null,
        ?float $maxPrice = null,
    ): string {
        $clauses = ['is_active = true'];

        if ($categoryId) {
            // category_ids is indexed off Category::$id (keyType=string) — quoted.
            $clauses[] = "category_ids = \"{$categoryId}\"";
        }

        // effective_price is a Meilisearch-native numeric filter — no per-row
        // computation at query time, unlike the SQL fallback's LEAST() expression.
        if ($minPrice !== null) {
            $clauses[] = 'effective_price >= '.number_format($minPrice, 2, '.', '');
        }

        if ($maxPrice !== null) {
            $clauses[] = 'effective_price <= '.number_format($maxPrice, 2, '.', '');
        }

        foreach ($filterGroups as $group) {
            if (empty($activeValueSlugs[$group->slug])) {
                continue;
            }

            $valueIds = $group->activeValues
                ->filter(fn ($v) => in_array($v->slug, $activeValueSlugs[$group->slug], true))
                ->pluck('id');

            if ($valueIds->isEmpty()) {
                continue;
            }

            $clauses[] = '('.$valueIds->map(fn ($id) => "filter_value_ids = {$id}")->implode(' OR ').')';
        }

        return implode(' AND ', $clauses);
    }
}
