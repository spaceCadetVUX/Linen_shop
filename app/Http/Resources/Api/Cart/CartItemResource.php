<?php

namespace App\Http\Resources\Api\Cart;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Base Product columns (name/slug/price) are the vi copy by convention —
     * reading them directly means an EN cart shows Vietnamese names and a vi
     * slug baked into the product link (404s under /en/products/, since
     * that route only matches the en ProductTranslation's own slug). This
     * API has no {locale} route segment, so the frontend passes ?locale= on
     * GET /api/v1/cart; same fix as WishlistItemResource.
     */
    public function toArray(Request $request): array
    {
        $locale = (string) $request->query('locale', config('app.fallback_locale', 'vi'));

        return [
            'id'       => $this->id,
            'product'  => $this->whenLoaded('product', function () use ($locale) {
                /** @var \App\Models\Product $product */
                $product = $this->product;
                $t = $product->translation($locale);

                return [
                    'id'             => $product->id,
                    'name'           => $t?->name ?? $product->name,
                    'slug'           => $t?->slug ?? $product->slug,
                    'price'          => (string) ($t?->price ?? $product->price),
                    'sale_price'     => ($t?->sale_price ?? $product->sale_price)
                        ? (string) ($t?->sale_price ?? $product->sale_price)
                        : null,
                    'stock_quantity' => $product->stock_quantity,
                    'thumbnail'      => $product->relationLoaded('thumbnail')
                        ? $product->thumbnail?->url
                        : null,
                ];
            }),
            'variant_id'    => $this->product_variant_id,
            'variant_label' => $this->whenLoaded('variant', fn () => $this->variant?->combination_label),
            'quantity'      => $this->quantity,
            'subtotal'      => number_format($this->subtotal, 2, '.', ''),
        ];
    }
}
