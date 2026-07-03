<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class DeveloperPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon  = 'heroicon-o-code-bracket';
    protected static \UnitEnum|string|null  $navigationGroup = 'System';
    protected static ?string               $navigationLabel = 'Developer';
    protected static ?int                  $navigationSort  = 40;

    protected string $view = 'filament.pages.developer';

    public function getSystemInfo(): array
    {
        $dbVersion = '—';
        try {
            $dbVersion = match (config('database.default')) {
                'pgsql'  => DB::selectOne('SELECT version() AS v')->v,
                'mysql'  => DB::selectOne('SELECT VERSION() AS v')->v,
                default  => config('database.default'),
            };
            // shorten PostgreSQL verbose version
            if (str_contains($dbVersion, ',')) {
                $dbVersion = explode(',', $dbVersion)[0];
            }
        } catch (\Throwable) {}

        return [
            'PHP'           => '8.5',
            'Laravel'       => app()->version(),
            'Environment'   => config('app.env'),
            'App URL'       => config('app.url'),
            'Database'      => ucfirst(config('database.default')) . ' — ' . $dbVersion,
            'Cache'         => config('cache.default'),
            'Queue'         => config('queue.default'),
            'Session'       => config('session.driver'),
        ];
    }

    public function getStack(): array
    {
        return [
            'Backend'  => 'Laravel 13 · PHP 8.5 · PostgreSQL',
            'Frontend' => 'Nuxt 3 · TypeScript · Tailwind CSS',
            'Admin'    => 'Filament v3',
            'Search'   => 'Meilisearch + Laravel Scout',
            'Queue'    => 'Redis + Laravel Horizon',
            'AI / MCP' => 'Claude API · MCP Server (TypeScript)',
            'Workflow' => 'n8n · Supabase · Pancake',
        ];
    }

    /**
     * Ordered, newest-first showcase of shipped features — each entry names the
     * tech it exercises so this page doubles as a living map of what's actually
     * wired vs. still mockup. Append new entries to the top as features ship.
     */
    public function getFeatures(): array
    {
        return [
            [
                'title'       => 'Lọc giá theo khoảng (price range) — Shop & Category',
                'description' => 'Filter min/max giá trên trang Shop (/cua-hang) và Category, tính theo effective_price (ưu tiên sale_price khi thấp hơn price — đúng giá khách thực trả). Cận slider tự tính từ MIN/MAX giá thực tế trong DB (cache 5 phút), không cấu hình thủ công.',
                'tech'        => ['Meilisearch', 'Laravel Scout', 'PostgreSQL'],
                'status'      => 'done',
                'detail'      => 'Meilisearch là path chính — effective_price là filterable/sortable attribute index sẵn, filter chạy native không tính runtime. SQL fallback (khi Meilisearch down hoặc có filter brand) dùng LEAST(price, COALESCE(sale_price, price)). Cả 2 path đều batch eager-load (whereIn), không N+1 — đã đo bằng DB::listen: getPriceBounds() = 1 query (0 khi cache warm), search = 5-6 query cố định không phụ thuộc số kết quả.',
                'files'       => [
                    'app/Models/Product.php — effective_price trong toSearchableArray()',
                    'app/Services/Catalog/ProductSearchService.php — buildFilterExpression() + getPriceBounds()',
                    'app/Repositories/Eloquent/ProductRepository.php — searchWithFiltersSql() + getPriceBounds()',
                    'resources/views/pages/product/index.blade.php — dual-thumb range slider',
                ],
            ],
        ];
    }
}
