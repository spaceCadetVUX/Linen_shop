<?php

namespace App\Repositories\Eloquent;

use App\Models\FilterGroup;
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
     * @param  EloquentCollection<int, FilterGroup>  $filterGroups  active groups with activeValues loaded
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
        ?float $minPrice = null,
        ?float $maxPrice = null,
    ): LengthAwarePaginator {
        $query = ProductTranslation::where('locale', $locale)
            ->whereHas('product', function ($q) use ($categoryId, $minPrice, $maxPrice) {
                $q->active();
                if ($categoryId) {
                    $q->whereHas('categories', fn ($q2) => $q2->where('categories.id', $categoryId));
                }
                // LEAST(price, COALESCE(sale_price, price)) mirrors Product::toSearchableArray()'s
                // effective_price — the price the customer actually pays.
                if ($minPrice !== null) {
                    $q->whereRaw('LEAST(price, COALESCE(sale_price, price)) >= ?', [$minPrice]);
                }
                if ($maxPrice !== null) {
                    $q->whereRaw('LEAST(price, COALESCE(sale_price, price)) <= ?', [$maxPrice]);
                }
            })
            ->with([
                'product.images',
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
            // COALESCE mirrors the Meilisearch path's filter_value_names_en, which
            // is indexed with a vi-name fallback when name_en is null.
            $attributeNameSql = $locale === 'en'
                ? 'COALESCE(filter_values.name_en, filter_values.name)'
                : 'filter_values.name';

            $query->where(fn ($q) => $q
                ->where('name', 'ilike', "%{$keyword}%")
                ->orWhere('short_description', 'ilike', "%{$keyword}%")
                ->orWhereHas('product.filterValues', fn ($q2) => $q2
                    ->whereRaw("{$attributeNameSql} ILIKE ?", ["%{$keyword}%"])));
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
                'product.images',
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

    /**
     * Min/max of the effective price (see searchWithFiltersSql()) across active
     * products, optionally scoped to one category — bounds for the PLP price
     * slider. Single aggregate query, no per-product computation.
     */
    public function getPriceBounds(?string $categoryId = null): array
    {
        $query = $this->query()->active();

        if ($categoryId) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId));
        }

        $row = $query->selectRaw(
            'MIN(LEAST(price, COALESCE(sale_price, price))) as min_price,
             MAX(LEAST(price, COALESCE(sale_price, price))) as max_price'
        )->first();

        return [
            'min' => $row?->min_price !== null ? (float) $row->min_price : 0.0,
            'max' => $row?->max_price !== null ? (float) $row->max_price : 0.0,
        ];
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
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('name', 'asc'),
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

    /**
     * Same as findActiveBySlug(), but also matches per-locale translation
     * slugs (product_translations.slug) — needed anywhere the caller only
     * knows the slug shown on the current locale's PDP, which can differ
     * from the base products.slug column (e.g. EN translation slug).
     *
     * Checks products.slug FIRST (globally unique — safe) and only falls
     * back to product_translations.slug if nothing matched. translations.slug
     * is only unique per (locale, slug), not globally, so an OR'd single
     * query could in theory match a different product's translation slug
     * than intended; two-step lookup keeps the safe, unambiguous match
     * as the priority and only risks the rarer collision on the fallback.
     */
    public function findActiveBySlugAnyLocale(string $slug): ?Product
    {
        $product = $this->findActiveBySlug($slug);

        if ($product) {
            return $product;
        }

        /** @var Product|null */
        return $this->query()
            ->where('is_active', true)
            ->whereHas('translations', fn ($t) => $t->where('slug', $slug))
            ->first();
    }

    // ── Header / mega menu ───────────────────────────────────────────────────────

    /**
     * Newest active products for the header mega menu "Sản phẩm mới" slider.
     */
    public function latestActive(int $limit = 4): EloquentCollection
    {
        return $this->query()
            ->active()
            ->with(['thumbnail', 'translations'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Active products matching $ids, in the exact given order (admin-curated
     * mega menu selection) — not DB insertion order.
     */
    public function findActiveByIdsOrdered(array $ids): EloquentCollection
    {
        $products = $this->query()
            ->active()
            ->with(['thumbnail', 'translations'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return EloquentCollection::make($ids)
            ->map(fn ($id) => $products->get($id))
            ->filter()
            ->values();
    }
}
