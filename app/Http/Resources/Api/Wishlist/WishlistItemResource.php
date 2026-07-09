<?php

namespace App\Http\Resources\Api\Wishlist;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistItemResource extends JsonResource
{
    /**
     * The base Product columns (name/slug/price) are effectively the vi copy
     * by convention in this codebase — reading them directly means an EN
     * viewer sees Vietnamese names AND a vi slug baked into product links
     * (which 404 under /en/products/, since that route only matches the en
     * ProductTranslation's own slug). Frontend passes ?locale= on
     * GET /api/v1/wishlist (this API has no {locale} route segment to infer
     * it from) so this resource can resolve the same way JsonldService and
     * every other locale-aware view does: translation() first, base column
     * fallback for whatever a locale hasn't been filled in for.
     */
    public function toArray(Request $request): array
    {
        $locale = (string) $request->query('locale', config('app.fallback_locale', 'vi'));

        /** @var \App\Models\Product $product */
        $product = $this->product;
        $t = $product->translation($locale);

        return [
            'product_id' => $product->id,
            'name' => $t?->name ?? $product->name,
            'slug' => $t?->slug ?? $product->slug,
            'price' => (string) ($t?->price ?? $product->price),
            'sale_price' => ($t?->sale_price ?? $product->sale_price)
                ? (string) ($t?->sale_price ?? $product->sale_price)
                : null,
            'thumbnail' => $product->relationLoaded('thumbnail')
                ? $product->thumbnail?->url
                : null,
            'added_at' => $this->created_at->toIso8601String(),
        ];
    }
}
