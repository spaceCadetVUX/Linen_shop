<?php

namespace App\Http\Controllers\Web;

use App\Enums\HomeEditorialScope;
use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use App\Models\Category;
use App\Models\ProductTranslation;
use App\Models\Setting;
use App\Repositories\Eloquent\BlogPostRepository;
use App\Services\Category\CategoryService;
use App\Services\Seo\BusinessJsonldService;
use App\Support\LocaleUrl;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __construct(
        private BusinessJsonldService $jsonld,
        private CategoryService $categoryService,
    ) {}

    public function index(string $locale): View
    {
        view()->share('alternateUrls', [
            'vi' => route('vi.index'),
            'en' => route('en.index'),
        ]);

        $businessSchemas = $this->jsonld->getSchemas($locale);
        $profile         = BusinessProfile::instance();

        // FAQ items for the visible FAQ section on the page
        $faqKey   = $locale === 'en' ? 'faq_en' : 'faq';
        $faqItems = collect((array) ($profile->extra[$faqKey] ?? []))
            ->map(fn ($f) => ['q' => $f['question'] ?? '', 'a' => $f['answer'] ?? ''])
            ->filter(fn ($f) => filled($f['q']))
            ->values()
            ->all();

        // ── SEO fallbacks ──────────────────────────────────────────────────────
        $siteName    = $profile->name ?: config('app.name');
        $tagline     = $profile->tagline ?? '';

        $enTagline = Setting::get('site_tagline_en') ?: 'Minimalist, Sustainable Linen Fashion';
        $fallbackTitle = $locale === 'vi'
            ? (Setting::get('home_title') ?: ($tagline ?: $siteName))
            : (Setting::get('home_title_en') ?: $enTagline);

        $fallbackDescription = $locale === 'vi'
            ? (Setting::get('meta_description')
                ?: ($tagline ?: 'CacyLinen - Thời trang linen tối giản, bền vững.'))
            : (Setting::get('meta_description_en')
                ?: (Setting::get('meta_description')
                ?: 'CacyLinen - Minimalist, sustainable linen fashion.'));

        $ogRaw = $profile->extra['og_image'] ?? Setting::get('default_og_image');
        $fallbackImage = $ogRaw
            ? (str_starts_with($ogRaw, 'http') ? $ogRaw : asset('storage/' . ltrim($ogRaw, '/')))
            : null;

        $landing      = (array) ($profile->extra['landing'] ?? []);
        $heroImageRaw = $landing['hero_image'] ?? null;
        $heroImageUrl = $heroImageRaw
            ? (str_starts_with($heroImageRaw, 'http') ? $heroImageRaw : asset('storage/' . ltrim($heroImageRaw, '/')))
            : null;

        $isEn   = $locale === 'en';
        $imgUrl = fn(?string $path) => $path
            ? (str_starts_with($path, 'http') ? $path : asset('storage/' . ltrim($path, '/')))
            : null;

        // Editorial grid — active categories (Category::is_active, sorted by sort_order),
        // reuses CategoryService's cached tree (already loaded for the mega menu).
        // Scope configurable via LandingSetup (extra['landing']['editorial_scope']).
        $editorialScope = HomeEditorialScope::tryFrom((string) ($landing['editorial_scope'] ?? ''))
            ?? HomeEditorialScope::Parents;
        $editorialTree  = $this->categoryService->getTree();
        $editorialCategories = match ($editorialScope) {
            HomeEditorialScope::All      => $editorialTree->flatMap(fn (Category $root) => collect([$root])->concat($root->children)),
            HomeEditorialScope::Children => $editorialTree->flatMap(fn (Category $root) => $root->children),
            HomeEditorialScope::Parents  => $editorialTree,
        };

        $editorialItems = $editorialCategories
            ->map(function (Category $category) use ($locale, $isEn, $imgUrl): array {
                $translation = $category->translation($locale);

                return [
                    'image_url'      => $imgUrl($category->image_path),
                    'fallback_class' => $category->image_path ? null : 'edit-grid-img--default',
                    'name'           => $translation?->name ?? $category->name,
                    'cta'            => $isEn ? 'Explore' : 'Khám phá',
                    'url'            => LocaleUrl::for('category', $translation?->slug ?? $category->slug, $locale),
                ];
            })
            ->values()
            ->all();
        $heroEyebrow   = ($isEn ? ($landing['hero_eyebrow_en']    ?? null) : null) ?? $landing['hero_eyebrow']    ?? 'Mới ra mắt';
        $heroHeadline  = ($isEn ? ($landing['hero_headline_en']   ?? null) : null) ?? $landing['hero_headline']   ?? 'Bộ sưu tập Thu 2026';
        $heroCtaLabel  = ($isEn ? ($landing['hero_cta_label_en']  ?? null) : null) ?? $landing['hero_cta_label']  ?? 'Khám phá lookbook';
        $heroCtaUrl    = $landing['hero_cta_url']    ?? '/collections/lookbook';
        $heroCtaLabel2 = ($isEn ? ($landing['hero_cta2_label_en'] ?? null) : null) ?? $landing['hero_cta2_label'] ?? 'Khám phá thêm';
        $heroCtaUrl2   = $landing['hero_cta2_url']   ?? '/collections/new';

        $seoMeta = null;
        $ogType  = 'website';

        // Shop grid — sản phẩm mới nhất, bật/tắt + tiêu đề từ LandingSetup
        // (extra['landing']['featured_enabled'/'featured_title']).
        $featuredEnabled = (bool) ($landing['featured_enabled'] ?? true);
        $featuredTitle   = ($landing['featured_title'] ?? null) ?: ($isEn ? 'Featured products' : 'Sản phẩm nổi bật');
        $featuredProducts = collect();
        if ($featuredEnabled) {
            $featuredProducts = ProductTranslation::where('product_translations.locale', $locale)
                ->join('products', 'products.id', '=', 'product_translations.product_id')
                ->where('products.is_active', true)
                ->whereNull('products.deleted_at')
                ->select('product_translations.*')
                ->with(['product.images'])
                ->orderByDesc('products.created_at')
                ->limit(8)
                ->get();
        }

        // journal-grid desktop là 4 cột
        $latestBlogs = app(BlogPostRepository::class)->latestDecorated($locale, 4);

        return view('pages.home.index', compact(
            'locale', 'businessSchemas', 'faqItems', 'latestBlogs',
            'seoMeta', 'fallbackTitle', 'fallbackDescription', 'fallbackImage', 'ogType',
            'heroImageUrl', 'heroEyebrow', 'heroHeadline', 'heroCtaLabel', 'heroCtaUrl', 'heroCtaLabel2', 'heroCtaUrl2',
            'editorialItems', 'featuredEnabled', 'featuredTitle', 'featuredProducts'
        ));
    }
}
