<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Models\ProductTranslation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ProductRepository extends BaseRepository
{
    protected function model(): string
    {
        return Product::class;
    }

    // ── PLP / category search (locale-aware, filter groups, brand, keyword) ────

    /**
     * SQL fallback for the storefront listing (PLP + category pages).
     * This is the source of truth for the query shape — ProductSearchService's
     * Meilisearch path must return equivalent results for the same inputs.
     *
     * @param  EloquentCollection<int, \App\Models\FilterGroup>  $filterGroups  active groups with activeValues loaded
     * @param  array<string, array<int, string>>  $activeValueSlugs  [group_slug => [value_slug, ...]]
     */
    public function searchWithFiltersSql(
        string $locale,
        string $keyword,
        EloquentCollection $filterGroups,
        array $activeValueSlugs,
        string $brandSlug,
        ?string $categoryId,
        int $perPage,
    ): LengthAwarePaginator {
        $query = ProductTranslation::where('locale', $locale)
            ->whereHas('product', function ($q) use ($categoryId) {
                $q->active();
                if ($categoryId) {
                    $q->whereHas('categories', fn ($q2) => $q2->where('categories.id', $categoryId));
                }
            })
            ->with([
                'product.thumbnail',
                'product.brand',
                'product.categories' => fn ($q) => $q->orderBy('sort_order'),
                'product.categories.translations' => fn ($q) => $q->where('locale', $locale),
            ]);

        // Each active group is AND-ed; values within a group are OR-ed.
        foreach ($filterGroups as $group) {
            if (empty($activeValueSlugs[$group->slug])) {
                continue;
            }
            $valueSlugs = $activeValueSlugs[$group->slug];
            $query->whereHas(
                'product.filterValues',
                fn ($q) => $q->where('filter_group_id', $group->id)
                             ->whereIn('filter_values.slug', $valueSlugs)
            );
        }

        if ($brandSlug) {
            $query->whereHas('product.brand', fn ($q) => $q->where('slug', $brandSlug));
        }

        if ($keyword) {
            $query->where(fn ($q) => $q
                ->where('name', 'ilike', "%{$keyword}%")
                ->orWhere('short_description', 'ilike', "%{$keyword}%"));
        }

        return $query->orderBy('id', 'desc')->paginate($perPage)->withQueryString();
    }

    /**
     * ProductTranslation rows for a given set of product IDs, re-ordered to
     * match $orderedProductIds (the relevance order Meilisearch returned).
     * Used by ProductSearchService to reshape a Meilisearch hit list into the
     * same ProductTranslation collection shape the SQL path returns.
     *
     * @param  array<int, string>  $orderedProductIds
     */
    public function translationsForProductIdsInOrder(array $orderedProductIds, string $locale): Collection
    {
        if (empty($orderedProductIds)) {
            return collect();
        }

        $translations = ProductTranslation::where('locale', $locale)
            ->whereIn('product_id', $orderedProductIds)
            ->with([
                'product.thumbnail',
                'product.brand',
                'product.categories' => fn ($q) => $q->orderBy('sort_order'),
                'product.categories.translations' => fn ($q) => $q->where('locale', $locale),
            ])
            ->get()
            ->keyBy('product_id');

        return collect($orderedProductIds)
            ->map(fn ($id) => $translations->get($id))
            ->filter();
    }

    // ── List ──────────────────────────────────────────────────────────────────

    /**
     * Paginated active product list with filters.
     *
     * Supported filters:
     *   category   string  — filter by category slug
     *   sort       string  — price_asc | price_desc | name_asc | name_desc | newest
     *   min_price  numeric — price >=
     *   max_price  numeric — price <=
     *   in_stock   bool    — stock_quantity > 0 only
     */
    public function paginate(int $perPage = 15, array $filters = [], array $with = []): LengthAwarePaginator
    {
        $query = $this->query()
            ->with($with ?: ['categories', 'thumbnail'])
            ->where('is_active', true);

        if (! empty($filters['category'])) {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $filters['category']));
        }

        if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        if (! empty($filters['in_stock'])) {
            $query->where('stock_quantity', '>', 0);
        }

        match ($filters['sort'] ?? null) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc'   => $query->orderBy('name', 'asc'),
            'name_desc'  => $query->orderBy('name', 'desc'),
            'newest'     => $query->orderBy('created_at', 'desc'),
            default      => $query->orderBy('name', 'asc'),
        };

        return $query->paginate($perPage);
    }

    // ── Detail ────────────────────────────────────────────────────────────────

    /**
     * Single active product by slug with all detail relations.
     */
    public function findActiveBySlug(string $slug): ?Product
    {
        /** @var Product|null */
        return $this->query()
            ->with(['categories', 'images', 'videos', 'translations', 'seoMetas', 'activeSchemas'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }
}
