<?php

namespace App\Observers;

use App\Jobs\Seo\SyncJsonldSchema;
use App\Jobs\Seo\SyncLlmsEntry;
use App\Jobs\Seo\SyncSitemapEntry;
use App\Models\Category;
use App\Models\Seo\GeoEntityProfile;
use App\Models\Seo\JsonldSchema;
use App\Models\Seo\LlmsEntry;
use App\Models\Seo\SeoMeta;
use App\Models\Seo\SitemapEntry;
use App\Services\Category\CategoryService;

class CategoryObserver
{
    /**
     * Defense-in-depth against a circular parent chain (A→B→A, or deeper) and
     * against nesting past the 2-level max (root → child only). The Filament
     * form already filters both cases out of the parent picker, but this also
     * covers writes from the MCP upsert endpoint and any future direct
     * Eloquent write that bypasses the form.
     */
    public function saving(Category $category): void
    {
        if (! $category->isDirty('parent_id') || ! $category->parent_id) {
            return;
        }

        if ($category->exists) {
            $blocked = array_map(
                'strval',
                [$category->getKey(), ...$category->descendantIds()],
            );

            if (in_array((string) $category->parent_id, $blocked, true)) {
                throw new \DomainException(
                    "Category #{$category->getKey()}: parent_id {$category->parent_id} would create a circular parent reference.",
                );
            }
        }

        $parent = Category::withTrashed()->find($category->parent_id);

        if ($parent && $parent->parent_id) {
            throw new \DomainException(
                "Category parent_id {$category->parent_id} is itself a child category — nesting is capped at 2 levels.",
            );
        }
    }

    public function saved(Category $category): void
    {
        $morphClass = $category->getMorphClass();

        if (! $category->is_active) {
            // Inactive category must not appear in sitemap, LLMs docs, or page <head>.
            $this->deactivateSeoEntries($morphClass, $category->getKey());

            // Children require an active parent to resolve (Category::isPubliclyVisible()) —
            // pull their SEO surface too so sitemap/llms.txt stop listing now-404 URLs.
            $this->cascadeDeactivateChildren($category);

            app(CategoryService::class)->bustTreeCache();

            return;
        }

        foreach (config('app.supported_locales') as $locale) {
            if ($category->translations()->where('locale', $locale)->exists()) {
                dispatch(new SyncJsonldSchema($category, $locale))->onQueue('seo');
                dispatch(new SyncSitemapEntry($category, $locale))->onQueue('seo');
                dispatch(new SyncLlmsEntry($category, $locale))->onQueue('seo');
            }
        }

        // Parent just (re)activated — resync SEO for children that are themselves
        // active but had their SEO surface pulled while this parent was inactive.
        $this->cascadeResyncActiveChildren($category);

        app(CategoryService::class)->bustTreeCache();
    }

    /**
     * Soft-delete deactivates all SEO entries — rows are kept for potential restore.
     */
    public function deleted(Category $category): void
    {
        $morphClass = $category->getMorphClass();

        $this->deactivateSeoEntries($morphClass, $category->getKey());
        $this->cascadeDeactivateChildren($category);

        app(CategoryService::class)->bustTreeCache();
    }

    public function restored(Category $category): void
    {
        if (! $category->is_active) {
            return;
        }

        foreach (config('app.supported_locales') as $locale) {
            if ($category->translations()->where('locale', $locale)->exists()) {
                dispatch(new SyncJsonldSchema($category, $locale))->onQueue('seo');
                dispatch(new SyncSitemapEntry($category, $locale))->onQueue('seo');
                dispatch(new SyncLlmsEntry($category, $locale))->onQueue('seo');
            }
        }

        $this->cascadeResyncActiveChildren($category);

        app(CategoryService::class)->bustTreeCache();
    }

    /**
     * Pull the SEO surface (sitemap/llms/json-ld) for one category — does not
     * touch its own `is_active`/`deleted_at`, only the derived SEO rows.
     */
    private function deactivateSeoEntries(string $morphClass, int|string $modelId): void
    {
        SitemapEntry::where('model_type', $morphClass)
            ->where('model_id', $modelId)
            ->update(['is_active' => false]);

        LlmsEntry::where('model_type', $morphClass)
            ->where('model_id', $modelId)
            ->update(['is_active' => false]);

        JsonldSchema::where('model_type', $morphClass)
            ->where('model_id', $modelId)
            ->update(['is_active' => false]);
    }

    /**
     * Every direct child becomes unreachable while $category is inactive/trashed
     * (Category::isPubliclyVisible() requires an active, non-trashed parent) —
     * regardless of the child's own `is_active` flag, which is left untouched.
     */
    private function cascadeDeactivateChildren(Category $category): void
    {
        $morphClass = $category->getMorphClass();

        Category::where('parent_id', $category->getKey())
            ->get()
            ->each(fn (Category $child) => $this->deactivateSeoEntries($morphClass, $child->getKey()));
    }

    /**
     * Re-sync SEO for children that are themselves active — they were only
     * hidden because $category (their parent) was inactive/trashed.
     */
    private function cascadeResyncActiveChildren(Category $category): void
    {
        Category::where('parent_id', $category->getKey())
            ->where('is_active', true)
            ->get()
            ->each(function (Category $child): void {
                foreach (config('app.supported_locales') as $locale) {
                    if ($child->translations()->where('locale', $locale)->exists()) {
                        dispatch(new SyncJsonldSchema($child, $locale))->onQueue('seo');
                        dispatch(new SyncSitemapEntry($child, $locale))->onQueue('seo');
                        dispatch(new SyncLlmsEntry($child, $locale))->onQueue('seo');
                    }
                }
            });
    }

    /**
     * Force delete: remove all polymorphic SEO rows from DB.
     * Runs BEFORE the SQL DELETE so model_id is still resolvable.
     */
    public function forceDeleting(Category $category): void
    {
        $morphClass = $category->getMorphClass();
        $modelId = $category->getKey();

        SeoMeta::where('model_type', $morphClass)->where('model_id', $modelId)->delete();
        GeoEntityProfile::where('model_type', $morphClass)->where('model_id', $modelId)->delete();
        JsonldSchema::where('model_type', $morphClass)->where('model_id', $modelId)->delete();
        SitemapEntry::where('model_type', $morphClass)->where('model_id', $modelId)->delete();
        LlmsEntry::where('model_type', $morphClass)->where('model_id', $modelId)->delete();

        app(CategoryService::class)->bustTreeCache();
    }
}
