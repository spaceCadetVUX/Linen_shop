<?php

namespace App\Console\Commands;

use App\Enums\BlogPostStatus;
use App\Models\BlogPost;
use App\Models\Seo\JsonldSchema;
use App\Models\Seo\LlmsEntry;
use App\Models\Seo\SitemapEntry;
use App\Observers\BlogPostObserver;
use Illuminate\Console\Command;

class BlogPostActivateScheduledCommand extends Command
{
    protected $signature = 'blog-post:activate-scheduled';

    protected $description = 'Activate sitemap/JSON-LD/LLMs sync for scheduled blog posts once their published_at time has arrived';

    /**
     * BlogPostObserver only fires on save — a post scheduled with a future
     * published_at is correctly kept out of SEO surfaces at save time, but
     * nothing re-checks it once that time passes if nobody edits it again.
     * This command closes that gap by finding posts that are publicly
     * visible now but whose SEO entries are still missing or inactive.
     *
     * All three surfaces (sitemap/JSON-LD/LLMs) are checked independently —
     * activate() dispatches all three as separate queued jobs, so one can
     * succeed while another permanently fails (bad template data, a lost job
     * after a Horizon restart, etc.). Checking only sitemapEntries would let
     * that post drop out of consideration forever the moment its sitemap
     * entry alone looks fine.
     *
     * Deliberately avoids whereHas()/whereDoesntHave() on these morphMany
     * relations: Eloquent compiles them into a correlated EXISTS subquery
     * that compares model_id (varchar(36), the CLAUDE.md polymorphic
     * convention) directly against blog_posts.id (native Postgres uuid via
     * HasUuids) — two already-typed columns with no implicit cast between
     * them, so Postgres throws "operator does not exist: character varying
     * = uuid". Fetching each surface's active model_id set separately (bound
     * whereIn parameters, not a raw column comparison) and intersecting in
     * PHP sidesteps that entirely.
     */
    public function handle(BlogPostObserver $observer): int
    {
        $candidates = BlogPost::query()
            ->where('status', BlogPostStatus::Published)
            ->where('published_at', '<=', now())
            ->get();

        $morphClass = (new BlogPost())->getMorphClass();
        $candidateIds = $candidates->pluck('id')->map(fn ($id) => (string) $id)->all();

        $activeIds = fn (string $model) => $model::query()
            ->where('model_type', $morphClass)
            ->where('is_active', true)
            ->whereIn('model_id', $candidateIds)
            ->pluck('model_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $fullySyncedIds = array_intersect(
            $activeIds(SitemapEntry::class),
            $activeIds(JsonldSchema::class),
            $activeIds(LlmsEntry::class),
        );

        $posts = $candidates->reject(fn (BlogPost $post) => in_array((string) $post->id, $fullySyncedIds, true));

        foreach ($posts as $post) {
            $observer->activate($post);
        }

        $this->info("Activated SEO sync for {$posts->count()} scheduled blog post(s).");

        return self::SUCCESS;
    }
}
