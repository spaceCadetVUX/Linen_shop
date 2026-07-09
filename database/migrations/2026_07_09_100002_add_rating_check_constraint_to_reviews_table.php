<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Laravel's schema builder has no first-class CHECK constraint method —
        // raw DDL is the only way. App-level validation (StoreReviewRequest,
        // Filament rating Select) already restricts 1–5, but nothing stopped
        // a stray tinker/seeder insert from writing 0 or 200 into this
        // tinyInteger unsigned column, which would silently break the
        // hardcoded bestRating:5 / worstRating:1 in the AggregateRating JSON-LD.
        DB::statement('ALTER TABLE reviews ADD CONSTRAINT reviews_rating_between_1_and_5 CHECK (rating BETWEEN 1 AND 5)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE reviews DROP CONSTRAINT reviews_rating_between_1_and_5');
    }
};
