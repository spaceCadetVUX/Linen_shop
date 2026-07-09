<?php

namespace App\Repositories\Eloquent;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;

class CartRepository extends BaseRepository
{
    protected function model(): string
    {
        return Cart::class;
    }

    // ── Resolve ───────────────────────────────────────────────────────────────

    public function firstOrCreateForUser(User $user): Cart
    {
        return Cart::firstOrCreate(['user_id' => $user->id]);
    }

    public function firstOrCreateForSession(string $sessionId): Cart
    {
        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }

    public function findBySession(string $sessionId): ?Cart
    {
        return Cart::where('session_id', $sessionId)->first();
    }

    public function withItems(Cart $cart): Cart
    {
        return $cart->load([
            'items.product.thumbnail',
            'items.product.translations',
            'items.variant.optionValues.group',
        ]);
    }

    // ── Items ─────────────────────────────────────────────────────────────────

    /**
     * $variantId matters for the lookup — two lines for the same product
     * with different (or no) variant are distinct cart lines, never merged.
     */
    public function findItem(Cart $cart, string $productId, ?string $variantId = null): ?CartItem
    {
        return $cart->items()
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->first();
    }

    public function upsertItem(Cart $cart, string $productId, ?string $variantId, int $quantity): void
    {
        $cart->items()->updateOrCreate(
            ['product_id' => $productId, 'product_variant_id' => $variantId],
            ['quantity'   => $quantity],
        );
    }

    public function updateItemQuantity(CartItem $item, int $quantity): void
    {
        $item->update(['quantity' => $quantity]);
    }

    public function deleteItem(CartItem $item): void
    {
        $item->delete();
    }

    public function clearItems(Cart $cart): void
    {
        $cart->items()->delete();
    }

    // ── Merge ─────────────────────────────────────────────────────────────────

    public function mergeItems(Cart $source, Cart $target): void
    {
        foreach ($source->items as $guestItem) {
            $existing = $target->items()
                ->where('product_id', $guestItem->product_id)
                ->where('product_variant_id', $guestItem->product_variant_id)
                ->first();

            if ($existing) {
                $existing->increment('quantity', $guestItem->quantity);
            } else {
                $target->items()->create([
                    'product_id'         => $guestItem->product_id,
                    'product_variant_id' => $guestItem->product_variant_id,
                    'quantity'           => $guestItem->quantity,
                ]);
            }
        }
    }
}
