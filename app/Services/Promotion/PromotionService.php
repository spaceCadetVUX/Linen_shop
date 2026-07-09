<?php

namespace App\Services\Promotion;

use App\Enums\PromotionBannerPosition;
use App\Repositories\Eloquent\ProductRepository;
use App\Repositories\Eloquent\PromotionRepository;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PromotionService
{
    public function __construct(
        private PromotionRepository $promotionRepository,
        private ProductRepository $productRepository,
    ) {}

    /**
     * Currently-running promotions (is_active + within starts_at/ends_at
     * window), each resolved to its admin-picked products in order. Used to
     * render the homepage promotion carousel — one entry per slide.
     *
     * @return array<int, array{title: string, banner_image_url: ?string, banner_position: PromotionBannerPosition, cta_label: ?string, cta_url: ?string, ends_at: ?CarbonInterface, products: Collection}>
     */
    public function getActiveForHomepage(string $locale): array
    {
        $isEn = $locale === 'en';

        return $this->promotionRepository->activeOrdered()
            ->map(function ($promotion) use ($locale, $isEn) {
                $products = $this->productRepository->findActiveTranslationsByIdsOrdered(
                    (array) ($promotion->product_ids ?? []),
                    $locale
                );

                return [
                    'title' => ($isEn ? $promotion->title_en : null) ?? $promotion->title,
                    'banner_image_url' => $this->resolveImageUrl($promotion->banner_image),
                    'banner_position' => $promotion->banner_position,
                    'cta_label' => ($isEn ? $promotion->cta_label_en : null) ?? $promotion->cta_label,
                    'cta_url' => $promotion->cta_url,
                    'ends_at' => $promotion->ends_at,
                    'products' => $products,
                ];
            })
            ->filter(fn (array $promo) => $promo['products']->isNotEmpty())
            ->values()
            ->all();
    }

    private function resolveImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : asset('storage/'.ltrim($path, '/'));
    }
}
