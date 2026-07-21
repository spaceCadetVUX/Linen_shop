<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\DeveloperPage;
use Filament\Widgets\Concerns\CanPoll;
use Filament\Widgets\Widget;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Meilisearch\Client as MeilisearchClient;

class SystemHealthWidget extends Widget
{
    use CanPoll;

    // Same key ProductSearchService trips when Meilisearch fails — reusing it
    // here means this widget and the storefront's fallback always agree on
    // whether search is currently degraded.
    private const CIRCUIT_KEY = 'search:meili:down';

    protected string $view = 'filament.widgets.system-health';

    protected int|string|array $columnSpan = 'full';

    // 30s instead of the framework default 5s: on this dev box a single
    // request can take several seconds (Windows/WSL2 bind-mount overhead —
    // see docker/php/php.ini). A 5s poll fires a new Livewire request before
    // the previous one finishes, and the overlapping requests make the
    // widget flicker/blank out. 30s keeps it comfortably clear of that.
    protected function getPollingInterval(): ?string
    {
        return '30s';
    }

    // Internal Docker network hostname:port — fixed by docker-compose.yml
    // (service `mcp-server`, port 3101), same on local dev and the VPS since
    // both run off the same compose file. Not deployment-configurable, so not
    // worth a config/env entry (mcp-server itself hardcodes KNXSTORE_API_BASE
    // the same way for its call back into this app).
    private const MCP_URL = 'http://mcp-server:3101/mcp';

    public function getServices(): array
    {
        return [
            $this->meilisearchService(),
            $this->horizonService(),
            $this->mcpService(),
        ];
    }

    private function meilisearchService(): array
    {
        $degraded = Cache::has(self::CIRCUIT_KEY);

        try {
            $start = microtime(true);
            app(MeilisearchClient::class)->health();
            $ms = round((microtime(true) - $start) * 1000);

            return [
                'name' => 'Meilisearch',
                'description' => 'Search engine cho trang sản phẩm & danh mục',
                'status' => $degraded ? 'degraded' : 'online',
                'statusLabel' => $degraded ? 'Fallback SQL' : 'Đang chạy',
                'headline' => "{$ms} ms",
                'headlineLabel' => $degraded
                    ? 'Health OK — vẫn trong cửa sổ fallback 30s do lỗi gần đây'
                    : 'Thời gian phản hồi',
                'stats' => [],
                'icon' => 'heroicon-o-magnifying-glass',
                // config('scout.meilisearch.host') is the internal Docker hostname
                // (only reachable from other containers) — swap in the browser-
                // reachable host so the link actually opens for the admin.
                'url' => str_replace('meilisearch', request()->getHost(), rtrim(config('scout.meilisearch.host'), '/')),
            ];
        } catch (\Throwable $e) {
            return [
                'name' => 'Meilisearch',
                'description' => 'Search engine cho trang sản phẩm & danh mục',
                'status' => 'offline',
                'statusLabel' => 'Không kết nối được',
                'headline' => '—',
                'headlineLabel' => 'Đang tự động fallback sang SQL',
                'stats' => [],
                'icon' => 'heroicon-o-magnifying-glass',
                'url' => null,
            ];
        }
    }

    private function horizonService(): array
    {
        $masters = app(MasterSupervisorRepository::class)->all();

        $status = match (true) {
            empty($masters) => 'offline',
            collect($masters)->contains(fn ($m) => $m->status === 'paused') => 'degraded',
            default => 'online',
        };

        $pending = collect(['default', 'orders', 'seo', 'notifications'])
            ->sum(fn ($queue) => (int) Redis::llen("queues:{$queue}"));
        $failed = DB::table('failed_jobs')->count();

        return [
            'name' => 'Horizon',
            'description' => 'Queue worker (Redis)',
            'status' => $status,
            'statusLabel' => match ($status) {
                'online' => 'Đang chạy',
                'degraded' => 'Tạm dừng',
                'offline' => 'Không hoạt động',
            },
            'headline' => (string) $pending,
            'headlineLabel' => 'Job đang chờ xử lý',
            'stats' => [
                ['label' => 'Lỗi', 'value' => $failed, 'tone' => $failed > 0 ? 'danger' : 'gray'],
            ],
            'icon' => 'heroicon-o-queue-list',
            'url' => '/horizon',
        ];
    }

    private function mcpService(): array
    {
        $description = 'Cầu nối Claude ↔ API (container mcp-server)';
        $icon = 'heroicon-o-command-line';
        $url = DeveloperPage::getUrl();
        $apiKey = (string) config('services.mcp.api_key');

        if ($apiKey === '') {
            return [
                'name' => 'MCP Server',
                'description' => $description,
                'status' => 'offline',
                'statusLabel' => 'Chưa cấu hình',
                'headline' => '—',
                'headlineLabel' => 'Thiếu MCP_API_KEY trong .env',
                'stats' => [],
                'icon' => $icon,
                'url' => $url,
            ];
        }

        $sessionId = null;

        try {
            $start = microtime(true);

            $response = Http::timeout(3)->withHeaders([
                'Content-Type' => 'application/json',
                // Streamable HTTP spec requires accepting BOTH — mcp-server's SDK
                // transport (webStandardStreamableHttp) returns 406 before it even
                // looks at X-Api-Key if either is missing.
                'Accept' => 'application/json, text/event-stream',
                'X-Api-Key' => $apiKey,
            ])->post(self::MCP_URL, [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'initialize',
                'params' => [
                    'protocolVersion' => '2025-03-26',
                    // Must encode as `{}`, not `[]` — the SDK's zod schema for
                    // "initialize" requires capabilities to be an object.
                    'capabilities' => new \stdClass,
                    'clientInfo' => ['name' => 'system-health-widget', 'version' => '1.0'],
                ],
            ]);

            $ms = round((microtime(true) - $start) * 1000);
            $sessionId = $response->header('Mcp-Session-Id');
            $serverName = data_get($this->parseMcpBody($response), 'result.serverInfo.name');

            $status = match (true) {
                $serverName === 'knxstore-mcp' => 'online',
                $response->status() === 401 => 'offline',
                default => 'degraded',
            };

            return [
                'name' => 'MCP Server',
                'description' => $description,
                'status' => $status,
                'statusLabel' => match ($status) {
                    'online' => 'Đang chạy',
                    'offline' => 'Sai API key',
                    'degraded' => "HTTP {$response->status()}",
                },
                'headline' => $status === 'online' ? "{$ms} ms" : '—',
                'headlineLabel' => $status === 'online'
                    ? 'Thời gian phản hồi'
                    : 'Kiểm tra: docker compose logs mcp-server',
                'stats' => [],
                'icon' => $icon,
                'url' => $url,
            ];
        } catch (\Throwable) {
            return [
                'name' => 'MCP Server',
                'description' => $description,
                'status' => 'offline',
                'statusLabel' => 'Không kết nối được',
                'headline' => '—',
                'headlineLabel' => 'Container mcp-server không phản hồi',
                'stats' => [],
                'icon' => $icon,
                'url' => $url,
            ];
        } finally {
            // mcp-server registers a brand-new session on every "initialize" call
            // and never expires it on its own (no TTL in mcp-server/src/index.ts —
            // only an explicit DELETE removes it from the in-memory `sessions`
            // Map). Without this, polling this widget every 30s would leak one
            // session into the container's memory forever. Best-effort cleanup:
            // its failure must never change the status computed above.
            if ($sessionId) {
                try {
                    Http::timeout(2)->withHeaders([
                        'X-Api-Key' => $apiKey,
                        'Mcp-Session-Id' => $sessionId,
                    ])->delete(self::MCP_URL);
                } catch (\Throwable) {
                }
            }
        }
    }

    /**
     * mcp-server's transport doesn't set `enableJsonResponse`, so even a
     * single-shot "initialize" reply comes back SSE-framed
     * ("event: message\ndata: {...}\n\n") rather than a bare JSON body.
     */
    private function parseMcpBody(Response $response): ?array
    {
        $json = $response->json();
        if (is_array($json)) {
            return $json;
        }

        if (preg_match('/^data:\s*(\{.*\})\s*$/m', $response->body(), $matches)) {
            return json_decode($matches[1], true);
        }

        return null;
    }
}
