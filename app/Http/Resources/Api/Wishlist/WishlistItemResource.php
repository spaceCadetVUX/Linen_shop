<?php

namespace App\Http\Resources\Api\Wishlist;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Product $product */
        $product = $this->product;

        return [
            'product_id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => (string) $product->price,
            'sale_price' => $product->sale_price ? (string) $product->sale_price : null,
            'thumbnail' => $product->relationLoaded('thumbnail')
                ? $product->thumbnail?->url
                : null,
            'added_at' => $this->created_at->toIso8601String(),
        ];
    }
}
