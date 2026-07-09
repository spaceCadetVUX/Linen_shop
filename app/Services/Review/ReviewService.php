<?php

namespace App\Services\Review;

use App\Enums\ReviewSort;
use App\Models\Product;
use App\Models\Review;
use App\Repositories\Eloquent\ReviewRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class ReviewService
{
    public function __construct(
        private readonly ReviewRepository $reviewRepository,
    ) {}

    public function listForProduct(Product $product, string $sort, int $perPage = 10): LengthAwarePaginator
    {
        $sort = ReviewSort::tryFrom($sort)?->value ?? ReviewSort::Newest->value;

        return $this->reviewRepository->approvedForProduct($product, $sort, $perPage);
    }

    public function summaryFor(Product $product): array
    {
        return $this->reviewRepository->ratingSummaryFor($product);
    }

    /**
     * Create a pending review for a product. Guest reviews are allowed — the
     * storefront has no login UI yet, so $userId is null for every submission
     * today; author/email always come from the form itself. Always
     * is_approved = false — only an admin approval in Filament makes it
     * public / counted in the AggregateRating JSON-LD (see ReviewObserver).
     *
     * @param  UploadedFile[]  $images
     */
    public function submit(Product $product, ?string $userId, array $data, array $images = []): Review
    {
        /** @var Review $review */
        $review = $this->reviewRepository->create([
            'product_id' => $product->id,
            'user_id' => $userId,
            'author' => $data['author'],
            'email' => $data['email'],
            'title' => $data['title'] ?? null,
            'rating' => $data['rating'],
            'content' => $data['content'],
            'is_approved' => false,
        ]);

        foreach ($images as $index => $image) {
            $path = $image->store('reviews', 'public');
            $review->images()->create([
                'path' => $path,
                'sort_order' => $index,
            ]);
        }

        return $review;
    }
}
