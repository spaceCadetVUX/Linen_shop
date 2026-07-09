<?php

namespace App\Http\Controllers\Web;

use App\Enums\BlogPostStatus;
use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogCategoryTranslation;
use App\Models\BlogPostTranslation;
use App\Models\BlogTag;
use App\Models\BusinessProfile;
use App\Models\Seo\GeoEntityProfile;
use App\Models\Setting;
use App\Repositories\Eloquent\BlogPostRepository;
use App\Services\Seo\JsonldService;
use App\Services\Seo\SeoService;
use App\Support\LocaleUrl;
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
    public function __construct(private BlogPostRepository $blogPostRepository) {}

    public function index(string $locale): View
    {
        $search = request()->string('q')->toString() ?: null;
        $categoryFilter = array_filter((array) request('blog_category', []));

        // ── Category filter pills ──────────────────────────────────────────────
        $blogCategories = BlogCategory::active()
            ->whereNull('parent_id')
            ->with([
                'translations' => fn ($q) => $q->where('locale', $locale),
                'children' => fn ($q) => $q->active()
                    ->withCount(['posts as blog_count' => fn ($q) => $q->published()])
                    ->with(['translations' => fn ($q) => $q->where('locale', $locale)]),
            ])
            ->withCount(['posts as root_count' => fn ($q) => $q->published()])
            ->orderBy('sort_order')
            ->get()
            ->each(function ($cat) {
                $tr = $cat->translations->first();
                $cat->name = $tr?->name ?? $cat->name;
                $cat->slug = $tr?->slug ?? $cat->slug;
                $cat->children->each(function ($child) {
                    $tr = $child->translations->first();
                    $child->name = $tr?->name ?? $child->name;
                    $child->slug = $tr?->slug ?? $child->slug;
                });
                $cat->total_blog_count = $cat->root_count + $cat->children->sum('blog_count');
            });

        // ── Blog posts query ───────────────────────────────────────────────────
        $blogs = $this->blogPostRepository->paginateIndexDecorated($locale, $search, array_values($categoryFilter), 12);

        // Resolve display name for the active category filter label
        $categoryName = null;
        if ($categoryFilter) {
            foreach ($blogCategories as $root) {
                if (in_array($root->slug, $categoryFilter)) {
                    $categoryName = $root->name;
                    break;
                }
                $matched = $root->children->first(fn ($c) => in_array($c->slug, $categoryFilter));
                if ($matched) {
                    $categoryName = $matched->name;
                    break;
                }
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
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog'],
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
        $translation = BlogCategoryTranslation::where('locale', $locale)
            ->where('slug', $slug)
            ->with('blogCategory')
            ->first();

        if (! $translation) {
            $viTranslation = BlogCategoryTranslation::where('locale', config('app.fallback_locale'))
                ->where('slug', $slug)
                ->first();

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

        // ── Subcategory pills ──────────────────────────────────────────────────
        $blogCategory->loadMissing([
            'children' => fn ($q) => $q->active()
                ->withCount(['posts as blog_count' => fn ($q) => $q->published()])
                ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
                ->orderBy('sort_order'),
        ]);
        $blogCategory->children->each(function ($child) {
            $tr = $child->translations->first();
            $child->name = $tr?->name ?? $child->name;
            $child->slug = $tr?->slug ?? $child->slug;
        });

        // ── Posts query (this category + direct children) ──────────────────────
        $categoryIds = collect([$blogCategory->id])
            ->merge($blogCategory->children->pluck('id'))
            ->unique();

        $blogs = $this->blogPostRepository->paginateByCategoryIdsDecorated($locale, $categoryIds->all(), 12);

        // Self-referencing canonical for pagination — same rule as blog index()/PLP.
        $canonicalUrl = $blogs->currentPage() > 1
            ? route($locale.'.blog.category', $translation->slug).'?page='.$blogs->currentPage()
            : route($locale.'.blog.category', $translation->slug);

        $blogCategory->loadMissing('seoMetas');

        // ── Rich content — admin-managed (BlogCategoryResource RichEditor).
        // Stored as plain HTML (rich_content has no 'array' cast, unlike product
        // CategoryTranslation), so no Tiptap JSON→HTML conversion needed here.
        $richContentHtml = filled($translation->rich_content) ? $translation->rich_content : null;

        $geoProfile = GeoEntityProfile::where('model_type', 'blog_category')
            ->where('model_id', (string) $blogCategory->id)
            ->where('locale', $locale)
            ->first();
        $faqItems = $geoProfile?->faq ?? [];
        $faqs = array_values(array_filter(array_map(
            fn ($f) => (trim($f['question'] ?? '') && trim($f['answer'] ?? '')) ? $f : null,
            $faqItems
        )));

        // ── Breadcrumb (visible) — mirrors JsonldService::buildBlogCategoryBreadcrumb()
        // so the visible trail never disagrees with the BreadcrumbList JSON-LD.
        $breadcrumbItems = [
            ['label' => $locale === 'vi' ? 'Trang chủ' : 'Home', 'url' => route($locale.'.index')],
            ['label' => 'Blog', 'url' => route($locale.'.blog.index')],
        ];
        $parentTranslation = $blogCategory->parent?->translation($locale);
        if ($parentTranslation) {
            $breadcrumbItems[] = [
                'label' => $parentTranslation->name,
                'url' => LocaleUrl::for('blog_category', $parentTranslation->slug, $locale),
            ];
        }
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
        $translation = BlogPostTranslation::where('locale', $locale)
            ->where('slug', $slug)
            ->with('blogPost')
            ->first();

        if (! $translation) {
            $viTranslation = BlogPostTranslation::where('slug', $slug)
                ->whereIn('locale', config('app.supported_locales'))
                ->where('locale', '!=', $locale)
                ->with(['blogPost.blogCategory.translations'])
                ->first();

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
        $geoProfile = GeoEntityProfile::where('model_type', 'blog_post')
            ->where('model_id', (string) $post->id)
            ->where('locale', $locale)
            ->first();
        $faqItems = $geoProfile?->faq
            ?? ($locale === 'vi' ? ($post->faq_items_vi ?? []) : ($post->faq_items_en ?? []));
        $faqs = array_values(array_filter(array_map(
            fn ($f) => (trim($f['question'] ?? '') && trim($f['answer'] ?? '')) ? $f : null,
            $faqItems
        )));

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

        $readMins = max(1, (int) ceil(str_word_count(strip_tags($bodyHtml)) / 200));

        // ── Breadcrumb (visible) — mirrors JsonldService::buildBlogPostBreadcrumb()
        // so the visible trail never disagrees with the BreadcrumbList JSON-LD.
        $breadcrumbItems = [
            ['label' => $locale === 'vi' ? 'Trang chủ' : 'Home', 'url' => route($locale.'.index')],
            ['label' => 'Blog', 'url' => route($locale.'.blog.index')],
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
            'published_at' => $post->published_at,
            'updated_at' => $post->updated_at,
            'formatted_published_date' => $post->published_at?->translatedFormat('d M, Y'),
            'reading_time' => $locale === 'vi' ? "{$readMins} phút đọc" : "{$readMins} min read",
            'author' => $post->author,
            'tags' => $post->tags->pluck('name')->all(),
            'faqs' => $faqs,
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
        $categories = BlogCategory::active()
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->orderBy('sort_order')
            ->get()
            ->map(function ($cat) {
                $tr = $cat->translations->first();

                return (object) [
                    'name' => $tr?->name ?? $cat->name,
                    'slug' => $tr?->slug ?? $cat->slug,
                ];
            });

        // ── Sidebar: latest posts (excl. current) ─────────────────────────────
        $sidebarPostsBase = $this->blogPostRepository->latestExcludingDecorated($locale, $post->id, 13);

        $latestPosts = $sidebarPostsBase->take(3);
        $morePostsList = $sidebarPostsBase->slice(3);

        // ── Related posts (same category, excl. current) ──────────────────────
        $relatedPosts = $post->blog_category_id
            ? $this->blogPostRepository->relatedDecorated($locale, $post->blog_category_id, $post->id, 4)
            : collect();

        // ── Sidebar: tags ─────────────────────────────────────────────────────
        $allTags = BlogTag::whereHas('posts', fn ($q) => $q->published())->pluck('name');

        view()->share('alternateUrls', $alternateUrls);

        return view('pages.blog.show', compact(
            'blog', 'alternateUrls', 'seoMeta', 'jsonldSchemas', 'locale',
            'fallbackTitle', 'fallbackDescription', 'fallbackImage', 'ogType', 'articleMeta',
            'categories', 'latestPosts', 'morePostsList', 'relatedPosts', 'allTags',
            'breadcrumbItems'
        ) + ['noScrollSmoother' => true]);
    }
}
