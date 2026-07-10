<?php

namespace App\Repositories\Eloquent;

use App\Models\BlogCategory;
use App\Models\BlogCategoryTranslation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class BlogCategoryRepository extends BaseRepository
{
    protected function model(): string
    {
        return BlogCategory::class;
    }

    /**
     * Single active blog category by translation slug.
     * Looks up by slug in any locale translation.
     */
    public function findActiveBySlug(string $slug): ?BlogCategory
    {
        /** @var BlogCategory|null */
        return $this->query()
            ->active()
            ->with(['translations', 'seoMetas', 'activeSchemas'])
            ->whereHas('translations', fn ($q) => $q->where('slug', $slug))
            ->first();
    }

    /**
     * Translation row (+ parent BlogCategory) for a slug in a given locale.
     * Used to resolve the public /blog/{slug} category page.
     */
    public function findTranslationBySlug(string $locale, string $slug): ?BlogCategoryTranslation
    {
        return BlogCategoryTranslation::where('locale', $locale)
            ->where('slug', $slug)
            ->with('blogCategory')
            ->first();
    }

    /**
     * All active categories (flat — no hierarchy), ordered by admin-configured
     * sort_order (was alphabetical by name — ignored the Filament sort_order field).
     */
    public function getActiveTree(): Collection
    {
        return $this->query()
            ->active()
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Active categories (any level), decorated with per-locale name/slug —
     * used for the blog post sidebar category list.
     */
    public function getActiveDecorated(string $locale): BaseCollection
    {
        return $this->query()
            ->active()
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->orderBy('sort_order')
            ->get()
            ->map(function (BlogCategory $cat) {
                $tr = $cat->translations->first();

                return (object) [
                    'name' => $tr?->name ?? $cat->name,
                    'slug' => $tr?->slug ?? $cat->slug,
                ];
            });
    }

    /**
     * All active categories (flat — no hierarchy), decorated with per-locale
     * name/slug and published post counts. Powers the blog index category pills.
     */
    public function getActiveTreeDecorated(string $locale): Collection
    {
        return $this->query()
            ->active()
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->withCount(['posts as total_blog_count' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->get()
            ->each(function (BlogCategory $cat) {
                $tr = $cat->translations->first();
                $cat->name = $tr?->name ?? $cat->name;
                $cat->slug = $tr?->slug ?? $cat->slug;
            });
    }
}
