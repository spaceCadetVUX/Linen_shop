<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Enums\BlogPostStatus;
use App\Models\BlogPostTranslation;
use App\Models\BusinessProfile;
use App\Models\Setting;
use App\Services\Seo\BusinessJsonldService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __construct(private BusinessJsonldService $jsonld) {}

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
                ?: ($tagline ?: 'LINNÉ — Thời trang linen tối giản, bền vững.'))
            : (Setting::get('meta_description_en')
                ?: (Setting::get('meta_description')
                ?: 'LINNÉ — Minimalist, sustainable linen fashion.'));

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

        $editorialItems = array_map(function (int $i) use ($landing, $isEn, $imgUrl): array {
            $defaults = [
                ['name' => 'Áo linen',    'name_en' => 'Linen Tops',    'cta' => 'Khám phá', 'cta_en' => 'Explore', 'url' => '/shop/ao-linen',  'fallback_class' => 'edit-grid-img--linen'],
                ['name' => 'Quần & Váy',  'name_en' => 'Pants & Skirts','cta' => 'Khám phá', 'cta_en' => 'Explore', 'url' => '/shop/quan-vay',  'fallback_class' => 'edit-grid-img--pants'],
                ['name' => 'Bộ set linen','name_en' => 'Linen Sets',    'cta' => 'Khám phá', 'cta_en' => 'Explore', 'url' => '/shop/set-linen', 'fallback_class' => 'edit-grid-img--set'],
            ][$i];
            $raw = $landing["eg{$i}_image"] ?? null;
            return [
                'image_url'      => $imgUrl($raw),
                'fallback_class' => $raw ? null : $defaults['fallback_class'],
                'name'           => ($isEn ? ($landing["eg{$i}_name_en"] ?? null) : null) ?? $landing["eg{$i}_name"] ?? $defaults[$isEn ? 'name_en' : 'name'],
                'cta'            => ($isEn ? ($landing["eg{$i}_cta_en"]  ?? null) : null) ?? $landing["eg{$i}_cta"]  ?? $defaults[$isEn ? 'cta_en'  : 'cta'],
                'url'            => $landing["eg{$i}_url"] ?? $defaults['url'],
            ];
        }, [0, 1, 2]);
        $heroEyebrow   = ($isEn ? ($landing['hero_eyebrow_en']    ?? null) : null) ?? $landing['hero_eyebrow']    ?? 'Mới ra mắt';
        $heroHeadline  = ($isEn ? ($landing['hero_headline_en']   ?? null) : null) ?? $landing['hero_headline']   ?? 'Bộ sưu tập Thu 2026';
        $heroCtaLabel  = ($isEn ? ($landing['hero_cta_label_en']  ?? null) : null) ?? $landing['hero_cta_label']  ?? 'Khám phá lookbook';
        $heroCtaUrl    = $landing['hero_cta_url']    ?? '/collections/lookbook';
        $heroCtaLabel2 = ($isEn ? ($landing['hero_cta2_label_en'] ?? null) : null) ?? $landing['hero_cta2_label'] ?? 'Khám phá thêm';
        $heroCtaUrl2   = $landing['hero_cta2_url']   ?? '/collections/new';

        $seoMeta = null;
        $ogType  = 'website';

        $latestBlogs = BlogPostTranslation::where('blog_post_translations.locale', $locale)
            ->join('blog_posts', 'blog_posts.id', '=', 'blog_post_translations.blog_post_id')
            ->where('blog_posts.status', BlogPostStatus::Published)
            ->where('blog_posts.published_at', '<=', now())
            ->whereNull('blog_posts.deleted_at')
            ->select('blog_post_translations.*')
            ->with(['blogPost.blogCategory.translations' => fn ($q) => $q->where('locale', $locale)])
            ->orderByDesc('blog_posts.published_at')
            ->limit(3)
            ->get()
            ->map(function ($tr) {
                $p    = $tr->blogPost;
                $cTr  = $p?->blogCategory?->translations->first();
                $img  = $p?->featured_image;
                return (object) [
                    'title'                  => $tr->title,
                    'slug'                   => $tr->slug,
                    'excerpt'                => $tr->excerpt,
                    'category'               => $cTr?->name ?? $p?->blogCategory?->name,
                    'category_slug'          => $cTr?->slug ?? $p?->blogCategory?->slug,
                    'featured_image'         => $img ? 'storage/' . ltrim($img, '/') : null,
                    'formatted_published_date' => $p?->published_at?->translatedFormat('d M, Y'),
                ];
            });

        return view('pages.home.index', compact(
            'locale', 'businessSchemas', 'faqItems', 'latestBlogs',
            'seoMeta', 'fallbackTitle', 'fallbackDescription', 'fallbackImage', 'ogType',
            'heroImageUrl', 'heroEyebrow', 'heroHeadline', 'heroCtaLabel', 'heroCtaUrl', 'heroCtaLabel2', 'heroCtaUrl2',
            'editorialItems'
        ));
    }
}
