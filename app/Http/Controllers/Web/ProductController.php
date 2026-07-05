<?php

namespace App\Http\Controllers\Web;

use App\Enums\FilterGroupType;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\BusinessProfile;
use App\Models\FilterGroup;
use App\Models\ProductTranslation;
use App\Models\Setting;
use App\Repositories\Eloquent\BlogPostRepository;
use App\Services\Catalog\ProductSearchService;
use App\Services\Seo\JsonldService;
use App\Services\Seo\SeoService;
use App\Support\LocaleUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductSearchService $productSearchService,
    ) {}

    public function index(string $locale): View
    {
        $keyword = (string) request()->query('q', '');
        $brandSlug = (string) request()->query('brand', '');

        // Load filter groups with their active values
        $filterGroups = FilterGroup::active()
            ->with('activeValues')
            ->orderBy('sort_order')
            ->get();

        // Parse active filter values from query: ?protocol=knx,dali-2&voltage=24v-dc
        // [group_slug => [value_slug, ...]]
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
            categoryId: null, perPage: 24, minPrice: $minPrice, maxPrice: $maxPrice,
        );

        $priceBounds = $this->productSearchService->getPriceBounds(categoryId: null);

        $brands = Brand::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        view()->share('alternateUrls', [
            'vi' => route('vi.product.shop'),
            'en' => route('en.product.shop'),
        ]);

        $ogRaw = Setting::get('default_og_image');
        $fallbackImage = $ogRaw ? (str_starts_with($ogRaw, 'http') ? $ogRaw : asset('storage/'.ltrim($ogRaw, '/'))) : null;

        // Shop page hero — admin-managed via Filament ShopSetting (extra['shop']).
        $shop = (array) (BusinessProfile::instance()->extra['shop'] ?? []);
        $isEn = $locale === 'en';
        $heroImageRaw = $shop['hero_image'] ?? null;
        $shopHeroTitle = ($isEn ? ($shop['h1_en'] ?? null) : ($shop['h1'] ?? null))
            ?: ($isEn ? 'All Products' : 'Tất cả sản phẩm');
        $shopHero = [
            'title' => $shopHeroTitle,
            'intro' => $isEn ? ($shop['intro_en'] ?? null) : ($shop['intro'] ?? null),
            'image_url' => $heroImageRaw
                ? (str_starts_with($heroImageRaw, 'http') ? $heroImageRaw : asset('storage/'.ltrim($heroImageRaw, '/')))
                : null,
            // Alt tự điền (BlogSetting/ShopSetting), fallback H1 + tên shop.
            'image_alt' => ($isEn ? ($shop['hero_alt_en'] ?? null) : ($shop['hero_alt'] ?? null))
                ?: $shopHeroTitle.' - '.Setting::get('site_name'),
        ];

        // Không kèm hậu tố "- CacyLinen" - layout blade append một lần duy nhất,
        // để suffix ở đây nữa là tab title thành "... - CacyLinen - CacyLinen".
        $fallbackTitle = $locale === 'vi'
            ? (Setting::get('product_catalog_title') ?: 'Tất cả sản phẩm')
            : (Setting::get('product_catalog_title_en') ?: 'All Products');
        $fallbackDescription = $locale === 'vi'
            ? (Setting::get('product_catalog_description') ?: 'Khám phá toàn bộ bộ sưu tập thời trang linen tối giản, bền vững của CacyLinen.')
            : (Setting::get('product_catalog_description_en') ?: 'Browse the full CacyLinen collection of minimalist, sustainable linen fashion.');

        // Canonical: bỏ toàn bộ query filter (mau-sac, min_price, q, brand...) —
        // n! tổ hợp filter là nội dung trùng. Giữ page để paginated pages tự canonical.
        $shopUrl = route($locale.'.product.shop');
        $canonicalUrl = $products->currentPage() > 1
            ? $shopUrl.'?page='.$products->currentPage()
            : $shopUrl;

        // CollectionPage + ItemList build runtime — thay đổi theo trang/filter nên
        // không đi qua pipeline jsonld_schemas DB như PDP/category.
        $positionOffset = ($products->currentPage() - 1) * $products->perPage();
        $jsonldSchemas = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => $fallbackTitle,
                'description' => $fallbackDescription,
                'url' => $canonicalUrl,
                'inLanguage' => $locale,
                'mainEntity' => [
                    '@type' => 'ItemList',
                    'numberOfItems' => $products->total(),
                    'itemListElement' => $products->getCollection()->values()->map(fn ($t, $i) => [
                        '@type' => 'ListItem',
                        'position' => $positionOffset + $i + 1,
                        'url' => route($locale.'.product.show', $t->slug),
                        'name' => $t->name,
                    ])->all(),
                ],
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => $locale === 'vi' ? 'Trang chủ' : 'Home', 'item' => route($locale.'.index')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $locale === 'vi' ? 'Cửa hàng' : 'Shop'],
                ],
            ],
        ];

        return view('pages.product.index', compact(
            'locale', 'products', 'filterGroups', 'brands',
            'activeValueSlugs', 'brandSlug', 'keyword', 'priceBounds', 'minPrice', 'maxPrice',
            'shopHero', 'canonicalUrl', 'jsonldSchemas'
        ) + [
            'seoMeta' => null,
            'fallbackTitle' => $fallbackTitle,
            'fallbackDescription' => $fallbackDescription,
            'fallbackImage' => $fallbackImage,
            'ogType' => 'website',
        ]);
    }

    public function autocomplete(string $locale): JsonResponse
    {
        $q = trim(request()->query('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['products' => [], 'total' => 0, 'hasMore' => false]);
        }

        $query = ProductTranslation::where('locale', $locale)
            ->where(fn ($w) => $w
                ->where('name', 'ilike', '%'.$q.'%')
                ->orWhere('slug', 'ilike', '%'.$q.'%')
            )
            ->whereHas('product', fn ($p) => $p->where('is_active', true))
            ->with(['product.thumbnail', 'product.brand'])
            ->limit(8);

        $translations = $query->get();
        $total = ProductTranslation::where('locale', $locale)
            ->where(fn ($w) => $w
                ->where('name', 'ilike', '%'.$q.'%')
                ->orWhere('slug', 'ilike', '%'.$q.'%')
            )
            ->whereHas('product', fn ($p) => $p->where('is_active', true))
            ->count();

        $products = $translations->map(fn ($t) => [
            'name' => $t->name,
            'url' => route($locale.'.product.show', $t->slug),
            'image_url' => $t->product->thumbnail?->url ?? null,
            'brand' => $t->product->brand?->name,
            'sku' => $t->product->sku ?? null,
        ]);

        return response()->json([
            'products' => $products,
            'total' => $total,
            'hasMore' => $total > 8,
        ]);
    }

    public function show(string $locale, string $slug): View|RedirectResponse
    {
        $translation = ProductTranslation::where('locale', $locale)
            ->where('slug', $slug)
            ->with([
                'product.categories.translations',
                'product.thumbnail',
                'product.images',
                'product.brand',
                'product.manufacturer',
                'product.attributes',
                'product.videos',
                'product.variantDimensionValues.group',
                'product.variants' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
                'product.variants.optionValues.group',
                'product.variants.image',
            ])
            ->first();

        if (! $translation) {
            $altTranslation = ProductTranslation::where('slug', $slug)
                ->whereIn('locale', config('app.supported_locales'))
                ->where('locale', '!=', $locale)
                ->first();

            if ($altTranslation) {
                return redirect(
                    LocaleUrl::for('product', $altTranslation->slug, $altTranslation->locale),
                    302
                );
            }

            abort(404);
        }

        $product = $translation->product;
        if (! $product || ! $product->is_active) {
            abort(404);
        }

        // Related products: same category, same locale, exclude self, limit 8
        $firstCategory = $product->categories->first();
        $relatedProducts = collect();
        if ($firstCategory) {
            $relatedIds = ProductTranslation::where('locale', $locale)
                ->where('id', '!=', $translation->id)
                ->whereHas('product', fn ($q) => $q->active()
                    ->whereHas('categories', fn ($q2) => $q2->where('categories.id', $firstCategory->id))
                )
                ->with(['product.images'])
                ->limit(8)
                ->get();
            $relatedProducts = $relatedIds;
        }

        $alternateUrls = app(SeoService::class)->alternateUrls($product);
        $seoMeta = $product->seoMeta($locale);
        $jsonldSchemas = app(JsonldService::class)->getActiveSchemas($product, $locale)
            ->pluck('payload')
            ->toArray();

        // Strip price fields at read-time so the JSON-LD always reflects
        // show_price instantly — regardless of whether the queue job has run.
        if (! $product->show_price) {
            $jsonldSchemas = array_map(function (array $schema) {
                if (($schema['@type'] ?? '') !== 'Product' || ! isset($schema['offers'])) {
                    return $schema;
                }
                $offers = $schema['offers'];
                unset($offers['price'], $offers['priceCurrency'], $offers['lowPrice'], $offers['highPrice']);
                if (isset($offers['offers'])) {
                    $offers['offers'] = array_map(
                        fn ($o) => array_diff_key($o, array_flip(['price', 'priceCurrency'])),
                        $offers['offers']
                    );
                }
                $schema['offers'] = $offers;

                return $schema;
            }, $jsonldSchemas);
        }
        $fallbackTitle = $translation->name;
        $fallbackDescription = $translation->short_description ?? '';
        $fallbackImage = $product->thumbnail
            ? url($product->thumbnail->url)
            : null;
        $ogType = 'product';

        view()->share('alternateUrls', $alternateUrls);

        // Build variants JSON for frontend selector
        $variantsData = $product->variants->map(fn ($v) => [
            'id' => $v->id,
            'sku' => $v->sku,
            'price' => (float) ($v->sale_price && $v->sale_price < $v->price ? $v->sale_price : $v->price),
            'base_price' => (float) $v->price,
            'sale_price' => $v->sale_price ? (float) $v->sale_price : null,
            'stock' => $v->stock_quantity,
            'image_url' => $v->image?->url,
            'options' => $v->optionValues->map(fn ($ov) => [
                'type_id' => $ov->filter_group_id,
                'value_id' => $ov->id,
                'value' => $ov->name,
            ])->values()->all(),
        ])->values()->all();

        $optionTypesData = $product->variantDimensionValues
            ->groupBy('filter_group_id')
            ->map(fn ($values, $groupId) => [
                'id' => $groupId,
                'name' => $values->first()->group->name,
                'is_color' => $values->first()->group->type === FilterGroupType::Color,
                'values' => $values->map(fn ($v) => [
                    'id' => $v->id,
                    'value' => $v->name,
                    'color_hex' => $v->color_hex,
                ])->values()->all(),
            ])->values()->all();

        // Journal section cuối PDP — 4 bài mới nhất, cùng shape với homepage.
        $latestBlogs = app(BlogPostRepository::class)->latestDecorated($locale, 4);

        return view('pages.product.show', compact(
            'product', 'translation', 'alternateUrls', 'seoMeta', 'jsonldSchemas', 'locale',
            'fallbackTitle', 'fallbackDescription', 'fallbackImage', 'ogType',
            'relatedProducts', 'variantsData', 'optionTypesData', 'latestBlogs'
        ));
    }
}
