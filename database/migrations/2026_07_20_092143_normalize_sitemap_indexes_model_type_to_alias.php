<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * sitemap_indexes.model_type was seeded with full class names
     * (App\Models\Product), while sitemap_entries.model_type — and every
     * other polymorphic model_type column in the project — uses the
     * morphMap alias (product). SitemapService::upsertEntry() looked indexes
     * up by full class name, which only worked because it happened to match
     * the seeder. Normalizing both to the alias removes that fragile
     * coincidence.
     */
    private const MAP = [
        'App\\Models\\Product' => 'product',
        'App\\Models\\Category' => 'category',
        'App\\Models\\BlogPost' => 'blog_post',
        'App\\Models\\BlogCategory' => 'blog_category',
    ];

    public function up(): void
    {
        foreach (self::MAP as $fullClass => $alias) {
            DB::table('sitemap_indexes')
                ->where('model_type', $fullClass)
                ->update(['model_type' => $alias]);
        }
    }

    public function down(): void
    {
        foreach (self::MAP as $fullClass => $alias) {
            DB::table('sitemap_indexes')
                ->where('model_type', $alias)
                ->update(['model_type' => $fullClass]);
        }
    }
};
