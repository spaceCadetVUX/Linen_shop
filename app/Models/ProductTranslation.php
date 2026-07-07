<?php

namespace App\Models;

use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductTranslation extends Model
{
    // bigint PK, no uuid, no soft deletes (CASCADE from product)
    protected $fillable = [
        'product_id',
        'locale',
        'name',
        'slug',
        'short_description',
        'description',
        'info_sections',
        'price',
        'sale_price',
        'currency',
        'is_mcp_protected',
    ];

    protected function casts(): array
    {
        return [
            'price'            => 'decimal:2',
            'sale_price'       => 'decimal:2',
            'is_mcp_protected' => 'boolean',
            'info_sections'    => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * `description` is a Filament RichEditor field, stored as Tiptap JSON
     * (`{"type":"doc",...}`). Older rows written before the RichEditor
     * migration hold plain text instead — decode-and-check rather than
     * assuming JSON, so those don't render as a raw JSON blob on the storefront.
     */
    public function getDescriptionHtmlAttribute(): ?string
    {
        return static::renderRichText($this->description);
    }

    /**
     * Dynamic accordion sections (title + RichEditor content pairs), managed
     * in Filament's Content tab — replaces the old hardcoded "Material &
     * Composition" / "Care instructions" / "Shipping & Returns" markup.
     *
     * @return list<array{title: string, html: string}>
     */
    public function getInfoSectionsHtmlAttribute(): array
    {
        return collect($this->info_sections ?? [])
            ->map(fn (array $section) => [
                'title' => (string) ($section['title'] ?? ''),
                'html' => static::renderRichText($section['content'] ?? null),
            ])
            ->filter(fn (array $section) => filled($section['title']) && filled($section['html']))
            ->values()
            ->all();
    }

    /**
     * `$raw` is a JSON string for top-level columns (e.g. `description`, cast
     * as plain string) but already a decoded array when it comes from inside
     * `info_sections` (that whole column is cast to `array`, so its nested
     * RichEditor content rides along already-decoded instead of re-encoded
     * as its own JSON string). Handle both rather than assuming one shape.
     */
    private static function renderRichText(string | array | null $raw): ?string
    {
        if (blank($raw)) {
            return null;
        }

        $decoded = is_array($raw) ? $raw : json_decode($raw, true);

        if (is_array($decoded) && ($decoded['type'] ?? null) === 'doc') {
            return RichContentRenderer::make($decoded)->toHtml();
        }

        return is_string($raw) ? nl2br(e($raw)) : null;
    }
}
