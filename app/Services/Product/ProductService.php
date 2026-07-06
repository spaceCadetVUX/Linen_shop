<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Repositories\Eloquent\ProductRepository;
use App\Support\LocaleUrl;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    private const LATEST_CACHE_KEY = 'products:latest_mega';

    private const LATEST_CACHE_TTL = 600;

    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    public function list(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->paginate($perPage, $filters);
    }

    public function getBySlug(string $slug): Product
    {
        $product = $this->productRepository->findActiveBySlug($slug);

        abort_if(! $product, 404, 'Product not found.');

        return $product;
    }

    /**
     * Newest active products, shaped for the header mega menu "Sản phẩm mới"
     * auto-slide (column 1). Caches the shaped array (not the Eloquent
     * collection) — caching Model instances round-trips through
     * serialize/unserialize and can come back as __PHP_Incomplete_Class.
     */
    public function getLatestForMegaMenu(string $locale, int $limit = 4): array
    {
        return Cache::remember(self::LATEST_CACHE_KEY . ":{$locale}:{$limit}", self::LATEST_CACHE_TTL,
            fn () => $this->productRepository->latestActive($limit)
                ->map(fn (Product $product) => [
                    'name' => $product->translation($locale)?->name ?? $product->name,
                    'image' => $product->thumbnail?->url,
                    'url' => LocaleUrl::for('product', $product->translation($locale)?->slug ?? $product->slug, $locale),
                ])
                ->filter(fn (array $p) => filled($p['image']))
                ->values()
                ->all()
        );
    }

    public function bustLatestMegaCache(int $limit = 4): void
    {
        foreach (config('app.supported_locales', ['vi', 'en']) as $locale) {
            Cache::forget(self::LATEST_CACHE_KEY . ":{$locale}:{$limit}");
        }
    }
}
