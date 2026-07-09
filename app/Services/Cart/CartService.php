<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Repositories\Eloquent\CartRepository;
use App\Repositories\Eloquent\ProductRepository;
use App\Repositories\Eloquent\ProductVariantRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function __construct(
        private readonly CartRepository           $cartRepository,
        private readonly ProductRepository        $productRepository,
        private readonly ProductVariantRepository $productVariantRepository,
    ) {}

    // ── Cart resolution ───────────────────────────────────────────────────────

    public function resolveCart(Request $request): Cart
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if ($user) {
            $cart = $this->cartRepository->firstOrCreateForUser($user);
        } else {
            $sessionId = $request->header('X-Session-ID');
            abort_if(! $sessionId, 400, 'X-Session-ID header is required for guest carts.');
            $cart = $this->cartRepository->firstOrCreateForSession($sessionId);
        }

        if (! $cart->wasRecentlyCreated) {
            $cart->touch();
        }

        return $this->cartRepository->withItems($cart);
    }

    // ── Item management ───────────────────────────────────────────────────────

    /**
     * Wrapped in DB::transaction() — the stock check reads product/variant
     * FOR UPDATE so two concurrent adds for the last unit of the same
     * product can't both read "1 in stock" and both succeed. Cart quantities
     * aren't a hard reservation the way an order's decrement is, but the
     * check should still be honest about what's actually available right now,
     * not a value that went stale the instant a concurrent request wrote to it.
     */
    public function addItem(Cart $cart, string $productId, int $quantity, ?string $variantId = null): Cart
    {
        return DB::transaction(function () use ($cart, $productId, $quantity, $variantId) {
            $product = $this->productRepository->findByIdForUpdate($productId);

            // findByIdForUpdate() only excludes soft-deleted (Eloquent global
            // scope) — a deactivated-but-not-deleted product (is_active=false)
            // still 404s on the PDP, so it must 404 here too. Without this, a
            // direct API call with a known inactive product_id could still add
            // it to the cart.
            abort_if(! $product || ! $product->is_active, 404, 'Product not found.');

            // PDP's own JS disables "Add to bag" until a variant fully resolves
            // (see updateVariantUi() in app.js) — but that's client-side only.
            // A direct API call can skip straight past it, so it has to be
            // enforced here too: a product with active variants can't be added
            // "bare" (product.stock_quantity is an independent, admin-typed
            // number that isn't kept in sync with variant stock — it's not a
            // meaningful fallback once variants exist).
            if (! $variantId && $product->activeVariants()->exists()) {
                throw ValidationException::withMessages([
                    'variant_id' => ['This product requires selecting a variant.'],
                ]);
            }

            // Variant stock gates the add when one is selected — a product's own
            // stock_quantity is meaningless once it has variants (each variant
            // tracks its own stock; see ProductVariant::stock_quantity).
            $stockQuantity = $product->stock_quantity;
            if ($variantId) {
                $variant = $this->productVariantRepository->findForProductOrFail($variantId, $productId, lock: true);
                $stockQuantity = $variant->stock_quantity;
            }

            $existing    = $this->cartRepository->findItem($cart, $productId, $variantId);
            $newQuantity = ($existing ? $existing->quantity : 0) + $quantity;

            if ($stockQuantity < $newQuantity) {
                throw ValidationException::withMessages([
                    'quantity' => ["Only {$stockQuantity} units are available in stock."],
                ]);
            }

            $this->cartRepository->upsertItem($cart, $productId, $variantId, $newQuantity);
            $cart->touch();

            return $this->cartRepository->withItems($cart);
        });
    }

    public function updateItem(CartItem $item, int $quantity): Cart
    {
        return DB::transaction(function () use ($item, $quantity) {
            $stockQuantity = $item->product_variant_id
                ? $this->productVariantRepository->findForProductOrFail($item->product_variant_id, $item->product_id, lock: true)->stock_quantity
                : $this->productRepository->findByIdForUpdate($item->product_id)?->stock_quantity ?? 0;

            if ($stockQuantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ["Only {$stockQuantity} units are available in stock."],
                ]);
            }

            $this->cartRepository->updateItemQuantity($item, $quantity);
            $item->cart->touch();

            return $this->cartRepository->withItems($item->cart);
        });
    }

    public function removeItem(CartItem $item): Cart
    {
        $cart = $item->cart;
        $this->cartRepository->deleteItem($item);
        $cart->touch();

        return $this->cartRepository->withItems($cart);
    }

    public function clearCart(Cart $cart): void
    {
        $this->cartRepository->clearItems($cart);
        $cart->touch();
    }

    // ── Merge ─────────────────────────────────────────────────────────────────

    public function mergeGuestCart(User $user, string $sessionId): Cart
    {
        $guestCart = $this->cartRepository->findBySession($sessionId);
        $userCart  = $this->cartRepository->firstOrCreateForUser($user);

        if ($guestCart) {
            $this->cartRepository->mergeItems($guestCart, $userCart);
            $guestCart->delete();
        }

        $userCart->touch();

        return $this->cartRepository->withItems($userCart);
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    /**
     * IDOR guard — cart_items.id is a plain auto-increment integer, trivially
     * enumerable. Both branches must reject a null/null match: a guest cart's
     * session_id is never null (firstOrCreateForSession() always sets one),
     * and a user cart's session_id is always null (user carts never have
     * one) — so an unauthenticated request that simply omits the
     * X-Session-ID header would otherwise satisfy `null === null` against
     * ANY logged-in user's cart item and pass as "owned".
     */
    public function authorizeItem(Request $request, CartItem $item): void
    {
        $cart = $item->cart;
        $user = $request->user() ?? auth('sanctum')->user();

        if ($user) {
            $owns = $cart->user_id !== null && (string) $cart->user_id === (string) $user->id;
        } else {
            $sessionId = $request->header('X-Session-ID');
            $owns = $sessionId !== null && $cart->session_id !== null && $cart->session_id === $sessionId;
        }

        abort_unless($owns, 403, 'This item does not belong to your cart.');
    }
}
