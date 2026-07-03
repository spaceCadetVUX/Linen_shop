<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Contracts\View\View;

class AboutController extends Controller
{
    public function show(string $locale): View
    {
        $seoTitle = $locale === 'vi'
            ? (Setting::get('about_title') ?: 'Về LINNÉ — Thời trang tối giản, vải tự nhiên')
            : (Setting::get('about_title_en') ?: 'About LINNÉ — Minimalist fashion, natural fabrics');

        $seoDescription = $locale === 'vi'
            ? (Setting::get('about_meta_description') ?: 'LINNÉ được tạo ra cho những người tin rằng vẻ đẹp thực sự đến từ sự tối giản — chất liệu thuần khiết, đường cắt may lâu bền, lựa chọn có ý thức.')
            : (Setting::get('about_meta_description_en') ?: 'LINNÉ is made for people who believe true beauty comes from simplicity — pure fabrics, lasting construction, conscious choices.');

        $data = [
            'seoTitle'       => $seoTitle,
            'seoDescription' => $seoDescription,
            'description'    => Setting::get('about_description') ?? '',
            'foundedYear'    => Setting::get('company_founded_year'),
            'certifications' => array_values(array_filter(
                explode("\n", Setting::get('company_certifications') ?? '')
            )),
            'ogImage'        => Setting::get('about_og_image'),
        ];

        view()->share('alternateUrls', [
            'vi' => route('vi.about'),
            'en' => route('en.about'),
        ]);

        $ogRaw         = $data['ogImage'] ?? Setting::get('default_og_image');
        $fallbackImage = $ogRaw
            ? (str_starts_with($ogRaw, 'http') ? $ogRaw : asset('storage/' . ltrim($ogRaw, '/')))
            : null;

        return view('pages.static.about', compact('data', 'locale') + [
            'seoMeta'             => null,
            'fallbackTitle'       => $data['seoTitle'],
            'fallbackDescription' => $data['seoDescription'] ?: ($data['description'] ?? ''),
            'fallbackImage'       => $fallbackImage,
            'ogType'              => 'website',
            'jsonldSchemas'       => [],
        ]);
    }
}
