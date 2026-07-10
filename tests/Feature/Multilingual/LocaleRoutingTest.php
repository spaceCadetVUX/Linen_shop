<?php

namespace Tests\Feature\Multilingual;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_locale(): void
    {
        // Permanent (301) — routes/web.php intentionally uses redirect('/vi/', 301)
        // for SEO (root is a single canonical destination, not a temporary one).
        $this->get('/')->assertStatus(301);
    }

    public function test_valid_locale_returns_200(): void
    {
        $this->get('/vi/')->assertStatus(200);
        $this->get('/en/')->assertStatus(200);
    }

    public function test_invalid_locale_returns_404(): void
    {
        // /xx/ hits the fallback (301 → /vi/xx); the locale group routes it to
        // PageController which finds no matching PageTranslation → 404.
        $this->followingRedirects()
            ->get('/xx/')
            ->assertStatus(404);
    }

    public function test_no_locale_path_redirects_301(): void
    {
        // 'products' is an English-only entity segment (vi.product.show only
        // matches /vi/san-pham/{slug}) — the fallback route deliberately sends
        // it to /en/, not /vi/, so it resolves instead of 404ing.
        $this->get('/products/test')
            ->assertStatus(301)
            ->assertRedirect('/en/products/test');
    }
}
