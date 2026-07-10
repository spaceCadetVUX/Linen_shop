<?php

namespace App\Services\Category;

use App\Models\Category;
use App\Models\Product;
use App\Repositories\Eloquent\CategoryRepository;
use App\Support\LocaleUrl;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    public const TREE_CACHE_KEY = 'categories:tree';

    private const TREE_CACHE_TTL = 600;

    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function getTree(): Collection
    {
        return Cache::remember(self::TREE_CACHE_KEY, self::TREE_CACHE_TTL,
            fn () => $this->categoryRepository->getActiveTree()
        );
    }

    public function getBySlug(string $slug, ?string $locale = null): Category
    {
        $category = $this->categoryRepository->findActiveBySlug($slug, $locale);

        abort_if(! $category, 404, 'Category not found.');

        return $category;
    }

    public function getProductsPaginated(Category $category, array $filters = []): LengthAwarePaginator
    {
        return $this->categoryRepository->getProductsPaginated($category, $filters);
    }

    public function bustTreeCache(): void
    {
        Cache::forget(self::TREE_CACHE_KEY);
    }

    /**
     * Root categories shaped as simple {name, url} pairs — for the footer
     * "Bộ sưu tập" column. Reuses the cached tree (getTree()), skipping the
     * products eager-load getMegaMenuData() does since the footer only needs
     * the label + link.
     */
    public function getFooterCategories(string $locale): array
    {
        return $this->getTree()
            ->map(fn (Category $root) => [
                'name' => $root->translation($locale)?->name ?? $root->name,
                'url' => LocaleUrl::for('category', $root->translation($locale)?->slug ?? $root->slug, $locale),
            ])
            ->values()
            ->all();
    }

    /**
     * Root category + active children → up to 4 newest active products each,
     * shaped for the header mega menu (column 2 groups/links + column 3 hover
     * preview — hovering either a parent or a child swaps column 3).
     *
     * Reuses the cached tree for the group/link structure; products are loaded
     * fresh per request (not part of the tree cache) since a boutique catalog
     * this size makes that cheap and keeps stock in sync without a cache bust.
     */
    public function getMegaMenuData(string $locale): array
    {
        $tree = $this->getTree();

        $productsConstraint = fn ($q) => $q->where('products.is_active', true)
            ->orderByDesc('products.created_at');

        $tree->loadMissing([
            'products' => $productsConstraint,
            'products.thumbnail',
            'products.translations',
            'children.products' => $productsConstraint,
            'children.products.thumbnail',
            'children.products.translations',
        ]);

        $shapeProducts = fn (Category $category) => $category->products->take(4)
            ->map(fn (Product $product) => [
                'name' => $product->translation($locale)?->name ?? $product->name,
                'image' => $product->thumbnail?->url,
                'url' => LocaleUrl::for('product', $product->translation($locale)?->slug ?? $product->slug, $locale),
            ])
            ->filter(fn (array $p) => filled($p['image']))
            ->values()
            ->all();

        return $tree->map(fn (Category $root) => [
            'name' => $root->translation($locale)?->name ?? $root->name,
            'url' => LocaleUrl::for('category', $root->translation($locale)?->slug ?? $root->slug, $locale),
            'mega_cat' => $root->slug,
            'products' => $shapeProducts($root),
            'children' => $root->children->map(fn (Category $child) => [
                'label' => $child->translation($locale)?->name ?? $child->name,
                'mega_cat' => $child->slug,
                'url' => LocaleUrl::for('category', $child->translation($locale)?->slug ?? $child->slug, $locale),
                'products' => $shapeProducts($child),
            ])->values()->all(),
        ])->values()->all();
    }
}
