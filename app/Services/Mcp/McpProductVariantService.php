<?php

namespace App\Services\Mcp;

use App\Enums\VariantAvailability;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Product\VariantGeneratorService;

class McpProductVariantService
{
    // ── Public API ─────────────────────────────────────────────────────────────

    public function list(string $productSlug): array
    {
        $product = $this->loadProduct($productSlug);
        $product->variants->loadMissing('optionValues.group');

        return $product->variants
            ->map(fn (ProductVariant $variant) => $this->buildVariantResponse($variant))
            ->values()
            ->all();
    }

    public function upsert(string $productSlug, string $sku, array $data): array
    {
        $product = $this->loadProduct($productSlug);
        $variant = $this->findVariant($product, $sku);

        if (array_key_exists('price_vnd', $data)) {
            $variant->price = $data['price_vnd'];
        }
        if (array_key_exists('sale_price_vnd', $data)) {
            $variant->sale_price = $data['sale_price_vnd'];
        }
        if (array_key_exists('price_usd', $data)) {
            $variant->price_usd = $data['price_usd'];
        }
        if (array_key_exists('sale_price_usd', $data)) {
            $variant->sale_price_usd = $data['sale_price_usd'];
        }
        if (array_key_exists('stock_quantity', $data)) {
            $variant->stock_quantity = (int) $data['stock_quantity'];
        }
        if (array_key_exists('availability_status', $data)) {
            $status = VariantAvailability::tryFrom((string) $data['availability_status']);
            if ($status) {
                $variant->availability_status = $status->value;
            }
        }

        // Never auto-activate via upsert — use the /activate endpoint so the
        // price > 0 gate always runs.
        if (isset($data['is_active']) && $data['is_active'] === false) {
            $variant->is_active = false;
        }

        // Regular save() — not saveQuietly() — so ProductVariantObserver keeps
        // products.stock_quantity in sync with the sum of active variant stock.
        $variant->save();

        return ['data' => $this->buildVariantResponse($variant->fresh())];
    }

    public function activate(string $productSlug, string $sku): array
    {
        $product = $this->loadProduct($productSlug);
        $variant = $this->findVariant($product, $sku);

        if ((float) $variant->price <= 0) {
            abort(422, "Variant '{$sku}' chưa sẵn sàng để activate: price_vnd phải > 0.");
        }

        $variant->update(['is_active' => true]);

        return ['data' => $this->buildVariantResponse($variant->fresh())];
    }

    public function deactivate(string $productSlug, string $sku): array
    {
        $product = $this->loadProduct($productSlug);
        $variant = $this->findVariant($product, $sku);

        $variant->update(['is_active' => false]);

        return ['data' => $this->buildVariantResponse($variant->fresh())];
    }

    public function generateVariants(string $productSlug): array
    {
        $product = $this->loadProduct($productSlug);

        $result = app(VariantGeneratorService::class)->generate($product);

        return [
            'created' => $result['created'],
            'skipped' => $result['skipped'],
            'error' => $result['error'],
            'variants' => $this->list($productSlug),
        ];
    }

    // ── Private: load ────────────────────────────────────────────────────────────

    private function loadProduct(string $productSlug): Product
    {
        $product = Product::where('slug', $productSlug)->first();

        if (! $product) {
            abort(404, "Product '{$productSlug}' not found.");
        }

        return $product;
    }

    private function findVariant(Product $product, string $sku): ProductVariant
    {
        $variant = $product->variants()->where('sku', $sku)->first();

        if (! $variant) {
            abort(404, "Variant '{$sku}' not found on product '{$product->slug}'.");
        }

        return $variant;
    }

    // ── Private: response builder ─────────────────────────────────────────────────

    private function buildVariantResponse(ProductVariant $variant): array
    {
        $variant->loadMissing('optionValues.group');

        $combination = [];
        foreach ($variant->optionValues as $value) {
            $combination[$value->group?->name ?? 'unknown'] = $value->name;
        }

        return [
            'sku' => $variant->sku,
            'combination' => $combination,
            'price_vnd' => $variant->price !== null ? (float) $variant->price : null,
            'sale_price_vnd' => $variant->sale_price !== null ? (float) $variant->sale_price : null,
            'price_usd' => $variant->price_usd !== null ? (float) $variant->price_usd : null,
            'sale_price_usd' => $variant->sale_price_usd !== null ? (float) $variant->sale_price_usd : null,
            'stock_quantity' => $variant->stock_quantity,
            'availability_status' => $variant->availability_status,
            'is_active' => (bool) $variant->is_active,
        ];
    }
}
