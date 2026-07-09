<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReviewRepository extends BaseRepository
{
    protected function model(): string
    {
        return Review::class;
    }

    /**
     * Approved reviews for a product, sorted per App\Enums\ReviewSort.
     */
    public function approvedForProduct(Product $product, string $sort, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->query()
            ->where('product_id', $product->id)
            ->approved()
            ->with('images');

        match ($sort) {
            'rating_high' => $query->orderByDesc('rating'),
            'rating_low' => $query->orderBy('rating'),
            'with_photos' => $query->whereHas('images')->latest(),
            default => $query->latest(),
        };

        return $query->paginate($perPage);
    }

    /**
     * Average rating, total count, and 5→1 star breakdown for a product.
     * Only counts approved reviews — matches the AggregateRating JSON-LD rule.
     */
    public function ratingSummaryFor(Product $product): array
    {
        $base = fn () => $this->query()->where('product_id', $product->id)->approved();

        return [
            'average' => round((float) $base()->avg('rating'), 1),
            'count' => $base()->count(),
            'breakdown' => collect(range(5, 1))
                ->mapWithKeys(fn (int $star) => [$star => $base()->where('rating', $star)->count()])
                ->all(),
        ];
    }
}
