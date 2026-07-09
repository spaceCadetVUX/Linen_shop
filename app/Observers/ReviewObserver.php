<?php

namespace App\Observers;

use App\Jobs\Seo\SyncJsonldSchema;
use App\Models\Review;
use App\Services\Review\ReviewService;

class ReviewObserver
{
    /**
     * Re-sync Product JSON-LD whenever a review is saved.
     * This updates the AggregateRating schema on the product page.
     * Only dispatch if the review is approved — unapproved reviews
     * should not affect the product's public schema.
     *
     * Dispatched once per supported locale (same pattern as
     * ProductObserver) — a review has no locale of its own (it's shared
     * across vi/en, same as the product), but the JSON-LD is persisted
     * per (model, locale) in jsonld_schemas, so every locale's snapshot
     * needs its own sync job or its AggregateRating goes stale.
     */
    public function saved(Review $review): void
    {
        if ($review->is_approved) {
            $this->syncAllLocales($review);
        }

        $this->bustSummaryCache($review);
    }

    /**
     * Re-sync when a review is deleted so AggregateRating is recalculated.
     */
    public function deleted(Review $review): void
    {
        $this->syncAllLocales($review);
        $this->bustSummaryCache($review);
    }

    private function syncAllLocales(Review $review): void
    {
        foreach (config('app.supported_locales', ['vi', 'en']) as $locale) {
            dispatch(new SyncJsonldSchema($review->product, $locale))->onQueue('seo');
        }
    }

    private function bustSummaryCache(Review $review): void
    {
        app(ReviewService::class)->bustSummaryCache($review->product);
    }
}
