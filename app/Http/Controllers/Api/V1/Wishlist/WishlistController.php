<?php

namespace App\Http\Controllers\Api\V1\Wishlist;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Wishlist\WishlistItemResource;
use App\Http\Resources\Traits\ApiResponse;
use App\Services\Wishlist\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly WishlistService $wishlistService,
    ) {}

    /**
     * GET /api/v1/wishlist
     * Guest (X-Session-ID header) or logged-in user — same resolution as Cart.
     */
    public function index(Request $request): JsonResponse
    {
        $items = $this->wishlistService->list($request);

        return $this->success(data: WishlistItemResource::collection($items));
    }

    /**
     * POST /api/v1/wishlist/toggle
     * Add if not wishlisted yet, remove if it already is — one button, one endpoint.
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate(['product_id' => ['required', 'uuid']]);

        $result = $this->wishlistService->toggle($request, $request->input('product_id'));

        return $this->success(data: $result);
    }
}
