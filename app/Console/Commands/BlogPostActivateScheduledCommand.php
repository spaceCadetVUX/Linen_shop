<?php

namespace App\Console\Commands;

use App\Enums\BlogPostStatus;
use App\Models\BlogPost;
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
     */
    public function handle(BlogPostObserver $observer): int
    {
        $posts = BlogPost::query()
            ->where('status', BlogPostStatus::Published)
            ->where('published_at', '<=', now())
            ->where(function ($query) {
                $query->whereHas('sitemapEntries', fn ($q) => $q->where('is_active', false))
                    ->orWhereDoesntHave('sitemapEntries')
                    ->orWhereHas('jsonldSchemas', fn ($q) => $q->where('is_active', false))
                    ->orWhereDoesntHave('jsonldSchemas')
                    ->orWhereHas('llmsEntries', fn ($q) => $q->where('is_active', false))
                    ->orWhereDoesntHave('llmsEntries');
            })
            ->with('translations')
            ->get();

        foreach ($posts as $post) {
            $observer->activate($post);
        }

        $this->info("Activated SEO sync for {$posts->count()} scheduled blog post(s).");

        return self::SUCCESS;
    }
}
