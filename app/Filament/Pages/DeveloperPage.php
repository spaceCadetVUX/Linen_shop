<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Http\Controllers\Web\RobotsController;
use App\Models\BusinessProfile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class DeveloperPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-code-bracket';

    protected static ?int $navigationSort = 40;

    protected string $view = 'filament.pages.developer';

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.nav.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.nav.labels.developer_page');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $robotsTxt = (string) (BusinessProfile::instance()->extra['robots_txt'] ?? '');

        $this->form->fill([
            'robots_txt' => $robotsTxt !== '' ? $robotsTxt : RobotsController::defaultBody(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $sitemapLine = 'Sitemap: '.rtrim(config('app.url'), '/').'/sitemap.xml';

        return $schema
            ->schema([
                Section::make(__('admin.developer_page.sections.robots_txt'))
                    ->icon('heroicon-o-document-text')
                    ->description(__('admin.developer_page.sections.robots_txt_desc'))
                    ->schema([
                        Textarea::make('robots_txt')
                            ->label(false)
                            ->rows(18)
                            ->extraInputAttributes(['style' => 'font-family:ui-monospace,monospace;font-size:0.8125rem;'])
                            ->helperText(__('admin.developer_page.fields.robots_txt_help', ['line' => $sitemapLine]))
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewRobots')
                ->label(__('admin.developer_page.actions.view_robots'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url('/robots.txt')
                ->openUrlInNewTab(),

            Action::make('saveRobots')
                ->label(__('admin.developer_page.actions.save_robots'))
                ->icon('heroicon-o-check')
                ->action('saveRobots'),
        ];
    }

    public function saveRobots(): void
    {
        $data = $this->form->getState();

        $profile = BusinessProfile::instance();
        $extra = (array) ($profile->extra ?? []);
        $extra['robots_txt'] = trim((string) ($data['robots_txt'] ?? ''));

        $profile->extra = $extra;
        $profile->saveQuietly();

        Notification::make()
            ->title(__('admin.developer_page.notifications.saved'))
            ->success()
            ->send();
    }

    public function getSystemInfo(): array
    {
        $dbVersion = '—';
        try {
            $dbVersion = match (config('database.default')) {
                'pgsql' => DB::selectOne('SELECT version() AS v')->v,
                'mysql' => DB::selectOne('SELECT VERSION() AS v')->v,
                default => config('database.default'),
            };
            // shorten PostgreSQL verbose version
            if (str_contains($dbVersion, ',')) {
                $dbVersion = explode(',', $dbVersion)[0];
            }
        } catch (\Throwable) {
        }

        return [
            'PHP' => '8.5',
            'Laravel' => app()->version(),
            'Environment' => config('app.env'),
            'App URL' => config('app.url'),
            'Database' => ucfirst(config('database.default')).' — '.$dbVersion,
            'Cache' => config('cache.default'),
            'Queue' => config('queue.default'),
            'Session' => config('session.driver'),
        ];
    }

    /**
     * MCP server connection details for the "Add to Claude Desktop" guide.
     * URL is derived from APP_URL's host (mcp.{domain}/) rather than
     * hardcoded, so it follows if the domain ever changes. No API key here
     * any more — mcp-auth-proxy in front of mcp-server handles auth via
     * Google OAuth, so mcp-remote discovers it automatically.
     *
     * Root path, not /mcp: mcp-auth-proxy always advertises its OAuth
     * "resource" identifier as its external URL's root (upstream bug,
     * sigbit/mcp-auth-proxy#177 — still open, no fix). claude.ai enforces
     * exact RFC 9728 resource matching, so the URL given to clients must be
     * the root or the connector silently fails after a successful login.
     */
    public function getMcpConfig(): array
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?? 'cacylinen.com';

        return [
            'url' => "https://mcp.{$host}/",
        ];
    }

    /**
     * Ready-to-paste claude_desktop_config.json snippet for the MCP guide —
     * built once via json_encode() so the display block and the "Copy JSON"
     * button always show the exact same, correctly-escaped text.
     */
    public function getMcpConfigJson(): string
    {
        $mcp = $this->getMcpConfig();

        return json_encode([
            'mcpServers' => [
                'cacylinen-mcp' => [
                    'command' => 'npx',
                    'args' => [
                        '-y',
                        'mcp-remote',
                        $mcp['url'],
                        '--transport',
                        'http-only',
                    ],
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function getStack(): array
    {
        return [
            'Backend' => 'Laravel 13 · PHP 8.5 · PostgreSQL',
            'Frontend' => 'Nuxt 3 · TypeScript · Tailwind CSS',
            'Admin' => 'Filament v3',
            'Search' => 'Meilisearch + Laravel Scout',
            'Queue' => 'Redis + Laravel Horizon',
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
                'title' => 'Lọc giá theo khoảng (price range) — Shop & Category',
                'description' => 'Filter min/max giá trên trang Shop (/cua-hang) và Category, tính theo effective_price (ưu tiên sale_price khi thấp hơn price — đúng giá khách thực trả). Cận slider tự tính từ MIN/MAX giá thực tế trong DB (cache 5 phút), không cấu hình thủ công.',
                'tech' => ['Meilisearch', 'Laravel Scout', 'PostgreSQL'],
                'status' => 'done',
                'detail' => 'Meilisearch là path chính — effective_price là filterable/sortable attribute index sẵn, filter chạy native không tính runtime. SQL fallback (khi Meilisearch down hoặc có filter brand) dùng LEAST(price, COALESCE(sale_price, price)). Cả 2 path đều batch eager-load (whereIn), không N+1 — đã đo bằng DB::listen: getPriceBounds() = 1 query (0 khi cache warm), search = 5-6 query cố định không phụ thuộc số kết quả.',
                'files' => [
                    'app/Models/Product.php — effective_price trong toSearchableArray()',
                    'app/Services/Catalog/ProductSearchService.php — buildFilterExpression() + getPriceBounds()',
                    'app/Repositories/Eloquent/ProductRepository.php — searchWithFiltersSql() + getPriceBounds()',
                    'resources/views/pages/product/index.blade.php — dual-thumb range slider',
                ],
            ],
        ];
    }
}
