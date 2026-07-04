<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CategoryTranslation;
use App\Models\FilterGroup;
use App\Models\Setting;
use App\Services\Catalog\ProductSearchService;
use App\Services\Seo\JsonldService;
use App\Services\Seo\SeoService;
use App\Support\LocaleUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Tiptap\Editor;
use Tiptap\Extensions\StarterKit;
use Tiptap\Nodes\Image as TiptapImage;
use Tiptap\Nodes\Table;
use Tiptap\Nodes\TableCell;
use Tiptap\Nodes\TableHeader;
use Tiptap\Nodes\TableRow;

class CategoryController extends Controller
{
    public function __construct(
        private readonly ProductSearchService $productSearchService,
    ) {}

    public function index(string $locale): View
    {
        view()->share('alternateUrls', [
            'vi' => route('vi.product.category'),
            'en' => route('en.product.category'),
        ]);

        $categories = CategoryTranslation::where('locale', $locale)
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->with(['category' => fn ($q) => $q->withCount([
                'products as product_count' => fn ($q2) => $q2->where('is_active', true),
            ])])
            ->orderBy('name')
            ->get()
            ->filter(fn ($tr) => $tr->category !== null);

        $fallbackTitle = $locale === 'vi'
            ? (Setting::get('category_index_title') ?: 'Danh mục sản phẩm')
            : (Setting::get('category_index_title_en') ?: 'Product Categories');
        $fallbackDescription = $locale === 'vi'
            ? (Setting::get('category_index_description') ?: 'Khám phá tất cả danh mục sản phẩm của CacyLinen.')
            : (Setting::get('category_index_description_en') ?: 'Browse all CacyLinen product categories.');

        return view('pages.category.index', compact(
            'locale', 'categories', 'fallbackTitle', 'fallbackDescription'
        ));
    }

    public function show(string $locale, string $slug): View|RedirectResponse
    {
        $translation = CategoryTranslation::where('locale', $locale)
            ->where('slug', $slug)
            ->with('category')
            ->first();

        if (! $translation) {
            $alt = CategoryTranslation::where('slug', $slug)
                ->whereIn('locale', config('app.supported_locales'))
                ->where('locale', '!=', $locale)
                ->first();

            if ($alt) {
                return redirect(LocaleUrl::for('category', $alt->slug, $alt->locale), 302);
            }

            abort(404);
        }

        $category = $translation->category;
        if (! $category || ! $category->isPubliclyVisible()) {
            abort(404);
        }

        // ── Products in this category ─────────────────────────────────────────
        $keyword = (string) request()->query('q', '');
        $brandSlug = (string) request()->query('brand', '');

        $filterGroups = FilterGroup::active()
            ->with('activeValues')
            ->orderBy('sort_order')
            ->get();

        $activeValueSlugs = [];
        foreach ($filterGroups as $group) {
            $raw = (string) request()->query($group->slug, '');
            if ($raw) {
                $slugs = array_values(array_filter(array_map('trim', explode(',', $raw))));
                if ($slugs) {
                    $activeValueSlugs[$group->slug] = $slugs;
                }
            }
        }

        $minPrice = request()->query('min_price');
        $maxPrice = request()->query('max_price');
        $minPrice = is_numeric($minPrice) ? (float) $minPrice : null;
        $maxPrice = is_numeric($maxPrice) ? (float) $maxPrice : null;

        $products = $this->productSearchService->search(
            $locale, $keyword, $filterGroups, $activeValueSlugs, $brandSlug,
            categoryId: (string) $category->id, perPage: 24, minPrice: $minPrice, maxPrice: $maxPrice,
        );

        $priceBounds = $this->productSearchService->getPriceBounds(categoryId: (string) $category->id);

        $brands = Brand::active()->orderBy('sort_order')->orderBy('name')->get();

        // ── FAQ ───────────────────────────────────────────────────────────────
        $faqField = 'faq_items_'.$locale;
        $faqItems = is_array($category->$faqField ?? null) ? $category->$faqField : [];
        $faqEntities = array_filter(array_map(
            fn ($f) => (trim($f['question'] ?? '') && trim($f['answer'] ?? '')) ? $f : null,
            $faqItems
        ));

        // ── Rich content HTML ─────────────────────────────────────────────────
        $richContentHtml = null;
        $rawContent = $translation->rich_content;
        if (! empty($rawContent) && is_array($rawContent)) {
            try {
                $richContentHtml = (new Editor(['extensions' => [
                    new StarterKit,
                    new TiptapImage,
                    new Table,
                    new TableRow,
                    new TableHeader,
                    new TableCell,
                ]]))->setContent($rawContent)->getHTML();
                // Strip empty paragraphs only
                if (trim(strip_tags($richContentHtml)) === '') {
                    $richContentHtml = null;
                }
            } catch (\Throwable) {
                $richContentHtml = null;
            }
        }

        // ── SEO ───────────────────────────────────────────────────────────────
        $alternateUrls = app(SeoService::class)->alternateUrls($category);
        $category->loadMissing('seoMetas');
        $seoMeta = $category->seoMeta($locale);
        $jsonldSchemas = app(JsonldService::class)->getActiveSchemas($category, $locale)
            ->pluck('payload')->toArray();
        $fallbackTitle = $translation->name;
        $fallbackDescription = $translation->description ?? '';
        $fallbackImage = $category->image_path
            ? asset('storage/'.$category->image_path)
            : null;
        $ogType = 'website';
        $currentUrl = request()->fullUrl();

        // Canonical: bỏ query filter, giữ page (cùng rule với PLP).
        $canonicalUrl = $products->currentPage() > 1
            ? url()->current().'?page='.$products->currentPage()
            : url()->current();

        // ItemList của sản phẩm trên trang — runtime (đổi theo trang/filter),
        // bổ sung cạnh CollectionPage/BreadcrumbList tĩnh từ pipeline DB.
        $positionOffset = ($products->currentPage() - 1) * $products->perPage();
        $jsonldSchemas[] = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $fallbackTitle,
            'numberOfItems' => $products->total(),
            'itemListElement' => $products->getCollection()->values()->map(fn ($t, $i) => [
                '@type' => 'ListItem',
                'position' => $positionOffset + $i + 1,
                'url' => route($locale.'.product.show', $t->slug),
                'name' => $t->name,
            ])->all(),
        ];

        view()->share('alternateUrls', $alternateUrls);

        return view('pages.category.show', compact(
            'category', 'translation', 'locale',
            'products', 'filterGroups', 'brands', 'activeValueSlugs', 'brandSlug', 'keyword',
            'priceBounds', 'minPrice', 'maxPrice',
            'faqItems', 'faqEntities',
            'richContentHtml',
            'alternateUrls', 'seoMeta', 'jsonldSchemas', 'canonicalUrl',
            'fallbackTitle', 'fallbackDescription', 'fallbackImage', 'ogType', 'currentUrl'
        ));
    }
}
