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

    /**
     * Excludes lines whose product has since been soft-deleted or deactivated
     * by admin. Without this, a soft-deleted product makes `items.product`
     * resolve to null while still "loaded" (whenLoaded() sees it as loaded) —
     * CartItemResource then calls ->translation() on that null and 500s the
     * whole GET /api/v1/cart response for that cart. Same fix as
     * WishlistRepository::forUser()/forSession().
     */
    public function withItems(Cart $cart): Cart
    {
        return $cart->load([
            'items' => function ($query) {
                $query->whereHas('product', fn ($p) => $p->where('is_active', true))
                    ->with([
                        'product.thumbnail',
                        'product.translations',
                        'variant.optionValues.group',
                    ]);
            },
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
        // Same is_active guard as withItems() — don't carry a since-deactivated
        // product's line over into the user's real cart on login-merge.
        $activeGuestItems = $source->items()
            ->whereHas('product', fn ($p) => $p->where('is_active', true))
            ->get();

        foreach ($activeGuestItems as $guestItem) {
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
