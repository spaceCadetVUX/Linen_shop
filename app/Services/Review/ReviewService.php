<?php

namespace App\Services\Review;

use App\Enums\ReviewSort;
use App\Models\Product;
use App\Models\Review;
use App\Repositories\Eloquent\ReviewRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReviewService
{
    private const SUMMARY_CACHE_KEY = 'reviews:summary';

    private const SUMMARY_CACHE_TTL = 600;

    public function __construct(
        private readonly ReviewRepository $reviewRepository,
    ) {}

    public function listForProduct(Product $product, string $sort, int $perPage = 10): LengthAwarePaginator
    {
        $sort = ReviewSort::tryFrom($sort)?->value ?? ReviewSort::Newest->value;

        return $this->reviewRepository->approvedForProduct($product, $sort, $perPage);
    }

    /**
     * Average rating, count, and 5→1 breakdown for a product — cached,
     * since it fires 7 aggregate queries (avg + count + 5× per-star count)
     * and is read on every PDP load. Busted by ReviewObserver on
     * save/delete (see bustSummaryCache()).
     */
    public function summaryFor(Product $product): array
    {
        return Cache::remember(
            self::SUMMARY_CACHE_KEY . ":{$product->id}",
            self::SUMMARY_CACHE_TTL,
            fn () => $this->reviewRepository->ratingSummaryFor($product),
        );
    }

    public function bustSummaryCache(Product $product): void
    {
        Cache::forget(self::SUMMARY_CACHE_KEY . ":{$product->id}");
    }

    /**
     * Create a pending review for a product. Guest reviews are allowed — the
     * storefront has no login UI yet, so $userId is null for every submission
     * today; author/email always come from the form itself. Always
     * is_approved = false — only an admin approval in Filament makes it
     * public / counted in the AggregateRating JSON-LD (see ReviewObserver).
     *
     * Wrapped in a transaction — a review with a partially-stored image set
     * (e.g. disk failure on image 3 of 5) is worse than no review at all.
     *
     * @param  UploadedFile[]  $images
     */
    public function submit(Product $product, ?string $userId, array $data, array $images = []): Review
    {
        return DB::transaction(function () use ($product, $userId, $data, $images) {
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
        });
    }
}
