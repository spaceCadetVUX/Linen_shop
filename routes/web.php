<?php

use App\Enums\UserRole;
use App\Http\Controllers\Web\AboutController;
use App\Http\Controllers\Web\AuthorController;
use App\Http\Controllers\Web\BlogController;
use App\Http\Controllers\Web\CartController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\HealthController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LlmsController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\RobotsController;
use App\Http\Controllers\Web\SearchController;
use App\Http\Controllers\Web\SitemapController;
use App\Http\Controllers\Web\SizeGuideController;
use App\Http\Controllers\Web\WishlistController;
use App\Repositories\Eloquent\BlogPostRepository;
use App\Support\LocaleUrl;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// ── Sessionless public pages ──────────────────────────────────────────────────
// Anonymous, GET-only, content pages (category/product/blog/...) never read
// session() and have no @csrf forms — dropping these lets Cache-Control: public
// (see cache.public middleware) actually mean something. Without this, every
// response still carries Set-Cookie (XSRF-TOKEN + session id, added by
// PreventRequestForgery/StartSession regardless of route), which makes CDNs/
// proxies refuse to cache the response no matter what Cache-Control says.
// Cart/Wishlist/Search stay on the full 'web' stack — they're user-specific.
$sessionless = [
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    PreventRequestForgery::class,
];

// ── Root: detect preferred locale → redirect ─────────────────────────────────
Route::get('/', function () {
    return redirect('/vi/', 301);
});

// ── System: Health Check ─────────────────────────────────────────────────────
Route::get('health', HealthController::class);

// ── SEO: Robots ───────────────────────────────────────────────────────────────
Route::get('robots.txt', [RobotsController::class, 'index']);

// ── SEO: Sitemap XML ─────────────────────────────────────────────────────────
Route::get('sitemap.xml', [SitemapController::class, 'index']);
Route::get('sitemap-static.xml', [SitemapController::class, 'static']);
Route::get('sitemap-{locale}-{type}.xml', [SitemapController::class, 'child'])
    ->where(['locale' => 'vi|en', 'type' => 'products|product-categories|blog|blog-categories']);

// ── SEO: LLMs TXT ────────────────────────────────────────────────────────────
// These use {locale} param in URI — kept as-is (set.locale reads from route param)
Route::middleware('throttle:30,1')->group(function () {
    $localePattern = implode('|', config('app.supported_locales'));

    Route::get('{locale}/llms.txt', [LlmsController::class, 'localized'])
        ->where('locale', $localePattern)
        ->middleware('set.locale');

    Route::get('llms.txt', fn () => redirect('/vi/llms.txt', 302));
    Route::get('llms-full.txt', [LlmsController::class, 'full']);
    Route::get('llms-{slug}.txt', [LlmsController::class, 'scoped']);
});

// ── API Docs ──────────────────────────────────────────────────────────────────
// Scribe's "Try It Out" calls live /api/v1/* endpoints straight from the
// browser and lists every param/response shape — free on local/staging, but
// on production only an authenticated admin may load it (404, not 403, so an
// anonymous scanner can't even confirm the route exists).
//
// config/scribe.php has `laravel.add_routes` OFF (Scribe's own auto-registered
// `docs` route has no auth and would collide with / bypass this gate), so the
// two extra named routes its generated Blade view depends on — `scribe.postman`
// and `scribe.openapi` (download links) — are hand-registered here too, copied
// from vendor/knuckleswtf/scribe/routes/laravel.php, behind the same gate.
$scribeAuthorized = fn (): bool => (app()->isLocal() || app()->environment('staging'))
    || auth()->user()?->role === UserRole::Admin;

Route::get('docs', function () use ($scribeAuthorized) {
    abort_unless($scribeAuthorized(), 404);

    return view('scribe.index');
});

Route::get('docs.postman', function () use ($scribeAuthorized) {
    abort_unless($scribeAuthorized(), 404);

    return new JsonResponse(Storage::disk('local')->get('scribe/collection.json'), json: true);
})->name('scribe.postman');

Route::get('docs.openapi', function () use ($scribeAuthorized) {
    abort_unless($scribeAuthorized(), 404);

    return response()->file(Storage::disk('local')->path('scribe/openapi.yaml'));
})->name('scribe.openapi');

if (app()->isLocal() || app()->environment('staging')) {
    Route::get('test-seo-head', fn () => view('test-seo-head'));
}

// ══════════════════════════════════════════════════════════════════════════════
// TIẾNG VIỆT  /vi/*
//
// Hardcoded prefix "vi" (not a route param) → avoids URI collision with /en/*
// Locale is injected into the route as a virtual parameter by SetLocale
// middleware, so controllers can still type-hint `string $locale` normally.
//
// Route names: vi.{name}
// ══════════════════════════════════════════════════════════════════════════════
Route::prefix('vi')
    ->middleware('set.locale:vi')
    ->group(function () use ($sessionless) {

        Route::get('/', [HomeController::class, 'index'])
            ->name('vi.index')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        // ── Giới thiệu ────────────────────────────────────────────────────────
        Route::get('gioi-thieu', [AboutController::class, 'show'])
            ->name('vi.about')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        // ── Tìm kiếm autocomplete (trước tim-kiem để tránh slug collision) ───
        Route::get('tim-kiem/goi-y', [ProductController::class, 'autocomplete'])
            ->name('vi.product.autocomplete');

        // ── Tìm kiếm ─────────────────────────────────────────────────────────
        Route::get('tim-kiem', [SearchController::class, 'index'])
            ->name('vi.search');

        // ── Cửa hàng / Danh mục sản phẩm ─────────────────────────────────────
        Route::get('cua-hang', [ProductController::class, 'index'])
            ->name('vi.product.shop')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        Route::get('danh-muc', [CategoryController::class, 'index'])
            ->name('vi.product.category')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        // ── Danh mục + Sản phẩm ───────────────────────────────────────────────
        Route::get('danh-muc/{slug}', [CategoryController::class, 'show'])
            ->name('vi.category.show')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        Route::get('san-pham/{slug}', [ProductController::class, 'show'])
            ->name('vi.product.show')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        // ── Blog ──────────────────────────────────────────────────────────────
        Route::get('bai-viet', [BlogController::class, 'index'])
            ->name('vi.blog.index')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        Route::get('blog/{slug}', [BlogController::class, 'category'])
            ->name('vi.blog.category')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        // Legacy: redirect old /vi/chu-de/{slug} → /vi/blog/{slug}
        Route::get('chu-de/{slug}', fn (string $locale, string $slug) => redirect("/{$locale}/blog/{$slug}", 301));

        // Nested: /vi/bai-viet/{category_slug}/{slug}
        Route::get('bai-viet/{category_slug}/{slug}', [BlogController::class, 'show'])
            ->name('vi.blog.show')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        // Legacy flat URL → 301 to nested (SEO backward compat)
        Route::get('bai-viet/{slug}', function (string $locale, string $slug) {
            $translation = app(BlogPostRepository::class)->findTranslationBySlug($slug, 'vi');
            if (! $translation) {
                abort(404);
            }
            $post = $translation->blogPost;

            return redirect(LocaleUrl::forBlogPost($post, 'vi'), 301);
        })->name('vi.blog.show.legacy');

        // ── Tác giả ───────────────────────────────────────────────────────────
        Route::get('tac-gia/{slug}', [AuthorController::class, 'show'])
            ->name('vi.author.show')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        // ── Hướng dẫn chọn size ──────────────────────────────────────────────
        Route::get('huong-dan-size', [SizeGuideController::class, 'index'])
            ->name('vi.size-guide')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        // Legacy hardcoded link /size-guide (fallback redirects here without locale)
        Route::get('size-guide', fn () => redirect(route('vi.size-guide'), 301));

        // ── Yêu thích (guest-session wishlist, xem WishlistController) ────────
        Route::get('tai-khoan/yeu-thich', [WishlistController::class, 'index'])
            ->name('vi.account.wishlist');

        // ── Giỏ hàng (guest-session cart, xem CartController) ─────────────────
        Route::get('gio-hang', [CartController::class, 'index'])
            ->name('vi.cart');

        // ── Trang tĩnh — catch-all, phải đặt cuối cùng ───────────────────────
        Route::get('{slug}', [PageController::class, 'show'])
            ->name('vi.page.show');
    });

// ══════════════════════════════════════════════════════════════════════════════
// ENGLISH  /en/*
// Route names: en.{name}
// ══════════════════════════════════════════════════════════════════════════════
Route::prefix('en')
    ->middleware('set.locale:en')
    ->group(function () use ($sessionless) {

        Route::get('/', [HomeController::class, 'index'])
            ->name('en.index')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        // ── About ─────────────────────────────────────────────────────────────
        Route::get('about', [AboutController::class, 'show'])
            ->name('en.about')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        // ── Search autocomplete (trước search để tránh slug collision) ────────
        Route::get('search/autocomplete', [ProductController::class, 'autocomplete'])
            ->name('en.product.autocomplete');

        // ── Search ────────────────────────────────────────────────────────────
        Route::get('search', [SearchController::class, 'index'])
            ->name('en.search');

        // ── Shop / Category listing ───────────────────────────────────────────
        Route::get('shop', [ProductController::class, 'index'])
            ->name('en.product.shop')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        Route::get('categories', [CategoryController::class, 'index'])
            ->name('en.product.category')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        // ── Categories + Products ─────────────────────────────────────────────
        Route::get('categories/{slug}', [CategoryController::class, 'show'])
            ->name('en.category.show')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        Route::get('products/{slug}', [ProductController::class, 'show'])
            ->name('en.product.show')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        // ── Blog ──────────────────────────────────────────────────────────────
        Route::get('blog', [BlogController::class, 'index'])
            ->name('en.blog.index')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        // Legacy: old /en/blog/category/{slug} → /en/blog/{slug} (301)
        // Must be declared before blog/{category_slug}/{slug} to avoid collision
        Route::get('blog/category/{slug}', fn (string $locale, string $slug) => redirect("/{$locale}/blog/{$slug}", 301));

        // Blog post: /en/blog/{category_slug}/{post_slug}
        Route::get('blog/{category_slug}/{slug}', [BlogController::class, 'show'])
            ->name('en.blog.show')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        // Blog category + legacy flat post redirect: /en/blog/{slug}
        Route::get('blog/{slug}', function (string $locale, string $slug) {
            // Legacy flat post URL → redirect to nested (301, SEO backward compat)
            $translation = app(BlogPostRepository::class)->findTranslationBySlug($slug, 'en');
            if ($translation) {
                return redirect(LocaleUrl::forBlogPost($translation->blogPost, 'en'), 301);
            }

            // Serve as blog category page
            return app(BlogController::class)->category($locale, $slug);
        })->name('en.blog.category')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        // ── Authors ───────────────────────────────────────────────────────────
        Route::get('authors/{slug}', [AuthorController::class, 'show'])
            ->name('en.author.show')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        // ── Size guide ────────────────────────────────────────────────────────
        Route::get('size-guide', [SizeGuideController::class, 'index'])
            ->name('en.size-guide')
            ->middleware('cache.public')->withoutMiddleware($sessionless);

        // ── Wishlist (guest-session, see WishlistController) ──────────────────
        Route::get('account/wishlist', [WishlistController::class, 'index'])
            ->name('en.account.wishlist');

        // ── Cart (guest-session, see CartController) ──────────────────────────
        Route::get('cart', [CartController::class, 'index'])
            ->name('en.cart');

        // ── Static pages — catch-all, must be last ────────────────────────────
        Route::get('{slug}', [PageController::class, 'show'])
            ->name('en.page.show');
    });

// ── Fallback: no locale prefix → 301 to /vi/ ────────────────────────────────
Route::fallback(function (Request $request) {
    $path = ltrim($request->path(), '/');

    // 404 instead of redirecting when the path is already locale-prefixed
    // (e.g. a missing asset under /vi/assets/...) or looks like a static
    // asset path. Without this guard, a missing file under /assets/... loops
    // forever: /assets/x.jpg → redirect /vi/assets/x.jpg (still no route/file
    // match) → fallback fires again → /vi/vi/assets/x.jpg → ... until the
    // browser gives up with ERR_TOO_MANY_REDIRECTS.
    $firstSegment = explode('/', $path)[0] ?? '';
    $isAlreadyLocalePrefixed = in_array($firstSegment, config('app.supported_locales', ['vi', 'en']), true);
    $looksLikeStaticAsset = (bool) preg_match(
        '/\.(jpe?g|png|gif|svg|webp|avif|ico|css|js|mjs|map|woff2?|ttf|eot|json|xml|txt|pdf)$/i',
        $path
    );

    if ($isAlreadyLocalePrefixed || $looksLikeStaticAsset) {
        abort(404);
    }

    // Entity routes (product/category/brand/manufacturer) use a FIXED
    // Vietnamese path segment per locale (san-pham, danh-muc, thuong-hieu,
    // nha-san-xuat) — not a generic {slug} catch-all. A bare English-style
    // path like /products/{slug} redirected to /vi/products/{slug} 404s,
    // since vi.product.show only matches /vi/san-pham/{slug}. Static pages
    // (/about, /contact...) don't have this problem — vi.page.show is a
    // real {slug} catch-all that accepts any segment name.
    // 'blog' is deliberately excluded — both locales use it (blog_post AND
    // blog_category on en, blog_category on vi), so it can't be disambiguated
    // by segment name alone; falls through to the /vi/ default below.
    $enOnlyEntitySegments = ['products', 'categories', 'brands', 'manufacturers'];
    $targetLocale = in_array($firstSegment, $enOnlyEntitySegments, true) ? 'en' : 'vi';

    return redirect("/{$targetLocale}/{$path}", 301);
});
