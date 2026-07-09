<?php

namespace App\Repositories\Eloquent;

use App\Enums\BlogPostStatus;
use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\BlogPostTranslation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class BlogPostRepository extends BaseRepository
{
    protected function model(): string
    {
        return BlogPost::class;
    }

    /**
     * Latest published posts, decorated to the shape <x-blog.card> expects:
     * title, slug, excerpt, category, category_slug, featured_image
     * ('storage/...'), formatted_published_date. Used by the homepage and
     * PDP journal sections.
     */
    public function latestDecorated(string $locale, int $limit = 4): Collection
    {
        return BlogPostTranslation::where('blog_post_translations.locale', $locale)
            ->join('blog_posts', 'blog_posts.id', '=', 'blog_post_translations.blog_post_id')
            ->where('blog_posts.status', BlogPostStatus::Published)
            ->where('blog_posts.published_at', '<=', now())
            ->whereNull('blog_posts.deleted_at')
            ->select('blog_post_translations.*')
            ->with(['blogPost.blogCategory.translations' => fn ($q) => $q->where('locale', $locale)])
            ->orderByDesc('blog_posts.published_at')
            ->limit($limit)
            ->get()
            ->map(function ($tr) {
                $p = $tr->blogPost;
                $cTr = $p?->blogCategory?->translations->first();
                $img = $p?->featured_image;

                return (object) [
                    'title' => $tr->title,
                    'slug' => $tr->slug,
                    'excerpt' => $tr->excerpt,
                    'category' => $cTr?->name ?? $p?->blogCategory?->name,
                    'category_slug' => $cTr?->slug ?? $p?->blogCategory?->slug,
                    'featured_image' => $img ? 'storage/'.ltrim($img, '/') : null,
                    'formatted_published_date' => $p?->published_at?->translatedFormat('d M, Y'),
                ];
            });
    }

    // ── Web blog pages — translation-decorated ──────────────────────────────────
    // These back the Blade blog index/category/post pages, which are entirely
    // multilingual (content lives in blog_post_translations — blog_posts.title/
    // slug were dropped in 2026_05_27_007_drop_title_slug_from_blog_posts.php).
    // Unlike paginate()/findPublishedBySlug() below (single-locale, predate the
    // i18n migration — see their doc comments), these always join through the
    // translation row for the given locale.

    /**
     * Base query: published posts joined to their translation row for $locale,
     * with the translation fields selected and blogPost+category eager loaded.
     */
    private function decoratedBaseQuery(string $locale): Builder|\Illuminate\Database\Eloquent\Builder
    {
        return BlogPostTranslation::where('blog_post_translations.locale', $locale)
            ->join('blog_posts', 'blog_posts.id', '=', 'blog_post_translations.blog_post_id')
            ->where('blog_posts.status', BlogPostStatus::Published)
            ->where('blog_posts.published_at', '<=', now())
            ->whereNull('blog_posts.deleted_at')
            ->select('blog_post_translations.*')
            ->with(['blogPost.blogCategory.translations' => fn ($q) => $q->where('locale', $locale)]);
    }

    /**
     * Decorates a BlogPost (via its translation row) with the flat fields
     * <x-blog.card> and the blog Blade pages expect: title, slug, excerpt,
     * category, category_slug, featured_image ('storage/...'),
     * formatted_published_date.
     */
    private function decorateTranslation(BlogPostTranslation $tr): BlogPost
    {
        $post = $tr->blogPost;
        $catTr = $post?->blogCategory?->translations->first();
        $rawImage = $post?->featured_image;

        $post->slug = $tr->slug;
        $post->title = $tr->title;
        $post->excerpt = $tr->excerpt;
        $post->category = $catTr?->name ?? $post?->blogCategory?->name;
        $post->category_slug = $catTr?->slug ?? $post?->blogCategory?->slug;
        $post->featured_image = $rawImage ? 'storage/'.ltrim($rawImage, '/') : null;
        $post->formatted_published_date = $post?->published_at?->translatedFormat('d M, Y');

        return $post;
    }

    /**
     * Blog index — paginated, decorated, with optional free-text search
     * (title/excerpt) and category-slug filter (root or child slugs mixed).
     */
    public function paginateIndexDecorated(
        string $locale,
        ?string $search = null,
        array $categorySlugs = [],
        int $perPage = 12,
    ): LengthAwarePaginator {
        $query = $this->decoratedBaseQuery($locale)->orderByDesc('blog_posts.published_at');

        if ($search) {
            $query->where(fn ($q) => $q
                ->where('blog_post_translations.title', 'ilike', "%{$search}%")
                ->orWhere('blog_post_translations.excerpt', 'ilike', "%{$search}%"));
        }

        if ($categorySlugs) {
            $query->whereExists(fn ($q) => $q
                ->from('blog_category_translations')
                ->whereColumn('blog_category_translations.blog_category_id', 'blog_posts.blog_category_id')
                ->where('blog_category_translations.locale', $locale)
                ->whereIn('blog_category_translations.slug', $categorySlugs));
        }

        return $query->paginate($perPage)
            ->withQueryString()
            ->through(fn ($tr) => $this->decorateTranslation($tr));
    }

    /**
     * Blog category page — paginated, decorated posts within a set of
     * category ids (the category itself plus its direct children).
     */
    public function paginateByCategoryIdsDecorated(string $locale, array $categoryIds, int $perPage = 12): LengthAwarePaginator
    {
        return $this->decoratedBaseQuery($locale)
            ->whereIn('blog_posts.blog_category_id', $categoryIds)
            ->orderByDesc('blog_posts.published_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($tr) => $this->decorateTranslation($tr));
    }

    /**
     * Author page — paginated, decorated posts written by one author.
     */
    public function paginateByAuthorIdDecorated(string $locale, int $authorId, int $perPage = 12): LengthAwarePaginator
    {
        return $this->decoratedBaseQuery($locale)
            ->where('blog_posts.author_id', $authorId)
            ->orderByDesc('blog_posts.published_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($tr) => $this->decorateTranslation($tr));
    }

    /**
     * Latest published posts excluding one post (blog post sidebar: "latest" +
     * "more posts" are two slices of the same ordered list).
     */
    public function latestExcludingDecorated(string $locale, string $excludePostId, int $limit): Collection
    {
        return $this->decoratedBaseQuery($locale)
            ->where('blog_post_translations.blog_post_id', '!=', $excludePostId)
            ->orderByDesc('blog_posts.published_at')
            ->limit($limit)
            ->get()
            ->map(fn ($tr) => $this->decorateTranslation($tr));
    }

    /**
     * Other published posts in the same category, excluding the current one.
     */
    public function relatedDecorated(string $locale, int $categoryId, string $excludePostId, int $limit = 4): Collection
    {
        return $this->decoratedBaseQuery($locale)
            ->where('blog_posts.blog_category_id', $categoryId)
            ->where('blog_post_translations.blog_post_id', '!=', $excludePostId)
            ->orderByDesc('blog_posts.published_at')
            ->limit($limit)
            ->get()
            ->map(fn ($tr) => $this->decorateTranslation($tr));
    }

    /**
     * Translation row (+ parent BlogPost) for a slug in a given locale.
     * Used to resolve the public blog post page and legacy flat-URL redirects.
     */
    public function findTranslationBySlug(string $slug, ?string $locale = null): ?BlogPostTranslation
    {
        $query = BlogPostTranslation::where('slug', $slug)
            ->with(['blogPost.blogCategory.translations']);

        if ($locale !== null) {
            $query->where('locale', $locale);
        }

        return $query->first();
    }

    // ── List ──────────────────────────────────────────────────────────────────

    /**
     * Paginated published posts.
     *
     * Filters: category (slug), tag (slug), sort (newest|oldest)
     *
     * ⚠ Single-locale — predates the blog i18n migration (2026_04_26_004
     * ml02_create_blog_post_translations_table). BlogPost no longer has its
     * own title/slug/excerpt/content (dropped by 2026_05_27_00{1,2,7}_drop_*).
     * Callers relying on model attributes (not a Resource reading via
     * translation()) will see them as null. Used by Api\V1\Blog\BlogPostController
     * index() — output is correct there only because BlogPostResource reads
     * translation($locale) itself rather than the model's own attributes.
     */
    public function paginate(int $perPage = 12, array $filters = [], array $with = []): LengthAwarePaginator
    {
        $direction = ($filters['sort'] ?? 'newest') === 'oldest' ? 'asc' : 'desc';

        $query = $this->query()
            ->published()
            ->with($with ?: ['author', 'blogCategory', 'tags'])
            ->orderBy('published_at', $direction);

        if (! empty($filters['category'])) {
            $query->whereHas('blogCategory', fn ($q) => $q->where('slug', $filters['category']));
        }

        if (! empty($filters['tag'])) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $filters['tag']));
        }

        return $query->paginate($perPage);
    }

    /**
     * Paginated published posts for a specific blog category.
     */
    public function paginateByCategory(
        BlogCategory $category,
        int $perPage = 12,
        string $direction = 'desc',
    ): LengthAwarePaginator {
        return $this->query()
            ->published()
            ->with(['author', 'blogCategory', 'tags'])
            ->where('blog_category_id', $category->id)
            ->orderBy('published_at', $direction)
            ->paginate($perPage);
    }

    // ── Detail ────────────────────────────────────────────────────────────────

    /**
     * Single published post by slug with all detail relations.
     *
     * blog_posts has no slug column anymore (i18n migration — see decorated*
     * methods above) — slug lives per-locale on blog_post_translations, so
     * the post id must be resolved through the translation row first.
     */
    public function findPublishedBySlug(string $slug): ?BlogPost
    {
        $postId = $this->resolvePostIdBySlug($slug);

        if (! $postId) {
            return null;
        }

        /** @var BlogPost|null */
        return $this->query()
            ->published()
            ->with([
                'author',
                'blogCategory.translations',
                'tags',
                'translations',
                'seoMetas',
                'activeSchemas',
            ])
            ->whereKey($postId)
            ->first();
    }

    private function resolvePostIdBySlug(string $slug): ?string
    {
        $locale = app()->getLocale();

        return BlogPostTranslation::where('slug', $slug)->where('locale', $locale)->value('blog_post_id')
            ?? BlogPostTranslation::where('slug', $slug)->value('blog_post_id');
    }

    // ── Comments ──────────────────────────────────────────────────────────────

    /**
     * Published post by slug — lightweight, no heavy relations.
     * Used by comment endpoints that only need the post to exist.
     */
    public function findPublishedBySlugOrFail(string $slug): BlogPost
    {
        $postId = $this->resolvePostIdBySlug($slug);

        $post = $postId
            ? $this->query()->published()->whereKey($postId)->first()
            : null;

        abort_if(! $post, 404, 'Blog post not found.');

        return $post;
    }

    /**
     * Paginated approved comments for a post.
     */
    public function getApprovedComments(BlogPost $post, int $perPage = 20): LengthAwarePaginator
    {
        return $post->comments()
            ->approved()
            ->with('user')
            ->orderBy('created_at')
            ->paginate($perPage);
    }

    /**
     * Create a pending comment on a post.
     */
    public function createComment(BlogPost $post, string $userId, string $body): BlogComment
    {
        return $post->comments()->create([
            'user_id' => $userId,
            'body' => $body,
            'is_approved' => false,
        ]);
    }
}
