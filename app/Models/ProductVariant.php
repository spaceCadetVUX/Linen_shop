<?php

namespace App\Models;

use App\Enums\VariantAvailability;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'image_id',
        'sku',
        'price',
        'sale_price',
        'price_usd',
        'sale_price_usd',
        'stock_quantity',
        'availability_status',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'price_usd' => 'decimal:2',
            'sale_price_usd' => 'decimal:2',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock_quantity', '>', 0);
    }

    // ── Computed attributes ───────────────────────────────────────────────────

    /** Effective selling price — sale_price if set, otherwise base price. */
    public function getEffectivePriceAttribute(): string
    {
        return $this->sale_price ?? $this->price;
    }

    /** Effective USD selling price — sale_price_usd if set, otherwise price_usd. */
    public function getEffectivePriceUsdAttribute(): ?string
    {
        return $this->sale_price_usd ?? $this->price_usd;
    }

    /**
     * Schema.org availability URL for this variant's Offer.
     * "Auto" derives InStock/OutOfStock from stock_quantity; OutOfStock/PreOrder
     * are explicit admin overrides that ignore stock_quantity entirely.
     */
    public function resolvedAvailabilityUrl(): string
    {
        return match ($this->availability_status) {
            VariantAvailability::OutOfStock->value => 'https://schema.org/OutOfStock',
            VariantAvailability::PreOrder->value => 'https://schema.org/PreOrder',
            default => $this->stock_quantity > 0
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
        };
    }

    /** Same resolution as resolvedAvailabilityUrl(), as a short key for the storefront (app.js). */
    public function resolvedStatusKey(): string
    {
        return match ($this->availability_status) {
            VariantAvailability::OutOfStock->value => 'out_of_stock',
            VariantAvailability::PreOrder->value => 'pre_order',
            default => $this->stock_quantity > 0 ? 'in_stock' : 'out_of_stock',
        };
    }

    /**
     * Human-readable combination label: "Red / M / 256GB"
     * Requires optionValues.group to be loaded.
     */
    public function getCombinationLabelAttribute(): string
    {
        if (! $this->relationLoaded('optionValues')) {
            return '';
        }

        return $this->optionValues
            ->sortBy(fn ($v) => $v->group?->sort_order ?? 0)
            ->pluck('name')
            ->join(' / ');
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'image_id');
    }

    /**
     * The selected FilterValues that make up this variant's combination.
     * e.g. [Color=Red, Size=M] for a "Red / M" variant. Same FilterValue
     * rows used for storefront facet filtering — see Product::filterValues().
     */
    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            FilterValue::class,
            'product_variant_options',
            'variant_id',
            'filter_value_id',
        )->withTimestamps();
    }
}
