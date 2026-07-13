<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    /**
     * Serve robots.txt. Rule body is editable by admin (Developer page →
     * BusinessProfile.extra['robots_txt']), falling back to defaultBody().
     * The "Sitemap:" line is always appended here from APP_URL and is NOT
     * part of the editable text — admin edits can't hardcode the wrong
     * domain (this exact bug happened once with a leftover casambi.vn URL).
     * GET /robots.txt
     */
    public function index(): Response
    {
        $body = trim((string) (BusinessProfile::instance()->extra['robots_txt'] ?? ''));

        if ($body === '') {
            $body = self::defaultBody();
        }

        $baseUrl = rtrim(config('app.url'), '/');
        $content = $body."\n\nSitemap: {$baseUrl}/sitemap.xml\n";

        return response($content, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public static function defaultBody(): string
    {
        return implode("\n", [
            'User-agent: *',
            '',
            '# Public locale paths',
            'Allow: /vi/',
            'Allow: /en/',
            '',
            '# Block internal',
            'Disallow: /admin/',
            'Disallow: /horizon/',
            'Disallow: /api/',
            'Disallow: /telescope/',
            '',
            '# Block no-locale paths (all redirect to /vi/ or /en/ — avoid wasting crawl budget)',
            'Disallow: /products/',
            'Disallow: /categories/',
            'Disallow: /blog/',
            'Disallow: /brands/',
            'Disallow: /manufacturers/',
            '',
            '# Block user-session pages (cart/wishlist — no unique SEO value)',
            'Disallow: /vi/gio-hang',
            'Disallow: /vi/tai-khoan/yeu-thich',
            'Disallow: /en/cart',
            'Disallow: /en/account/wishlist',
            '',
            '# Block search results (near-duplicate, unbounded query space)',
            'Disallow: /vi/tim-kiem',
            'Disallow: /en/search',
            '',
            '# Block filter/sort (near-duplicate risk) — page kept crawlable,',
            '# paginated listing pages self-canonicalize (see ProductController)',
            'Disallow: /*?sort=',
            'Disallow: /*?filter=',
        ]);
    }
}
