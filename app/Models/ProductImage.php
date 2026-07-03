<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    // ── Model events ──────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::deleted(function (ProductImage $image): void {
            if ($image->path && Storage::disk('public')->exists($image->path)) {
                Storage::disk('public')->delete($image->path);
            }
        });

        // Defense-in-depth: cap "card priority" images at 2 per product,
        // regardless of entry point (Filament UI cap is JS-side via ->live()).
        static::saving(function (ProductImage $image): void {
            if (! $image->is_card_priority) {
                return;
            }

            $alreadyPriority = static::where('product_id', $image->product_id)
                ->where('is_card_priority', true)
                ->when($image->exists, fn ($q) => $q->where('id', '!=', $image->id))
                ->count();

            if ($alreadyPriority >= 2) {
                $image->is_card_priority = false;
            }
        });
    }

    protected $fillable = [
        'product_id',
        'path',
        'alt_text',
        'sort_order',
        'price',
        'is_card_priority',
    ];

    // ── Casts ─────────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'price' => 'decimal:2',
            'is_card_priority' => 'boolean',
        ];
    }

    // ── Computed attributes ───────────────────────────────────────────────────

    /** Full public URL for the image (read-only). */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => Storage::disk('public')->url($this->path),
        );
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Categories this image is tagged with (subset of product's categories). */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product_image');
    }
}
