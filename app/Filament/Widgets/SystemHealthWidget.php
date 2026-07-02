<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Concerns\CanPoll;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    public function getServices(): array
    {
        return [
            $this->meilisearchService(),
            $this->horizonService(),
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
                'name'        => 'Meilisearch',
                'description' => 'Search engine cho trang sản phẩm & danh mục',
                'status'      => $degraded ? 'degraded' : 'online',
                'statusLabel' => $degraded ? 'Fallback SQL' : 'Đang chạy',
                'metric'      => $degraded
                    ? 'Health OK — vẫn trong cửa sổ fallback 30s do lỗi gần đây'
                    : "Phản hồi {$ms}ms",
                'icon'        => 'heroicon-o-magnifying-glass',
                // config('scout.meilisearch.host') is the internal Docker hostname
                // (only reachable from other containers) — swap in the browser-
                // reachable host so the link actually opens for the admin.
                'url'         => str_replace('meilisearch', request()->getHost(), rtrim(config('scout.meilisearch.host'), '/')),
            ];
        } catch (\Throwable $e) {
            return [
                'name'        => 'Meilisearch',
                'description' => 'Search engine cho trang sản phẩm & danh mục',
                'status'      => 'offline',
                'statusLabel' => 'Không kết nối được',
                'metric'      => 'Đang tự động fallback sang SQL',
                'icon'        => 'heroicon-o-magnifying-glass',
                'url'         => null,
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
            'name'        => 'Horizon',
            'description' => 'Queue worker (Redis)',
            'status'      => $status,
            'statusLabel' => match ($status) {
                'online'   => 'Đang chạy',
                'degraded' => 'Tạm dừng',
                'offline'  => 'Không hoạt động',
            },
            'metric'      => "{$pending} job đang chờ · {$failed} job lỗi",
            'icon'        => 'heroicon-o-queue-list',
            'url'         => '/horizon',
        ];
    }
}
