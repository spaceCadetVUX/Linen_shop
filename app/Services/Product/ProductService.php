<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\Setting;
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
     * Products for the header mega menu "Sản phẩm mới" slider (column 1).
     * Admin-curated via Mega Menu Setting (extra.mega_menu.new_products_ids,
     * order preserved); falls back to the 4 newest active products when the
     * admin hasn't picked anything. Caches the shaped array (not the Eloquent
     * collection) — caching Model instances round-trips through
     * serialize/unserialize and can come back as __PHP_Incomplete_Class.
     */
    public function getLatestForMegaMenu(string $locale): array
    {
        return Cache::remember(self::LATEST_CACHE_KEY . ":{$locale}", self::LATEST_CACHE_TTL,
            function () use ($locale) {
                $curatedIds = (array) (Setting::profile()->extra['mega_menu']['new_products_ids'] ?? []);

                $products = filled($curatedIds)
                    ? $this->productRepository->findActiveByIdsOrdered($curatedIds)
                    : $this->productRepository->latestActive(4);

                return $products
                    ->map(fn (Product $product) => [
                        'name' => $product->translation($locale)?->name ?? $product->name,
                        'image' => $product->thumbnail?->url,
                        'url' => LocaleUrl::for('product', $product->translation($locale)?->slug ?? $product->slug, $locale),
                    ])
                    ->filter(fn (array $p) => filled($p['image']))
                    ->values()
                    ->all();
            }
        );
    }

    public function bustLatestMegaCache(): void
    {
        foreach (config('app.supported_locales', ['vi', 'en']) as $locale) {
            Cache::forget(self::LATEST_CACHE_KEY . ":{$locale}");
        }
    }
}
