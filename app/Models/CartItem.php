<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    // ── Mass assignment ───────────────────────────────────────────────────────

    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'quantity',
    ];

    // ── Computed attributes ───────────────────────────────────────────────────

    /**
     * Line subtotal — variant price takes precedence over the base product
     * price when a variant is selected (sale_price wins over price on
     * whichever of the two is the active source). Requires product/variant
     * relationships to be loaded (eager or lazy).
     */
    protected function subtotal(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->quantity * (float) (
                $this->variant
                    ? ($this->variant->sale_price ?? $this->variant->price)
                    : ($this->product->sale_price ?? $this->product->price)
            ),
        );
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
