<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SizeGuide;
use App\Services\Seo\JsonldService;
use Illuminate\Contracts\View\View;

class SizeGuideController extends Controller
{
    public function index(string $locale): View
    {
        $guides = SizeGuide::active()
            ->with('translations')
            ->orderBy('sort_order')
            ->orderBy('key')
            ->get()
            ->map(fn (SizeGuide $guide) => [
                'key' => $guide->key,
                'name' => $guide->translation($locale)?->name,
                'body' => $guide->translation($locale)?->body,
            ])
            ->filter(fn (array $guide) => filled($guide['name']) && filled($guide['body']))
            ->values();

        $alternateUrls = [
            'vi' => route('vi.size-guide'),
            'en' => route('en.size-guide'),
        ];

        $fallbackTitle = $locale === 'vi' ? 'Hướng dẫn chọn size' : 'Size Guide';
        $fallbackDescription = $locale === 'vi'
            ? 'Bảng size và hướng dẫn đo chi tiết cho các sản phẩm linen của CacyLinen.'
            : 'Detailed size charts and measuring guide for CacyLinen linen garments.';

        $jsonldSchemas = [
            app(JsonldService::class)->buildBreadcrumb([
                ['name' => $locale === 'vi' ? 'Trang chủ' : 'Home', 'url' => route($locale.'.index')],
                ['name' => $fallbackTitle, 'url' => url()->current()],
            ]),
        ];

        view()->share('alternateUrls', $alternateUrls);

        return view('pages.size-guide', compact(
            'guides', 'alternateUrls', 'jsonldSchemas', 'locale',
            'fallbackTitle', 'fallbackDescription',
        ) + [
            'seoMeta' => null,
            'fallbackImage' => null,
            'ogType' => 'website',
        ]);
    }
}
