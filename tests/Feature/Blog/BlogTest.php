<?php

namespace Tests\Feature\Blog;

use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('scout.driver', 'collection');
    }

    // ── 1. List published posts ───────────────────────────────────────────────

    public function test_can_list_published_blog_posts(): void
    {
        BlogPost::factory()->count(3)->published()->create();

        $this->getJson('/api/v1/blog')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'title', 'slug', 'excerpt', 'published_at']],
                'meta' => ['current_page', 'total', 'per_page', 'last_page'],
            ])
            ->assertJsonPath('meta.total', 3);
    }

    // ── 2. Draft posts hidden from list ──────────────────────────────────────

    public function test_draft_posts_not_in_list(): void
    {
        BlogPost::factory()->count(2)->published()->create();
        BlogPost::factory()->draft()->create();

        $this->getJson('/api/v1/blog')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 2);
    }

    // ── 3. Filter by category ─────────────────────────────────────────────────

    public function test_can_filter_blog_by_category(): void
    {
        $category = BlogCategory::factory()->create(['slug' => 'tech-news']);
        $inCat    = BlogPost::factory()->published()->create(['blog_category_id' => $category->id]);
        $inCat->translations()->create(['locale' => 'vi', 'title' => 'In category post', 'slug' => 'in-category-post', 'excerpt' => 'Excerpt']);
        BlogPost::factory()->published()->create(); // different category

        $this->getJson('/api/v1/blog?category=tech-news&locale=vi')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'in-category-post');
    }

    // ── 4. Filter by tag ──────────────────────────────────────────────────────

    public function test_can_filter_blog_by_tag(): void
    {
        $tag    = BlogTag::factory()->create(['slug' => 'casambi']);
        $tagged = BlogPost::factory()->published()->create();
        $tagged->translations()->create(['locale' => 'vi', 'title' => 'Tagged post', 'slug' => 'tagged-post', 'excerpt' => 'Excerpt']);
        $tagged->tags()->attach($tag->id);
        BlogPost::factory()->published()->create(); // untagged

        $this->getJson('/api/v1/blog?tag=casambi&locale=vi')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.slug', 'tagged-post');
    }

    // ── 5. Blog post detail ───────────────────────────────────────────────────

    public function test_can_get_blog_post_detail(): void
    {
        $post = BlogPost::factory()->published()->create();
        $post->translations()->create([
            'locale' => 'vi', 'title' => 'Mesh Lighting Guide', 'slug' => 'mesh-lighting',
            'excerpt' => 'Excerpt', 'body' => 'Full body content.',
        ]);

        $this->getJson('/api/v1/blog/mesh-lighting?locale=vi')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'title', 'slug', 'excerpt', 'content',
                    'seo', 'jsonld_schemas', 'published_at',
                ],
            ])
            ->assertJsonPath('data.slug', 'mesh-lighting');
    }

    // ── 6. Draft post returns 404 ─────────────────────────────────────────────

    public function test_draft_post_detail_returns_404(): void
    {
        $post = BlogPost::factory()->draft()->create();
        $post->translations()->create(['locale' => 'vi', 'title' => 'Hidden draft', 'slug' => 'hidden-draft', 'excerpt' => 'x']);

        $this->getJson('/api/v1/blog/hidden-draft')
            ->assertStatus(404);
    }

    // ── 7. Only approved comments returned ───────────────────────────────────

    public function test_can_list_approved_comments(): void
    {
        $post = BlogPost::factory()->published()->create();
        $post->translations()->create(['locale' => 'vi', 'title' => 'Post with comments', 'slug' => 'post-with-comments', 'excerpt' => 'x']);

        BlogComment::factory()->count(2)->approved()->create(['blog_post_id' => $post->id]);
        BlogComment::factory()->pending()->create(['blog_post_id' => $post->id]);

        $this->getJson('/api/v1/blog/post-with-comments/comments')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 2);
    }

    // ── 8. Authenticated user can submit comment ──────────────────────────────

    public function test_authenticated_user_can_submit_comment(): void
    {
        $user  = User::factory()->create();
        $post  = BlogPost::factory()->published()->create();
        $post->translations()->create(['locale' => 'vi', 'title' => 'Commentable post', 'slug' => 'commentable-post', 'excerpt' => 'x']);
        $token = $user->createToken('api-token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/blog/commentable-post/comments', ['body' => 'Great article!'])
            ->assertStatus(201)
            ->assertJsonPath('data.is_approved', false);
    }

    // ── 9. Unauthenticated user cannot submit comment ─────────────────────────

    public function test_unauthenticated_user_cannot_comment(): void
    {
        $post = BlogPost::factory()->published()->create();
        $post->translations()->create(['locale' => 'vi', 'title' => 'Auth required post', 'slug' => 'auth-required-post', 'excerpt' => 'x']);

        $this->postJson('/api/v1/blog/auth-required-post/comments', ['body' => 'Hello!'])
            ->assertStatus(401);
    }

    // ── 10. Blog categories list ──────────────────────────────────────────────

    public function test_can_get_blog_categories(): void
    {
        BlogCategory::factory()->count(2)->create();

        $this->getJson('/api/v1/blog/categories')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'slug']],
            ]);
    }

    // ── 11. Blog tags list ────────────────────────────────────────────────────

    public function test_can_get_blog_tags(): void
    {
        BlogTag::factory()->count(3)->create();

        $this->getJson('/api/v1/blog/tags')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'slug']],
            ])
            ->assertJsonCount(3, 'data');
    }
}
