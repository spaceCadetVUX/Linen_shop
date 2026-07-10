<?php

namespace App\Services\Mcp;

use App\Models\Category;
use App\Models\Manufacturer;
use App\Models\Product;

class McpImportService
{
    /**
     * Parse raw datasheet text into structured attributes + entity lookups.
     * Never writes to the DB — Claude reads the result, writes the actual
     * translations/SEO/FAQ content itself, then calls save_product.
     */
    public function fromSpecs(
        string $slug,
        ?string $manufacturerSlug,
        ?string $categorySlug,
        string $specsText,
        array $locales,
    ): array {
        return [
            'slug'              => $slug,
            'product_exists'    => Product::where('slug', $slug)->exists(),
            'parsed_attributes' => $this->parseAttributes($specsText),
            'manufacturer'      => $this->lookupEntity(Manufacturer::class, $manufacturerSlug),
            'category'          => $this->lookupEntity(Category::class, $categorySlug),
            'locales'           => $locales,
            'save_url'          => "PUT /api/v1/mcp/products/{$slug}",
            'note'              => 'Chỉ parse attributes thô từ specs_text — không tự sinh translations/SEO/FAQ. '
                . 'Viết content dựa trên parsed_attributes rồi gọi save_product để lưu chính thức.',
        ];
    }

    /**
     * Splits "Label: Value" / "Label = Value" lines into key-value pairs.
     * Lines without a separator are ignored — datasheets are inconsistently
     * formatted, so this stays a best-effort pass, not a strict parser.
     */
    private function parseAttributes(string $specsText): array
    {
        $attributes = [];

        foreach (preg_split('/\r\n|\r|\n/', $specsText) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (! preg_match('/^([^:=]{1,80})[:=]\s*(.+)$/u', $line, $m)) {
                continue;
            }

            $name  = trim($m[1]);
            $value = trim($m[2]);
            if ($name === '' || $value === '') {
                continue;
            }

            $attributes[] = ['name' => $name, 'value' => $value];
        }

        return $attributes;
    }

    private function lookupEntity(string $modelClass, ?string $slug): ?array
    {
        if (blank($slug)) {
            return null;
        }

        $entity = $modelClass::where('slug', $slug)->first();

        return [
            'slug'   => $slug,
            'exists' => (bool) $entity,
            'name'   => $entity?->name,
        ];
    }
}
