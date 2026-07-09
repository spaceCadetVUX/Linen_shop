<?php

namespace App\Repositories\Eloquent;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductVariantRepository extends BaseRepository
{
    protected function model(): string
    {
        return ProductVariant::class;
    }

    /**
     * A variant_id that doesn't actually belong to the product being added
     * (e.g. a stale/tampered request) must fail loudly, not silently attach
     * the wrong variant to a cart line. is_active=true is required too — a
     * deactivated variant of an otherwise-active product must not be
     * addable via a direct API call just because the product itself is fine.
     */
    public function findForProductOrFail(string $variantId, string $productId, bool $lock = false): ProductVariant
    {
        $query = ProductVariant::where('id', $variantId)
            ->where('product_id', $productId)
            ->where('is_active', true);

        if ($lock) {
            // Only holds through the caller's DB::transaction() — see
            // BaseRepository::findByIdForUpdate().
            $query->lockForUpdate();
        }

        $variant = $query->first();

        if (! $variant) {
            throw (new ModelNotFoundException())->setModel(ProductVariant::class, [$variantId]);
        }

        return $variant;
    }
}
