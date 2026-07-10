<?php

namespace App\Http\Controllers\Web;

use App\Enums\BlogPostStatus;
use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use App\Models\Setting;
use App\Repositories\Eloquent\BlogCategoryRepository;
use App\Repositories\Eloquent\BlogPostRepository;
use App\Repositories\Eloquent\BlogTagRepository;
use App\Services\Seo\JsonldService;
use App\Services\Seo\SeoService;
use App\Support\HeadingAnchors;
use App\Support\ImageDimensions;
use App\Support\LocaleUrl;
use App\Support\RichContentHtml;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Tiptap\Editor;
use Tiptap\Extensions\StarterKit;
use Tiptap\Nodes\Image;
use Tiptap\Nodes\Table;
use Tiptap\Nodes\TableCell;
use Tiptap\Nodes\TableHeader;
use Tiptap\Nodes\TableRow;

class BlogController extends Controller
{
    public function __construct(
        private BlogPostRepository $blogPostRepository,
        private BlogCategoryRepository $blogCategoryRepository,
        private BlogTagRepository $blogTagRepository,
    ) {}

    public function index(string $locale): View
    {
        $search = request()->string('q')->toString() ?: null;
        $categoryFilter = array_filter((array) request('blog_category', []));

        // ── Category filter pills ──────────────────────────────────────────────
        $blogCategories = $this->blogCategoryRepository->getActiveTreeDecorated($locale);

        // ── Blog posts query ───────────────────────────────────────────────────
        $blogs = $this->blogPostRepository->paginateIndexDecorated($locale, $search, array_values($categoryFilter), 12);

        // Resolve display name for the active category filter label
        $categoryName = null;
        if ($categoryFilter) {
            $matched = $blogCategories->first(fn ($cat) => in_array($cat->slug, $categoryFilter));
            if ($matched) {
                $categoryName = $matched->name;
            }
        }

        view()->share('alternateUrls', [
            'vi' => route('vi.blog.index'),
            'en' => route('en.blog.index'),
        ]);

        $fallbackTitle = $locale === 'vi'
            ? (Setting::get('blog_index_title') ?: 'Blog - Tin tức & Bài viết')
            : (Setting::get('blog_index_title_en') ?: 'Blog - News & Articles');

        // Hero image — admin-managed via Filament BlogSetting (extra['blog']).
        $blogExtra = (array) (BusinessProfile::instance()->extra['blog'] ?? []);
        $blogHeroRaw = $blogExtra['hero_image'] ?? null;
        $blogHeroUrl = $blogHeroRaw
            ? (str_starts_with($blogHeroRaw, 'http') ? $blogHeroRaw : asset('storage/'.ltrim($blogHeroRaw, '/')))
            : null;
        // Alt tự điền, fallback "Journal - {site_name}".
        $blogHeroAlt = ($locale === 'en' ? ($blogExtra['hero_alt_en'] ?? null) : ($blogExtra['hero_alt'] ?? null))
            ?: 'Journal - '.Setting::get('site_name');

        // Canonical: bỏ query (search/category filter), giữ page — cùng rule với PLP.
        $canonicalUrl = $blogs->currentPage() > 1
            ? route($locale.'.blog.index').'?page='.$blogs->currentPage()
            : route($locale.'.blog.index');

        // CollectionPage + ItemList runtime (đổi theo trang/filter, không lưu DB).
        $positionOffset = ($blogs->currentPage() - 1) * $blogs->perPage();
        $jsonldSchemas = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => $fallbackTitle,
                'url' => $canonicalUrl,
                'inLanguage' => $locale,
                'mainEntity' => [
                    '@type' => 'ItemList',
                    'numberOfItems' => $blogs->total(),
                    'itemListElement' => $blogs->getCollection()->values()
                        ->filter(fn ($post) => filled($post->category_slug) && filled($post->slug))
                        ->values()
                        ->map(fn ($post, $i) => [
                            '@type' => 'ListItem',
                            'position' => $positionOffset + $i + 1,
                            'url' => route($locale.'.blog.show', ['category_slug' => $post->category_slug, 'slug' => $post->slug]),
                            'name' => $post->title,
                        ])->all(),
                ],
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => $locale === 'vi' ? 'Trang chủ' : 'Home', 'item' => route($locale.'.index')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => LocaleUrl::listLabel('blog_post', $locale)],
                ],
            ],
        ];

        return view('pages.blog.index', [
            'blogs' => $blogs,
            'blogCategories' => $blogCategories,
            'searchTerm' => $search,
            'category' => $categoryName,
            'activeCategorySlugs' => array_values($categoryFilter),
            'locale' => $locale,
            'seoMeta' => null,
            'jsonldSchemas' => $jsonldSchemas,
            'canonicalUrl' => $canonicalUrl,
            'blogHeroUrl' => $blogHeroUrl,
            'blogHeroAlt' => $blogHeroAlt,
            'fallbackTitle' => $fallbackTitle,
            'fallbackDescription' => $locale === 'vi'
                ? (Setting::get('blog_index_description') ?: 'Cập nhật kiến thức, xu hướng và câu chuyện từ chúng tôi.')
                : (Setting::get('blog_index_description_en') ?: 'Insights, trends and stories from our team.'),
            'fallbackImage' => (($ogRaw = Setting::get('default_og_image')) && filled($ogRaw))
                                        ? (str_starts_with($ogRaw, 'http') ? $ogRaw : asset('storage/'.ltrim($ogRaw, '/')))
                                        : null,
            'ogType' => 'website',
        ]);
    }

    public function category(string $locale, string $slug): View|RedirectResponse
    {
        $translation = $this->blogCategoryRepository->findTranslationBySlug($locale, $slug);

        if (! $translation) {
            $viTranslation = $this->blogCategoryRepository->findTranslationBySlug(config('app.fallback_locale'), $slug);

            if ($viTranslation) {
                return redirect(
                    LocaleUrl::for('blog_category', $viTranslation->slug, config('app.fallback_locale')),
                    302
                );
            }

            abort(404);
        }

        $blogCategory = $translation->blogCategory;
        if (! $blogCategory || ! $blogCategory->is_active) {
            abort(404);
        }

        $alternateUrls = app(SeoService::class)->alternateUrls($blogCategory);
        $seoMeta = $blogCategory->seoMeta($locale);
        $jsonldSchemas = app(JsonldService::class)->getActiveSchemas($blogCategory, $locale)
            ->pluck('payload')
            ->toArray();
        $fallbackTitle = $translation->name;
        $fallbackDescription = $translation->description ?? '';
        $fallbackImage = (($ogRaw = Setting::get('default_og_image')) && filled($ogRaw))
                                    ? (str_starts_with($ogRaw, 'http') ? $ogRaw : asset('storage/'.ltrim($ogRaw, '/')))
                                    : null;
        $ogType = 'website';

        // ── Posts query (this category only — blog categories are flat, no children) ──
        $blogs = $this->blogPostRepository->paginateByCategoryIdsDecorated($locale, [$blogCategory->id], 12);

        // Self-referencing canonical for pagination — same rule as blog index()/PLP.
        $canonicalUrl = $blogs->currentPage() > 1
            ? route($locale.'.blog.category', $translation->slug).'?page='.$blogs->currentPage()
            : route($locale.'.blog.category', $translation->slug);

        $blogCategory->loadMissing('seoMetas');

        // ── Rich content — admin-managed (BlogCategoryResource RichEditor).
        // Stored as plain HTML (rich_content has no 'array' cast, unlike product
        // CategoryTranslation), so no Tiptap JSON→HTML conversion needed here.
        // Page banner already owns the single <h1> — never let admin content
        // emit a second one (see RichContentHtml docblock).
        $richContentHtml = filled($translation->rich_content)
            ? RichContentHtml::capHeadingLevels($translation->rich_content)
            : null;

        $blogCategory->loadMissing('geoProfiles');
        $geoProfile = $blogCategory->geoProfiles->firstWhere('locale', $locale);
        $faqItems = $geoProfile?->faq ?? [];
        $faqs = array_values(array_filter(array_map(
            fn ($f) => (trim($f['question'] ?? '') && trim($f['answer'] ?? '')) ? $f : null,
            $faqItems
        )));

        // ── Breadcrumb (visible) — mirrors JsonldService::buildBlogCategoryBreadcrumb()
        // so the visible trail never disagrees with the BreadcrumbList JSON-LD.
        $breadcrumbItems = [
            ['label' => $locale === 'vi' ? 'Trang chủ' : 'Home', 'url' => route($locale.'.index')],
            ['label' => LocaleUrl::listLabel('blog_post', $locale), 'url' => route($locale.'.blog.index')],
        ];
        $breadcrumbItems[] = ['label' => $translation->name, 'url' => null];

        view()->share('alternateUrls', $alternateUrls);

        return view('pages.blog.category', compact(
            'blogCategory', 'translation', 'blogs', 'alternateUrls', 'seoMeta', 'jsonldSchemas', 'locale',
            'fallbackTitle', 'fallbackDescription', 'fallbackImage', 'ogType', 'faqs', 'breadcrumbItems',
            'richContentHtml', 'canonicalUrl'
        ) + ['noScrollSmoother' => true]);
    }

    public function show(string $locale, string $categorySlug, string $slug): View|RedirectResponse
    {
        $translation = $this->blogPostRepository->findTranslationBySlug($slug, $locale);

        if (! $translation) {
            $viTranslation = $this->blogPostRepository->findTranslationBySlug($slug);

            if ($viTranslation) {
                $post = $viTranslation->blogPost->load('blogCategory.translations');

                return redirect(LocaleUrl::forBlogPost($post, $viTranslation->locale), 302);
            }

            abort(404);
        }

        $post = $translation->blogPost;
        if (! $post
            || $post->status !== BlogPostStatus::Published
            || ! $post->published_at
            || $post->published_at->gt(now())) {
            abort(404);
        }

        $post->loadMissing([
            'author',
            'blogCategory.translations',
            'tags',
        ]);

        // Validate category slug — redirect to canonical if wrong
        $catTr = $post->blogCategory?->translations->firstWhere('locale', $locale);
        $actualCategorySlug = $catTr?->slug ?? $post->blogCategory?->slug;

        if ($post->blog_category_id && $actualCategorySlug && $categorySlug !== $actualCategorySlug) {
            return redirect(LocaleUrl::forBlogPost($post, $locale), 301);
        }

        $alternateUrls = app(SeoService::class)->alternateUrls($post);
        $seoMeta = $post->seoMeta($locale);
        $jsonldSchemas = app(JsonldService::class)->getActiveSchemas($post, $locale)
            ->pluck('payload')
            ->toArray();

        // ── GEO / FAQs ────────────────────────────────────────────────────────
        $post->loadMissing('geoProfiles');
        $geoProfile = $post->geoProfiles->firstWhere('locale', $locale);
        $faqItems = $geoProfile?->faq
            ?? ($locale === 'vi' ? ($post->faq_items_vi ?? []) : ($post->faq_items_en ?? []));
        $faqs = array_values(array_filter(array_map(
            fn ($f) => (trim($f['question'] ?? '') && trim($f['answer'] ?? '')) ? $f : null,
            $faqItems
        )));

        $aiSummary = trim((string) ($geoProfile?->ai_summary ?? '')) ?: null;
        $keyFacts = array_values(array_filter(
            (array) ($geoProfile?->key_facts ?? []),
            fn ($f) => trim($f['label'] ?? '') !== '' && trim($f['value'] ?? '') !== ''
        ));

        // ── Blog DTO ───────────────────────────────────────────────────────────
        $catTr = $post->blogCategory?->translations->firstWhere('locale', $locale);
        $rawImage = $post->featured_image;
        $rawBody = $translation->body ?? '';

        // Convert Tiptap JSON → HTML if needed
        $bodyHtml = $rawBody;
        if (filled($rawBody)) {
            $decoded = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['type'])) {
                try {
                    $bodyHtml = (new Editor([
                        'extensions' => [
                            new StarterKit,
                            new Image,
                            new Table,
                            new TableRow,
                            new TableHeader,
                            new TableCell,
                        ],
                    ]))->setContent($decoded)->getHTML();
                } catch (\Throwable) {
                    $bodyHtml = $rawBody;
                }
            }
        }

        // Post title already owns the single <h1> — never let the body emit a
        // second one (see RichContentHtml docblock).
        $bodyHtml = RichContentHtml::capHeadingLevels($bodyHtml);

        $readMins = max(1, (int) ceil(str_word_count(strip_tags($bodyHtml)) / 200));
        $bodyHtml = HeadingAnchors::inject($bodyHtml);

        // ── Breadcrumb (visible) — mirrors JsonldService::buildBlogPostBreadcrumb()
        // so the visible trail never disagrees with the BreadcrumbList JSON-LD.
        $breadcrumbItems = [
            ['label' => $locale === 'vi' ? 'Trang chủ' : 'Home', 'url' => route($locale.'.index')],
            ['label' => LocaleUrl::listLabel('blog_post', $locale), 'url' => route($locale.'.blog.index')],
        ];
        if ($post->blogCategory && filled($catTr?->name ?? $post->blogCategory->name)) {
            $breadcrumbItems[] = [
                'label' => $catTr?->name ?? $post->blogCategory->name,
                'url' => LocaleUrl::for('blog_category', $catTr?->slug ?? $post->blogCategory->slug, $locale),
            ];
        }
        $breadcrumbItems[] = ['label' => $translation->title, 'url' => null];

        $blog = (object) [
            'title' => $translation->title,
            'slug' => $translation->slug,
            'excerpt' => $translation->excerpt,
            'content' => $bodyHtml,
            'category' => $catTr?->name ?? $post->blogCategory?->name,
            'category_slug' => $catTr?->slug ?? $post->blogCategory?->slug,
            'featured_image' => $rawImage ? 'storage/'.ltrim($rawImage, '/') : null,
            'featured_image_dimensions' => ImageDimensions::resolve($rawImage),
            'published_at' => $post->published_at,
            'updated_at' => $post->updated_at,
            'formatted_published_date' => $post->published_at?->translatedFormat('d M, Y'),
            'reading_time' => $locale === 'vi' ? "{$readMins} phút đọc" : "{$readMins} min read",
            'author' => $post->author,
            'tags' => $post->tags->pluck('name')->all(),
            'faqs' => $faqs,
            'ai_summary' => $aiSummary,
            'key_facts' => $keyFacts,
            'seo_description' => $seoMeta?->meta_description ?? $translation->excerpt,
            'canonical_url' => url()->current(),
        ];

        $fallbackTitle = $translation->title;
        $fallbackDescription = $translation->excerpt ?? '';
        $fallbackImage = $rawImage ? url('storage/'.ltrim($rawImage, '/')) : null;
        $ogType = 'article';

        // ── OG article:* tags (seo-head.blade.php) ─────────────────────────────
        $articleMeta = [
            'published_time' => $post->published_at?->toIso8601String(),
            'modified_time' => $post->updated_at?->toIso8601String(),
            'author' => $post->author?->slug ? route($locale.'.author.show', $post->author->slug) : null,
            'section' => $blog->category,
            'tags' => $blog->tags,
        ];

        // ── Sidebar: categories ────────────────────────────────────────────────
        $categories = $this->blogCategoryRepository->getActiveDecorated($locale);

        // ── Sidebar: latest posts (excl. current) ─────────────────────────────
        $sidebarPostsBase = $this->blogPostRepository->latestExcludingDecorated($locale, $post->id, 13);

        $latestPosts = $sidebarPostsBase->take(3);
        $morePostsList = $sidebarPostsBase->slice(3);

        // ── Related posts (same category, excl. current) ──────────────────────
        $relatedPosts = $post->blog_category_id
            ? $this->blogPostRepository->relatedDecorated($locale, $post->blog_category_id, $post->id, 4)
            : collect();

        // ── Sidebar: tags ─────────────────────────────────────────────────────
        $allTags = $this->blogTagRepository->getNamesWithPublishedPosts();

        view()->share('alternateUrls', $alternateUrls);

        return view('pages.blog.show', compact(
            'blog', 'alternateUrls', 'seoMeta', 'jsonldSchemas', 'locale',
            'fallbackTitle', 'fallbackDescription', 'fallbackImage', 'ogType', 'articleMeta',
            'categories', 'latestPosts', 'morePostsList', 'relatedPosts', 'allTags',
            'breadcrumbItems'
        ) + ['noScrollSmoother' => true]);
    }
}
