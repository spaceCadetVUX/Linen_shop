<?php

namespace App\Services\Wishlist;

use App\Models\User;
use App\Repositories\Eloquent\ProductRepository;
use App\Repositories\Eloquent\WishlistRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class WishlistService
{
    public function __construct(
        private readonly WishlistRepository $wishlistRepository,
        private readonly ProductRepository $productRepository,
    ) {}

    // ── Owner resolution — same guest-first pattern as CartService::resolveCart() ──

    /**
     * @return array{0: ?string, 1: ?string} [userId, sessionId]
     */
    public function resolveOwner(Request $request): array
    {
        $user = $request->user() ?? auth('sanctum')->user();

        if ($user) {
            return [$user->id, null];
        }

        $sessionId = $request->header('X-Session-ID');
        abort_if(! $sessionId, 400, 'X-Session-ID header is required for guest wishlists.');

        return [null, $sessionId];
    }

    public function list(Request $request): Collection
    {
        [$userId, $sessionId] = $this->resolveOwner($request);

        return $userId
            ? $this->wishlistRepository->forUser($userId)
            : $this->wishlistRepository->forSession($sessionId);
    }

    /**
     * Add if not already wishlisted, remove if it is — matches the single
     * heart-icon button on PDP and the "remove" button on the wishlist page.
     *
     * @return array{wishlisted: bool}
     */
    public function toggle(Request $request, string $productId): array
    {
        $this->productRepository->findByIdOrFail($productId);

        [$userId, $sessionId] = $this->resolveOwner($request);
        $existing = $this->wishlistRepository->find($userId, $sessionId, $productId);

        if ($existing) {
            $this->wishlistRepository->remove($existing);

            return ['wishlisted' => false];
        }

        $this->wishlistRepository->add($userId, $sessionId, $productId);

        return ['wishlisted' => true];
    }

    /**
     * Fold a guest session's wishlist into a user's on login.
     * No caller today (no storefront login flow yet) — mirrors
     * CartService::mergeGuestCart() so it's ready when one exists.
     */
    public function mergeGuestWishlist(User $user, string $sessionId): void
    {
        $this->wishlistRepository->mergeSessionIntoUser($sessionId, $user->id);
    }
}
