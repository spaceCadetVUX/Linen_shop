<?php

namespace App\Repositories\Eloquent;

use App\Enums\BlogPostStatus;
use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\BlogPostTranslation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    // ── List ──────────────────────────────────────────────────────────────────

    /**
     * Paginated published posts.
     *
     * Filters: category (slug), tag (slug), sort (newest|oldest)
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
        \App\Models\BlogCategory $category,
        int $perPage = 12,
        string $direction = 'desc',
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
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
     */
    public function findPublishedBySlug(string $slug): ?BlogPost
    {
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
            ->where('slug', $slug)
            ->first();
    }

    // ── Comments ──────────────────────────────────────────────────────────────

    /**
     * Published post by slug — lightweight, no heavy relations.
     * Used by comment endpoints that only need the post to exist.
     */
    public function findPublishedBySlugOrFail(string $slug): BlogPost
    {
        $post = $this->query()
            ->published()
            ->where('slug', $slug)
            ->first();

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
            'user_id'     => $userId,
            'body'        => $body,
            'is_approved' => false,
        ]);
    }
}
