<?php

namespace Tests\Feature\Seo;

use App\Models\Seo\SitemapIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_index_returns_xml(): void
    {
        $this->get('/sitemap.xml')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function test_sitemap_index_contains_child_links(): void
    {
        // Child sitemaps are locale-prefixed (sitemap-{locale}-{type}.xml) —
        // see SitemapController::child() / routes/web.php.
        SitemapIndex::create([
            'name'      => 'vi-products',
            'filename'  => 'sitemap-vi-products.xml',
            'url'       => url('sitemap-vi-products.xml'),
            'is_active' => true,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $this->assertStringContainsString('sitemap-vi-products.xml', $response->getContent());
    }

    public function test_product_sitemap_returns_xml(): void
    {
        Storage::fake('public');

        SitemapIndex::create([
            'name'      => 'vi-products',
            'filename'  => 'sitemap-vi-products.xml',
            'url'       => url('sitemap-vi-products.xml'),
            'locale'    => 'vi',
            'is_active' => true,
        ]);

        $this->get('/sitemap-vi-products.xml')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function test_blog_sitemap_returns_xml(): void
    {
        Storage::fake('public');

        SitemapIndex::create([
            'name'      => 'vi-blog',
            'filename'  => 'sitemap-vi-blog.xml',
            'url'       => url('sitemap-vi-blog.xml'),
            'locale'    => 'vi',
            'is_active' => true,
        ]);

        $this->get('/sitemap-vi-blog.xml')
            ->assertStatus(200);
    }

    public function test_unknown_sitemap_returns_404(): void
    {
        $this->get('/sitemap-nonexistent.xml')
            ->assertStatus(404);
    }
}
