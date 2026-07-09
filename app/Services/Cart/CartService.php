<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use App\Repositories\Eloquent\CartRepository;
use App\Repositories\Eloquent\ProductRepository;
use App\Repositories\Eloquent\ProductVariantRepository;
use Illuminate\Http\Request;
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

    public function addItem(Cart $cart, string $productId, int $quantity, ?string $variantId = null): Cart
    {
        $product = $this->productRepository->findByIdOrFail($productId);

        // findByIdOrFail() only excludes soft-deleted (Eloquent global scope) —
        // a deactivated-but-not-deleted product (is_active=false) still 404s
        // on the PDP, so it must 404 here too. Without this, a direct API call
        // with a known inactive product_id could still add it to the cart.
        abort_if(! $product->is_active, 404, 'Product not found.');

        // Variant stock gates the add when one is selected — a product's own
        // stock_quantity is meaningless once it has variants (each variant
        // tracks its own stock; see ProductVariant::stock_quantity).
        $stockQuantity = $product->stock_quantity;
        if ($variantId) {
            $variant = $this->productVariantRepository->findForProductOrFail($variantId, $productId);
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
    }

    public function updateItem(CartItem $item, int $quantity): Cart
    {
        $stockQuantity = $item->variant ? $item->variant->stock_quantity : $item->product->stock_quantity;

        if ($stockQuantity < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => ["Only {$stockQuantity} units are available in stock."],
            ]);
        }

        $this->cartRepository->updateItemQuantity($item, $quantity);
        $item->cart->touch();

        return $this->cartRepository->withItems($item->cart);
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

    public function authorizeItem(Request $request, CartItem $item): void
    {
        $cart = $item->cart;
        $user = $request->user() ?? auth('sanctum')->user();

        $owns = $user
            ? (string) $cart->user_id === (string) $user->id
            : $cart->session_id === $request->header('X-Session-ID');

        abort_unless($owns, 403, 'This item does not belong to your cart.');
    }
}
