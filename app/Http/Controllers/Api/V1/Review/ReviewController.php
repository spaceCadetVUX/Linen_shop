<?php

namespace App\Http\Controllers\Api\V1\Review;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Http\Resources\Api\Review\ReviewResource;
use App\Http\Resources\Traits\ApiResponse;
use App\Services\Product\ProductService;
use App\Services\Review\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ReviewService $reviewService,
        private readonly ProductService $productService,
    ) {}

    public function index(string $slug, Request $request): JsonResponse
    {
        $product = $this->productService->getBySlug($slug);

        $reviews = $this->reviewService->listForProduct(
            product: $product,
            sort: (string) $request->input('sort', 'newest'),
            perPage: (int) $request->input('per_page', 10),
        );

        return $this->success(
            data: ReviewResource::collection($reviews),
            meta: $this->paginationMeta($reviews) + ['summary' => $this->reviewService->summaryFor($product)],
        );
    }

    public function store(string $slug, StoreReviewRequest $request): JsonResponse
    {
        $product = $this->productService->getBySlug($slug);

        $review = $this->reviewService->submit(
            product: $product,
            userId: $request->user()->id,
            authorName: $request->user()->name,
            data: $request->validated(),
            images: $request->file('images', []),
        );

        return $this->success(
            data: ['id' => $review->id, 'is_approved' => false],
            message: 'Review submitted and pending approval',
            status: 201,
        );
    }
}
