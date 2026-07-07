<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductVariant;

/**
 * `products.stock_quantity` is a manual field for simple products, but once
 * a product has variants its own stock should reflect the sum of active
 * variant stock rather than drift from whatever an admin last typed into the
 * top-level field. Falls back to manual entry again once every variant is
 * removed (nothing left to sum from).
 */
class ProductVariantObserver
{
    public function saved(ProductVariant $variant): void
    {
        $this->syncProductStock($variant);
    }

    public function deleted(ProductVariant $variant): void
    {
        $this->syncProductStock($variant);
    }

    private function syncProductStock(ProductVariant $variant): void
    {
        // Query explicitly rather than $variant->product — lazy loading is
        // disabled app-wide, and this model isn't guaranteed to arrive here
        // with that relationship already eager-loaded (e.g. Filament's
        // per-item Repeater save, or a plain ProductVariant::save() call).
        $product = Product::find($variant->product_id);

        if (! $product || ! $product->variants()->exists()) {
            return;
        }

        $product->update([
            'stock_quantity' => $product->activeVariants()->sum('stock_quantity'),
        ]);
    }
}
