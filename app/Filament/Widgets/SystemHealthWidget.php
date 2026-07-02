<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Meilisearch\Client as MeilisearchClient;

class SystemHealthWidget extends StatsOverviewWidget
{
    // Same key ProductSearchService trips when Meilisearch fails — reusing it
    // here means this widget and the storefront's fallback always agree on
    // whether search is currently degraded.
    private const CIRCUIT_KEY = 'search:meili:down';

    protected function getStats(): array
    {
        return [
            $this->meilisearchStat(),
            $this->horizonStat(),
        ];
    }

    private function meilisearchStat(): Stat
    {
        $degraded = Cache::has(self::CIRCUIT_KEY);

        try {
            $start = microtime(true);
            app(MeilisearchClient::class)->health();
            $ms = round((microtime(true) - $start) * 1000);

            return Stat::make('Meilisearch', $degraded ? 'Fallback SQL' : 'Đang chạy')
                ->description($degraded
                    ? 'Health OK nhưng vẫn trong cửa sổ fallback (30s) do lỗi gần đây'
                    : "Phản hồi {$ms}ms")
                ->color($degraded ? 'warning' : 'success')
                ->icon($degraded ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                // config('scout.meilisearch.host') is the internal Docker hostname
                // (only reachable from other containers) — swap in the browser-
                // reachable host so the link actually opens for the admin.
                ->url(str_replace('meilisearch', request()->getHost(), rtrim(config('scout.meilisearch.host'), '/')))
                ->openUrlInNewTab();
        } catch (\Throwable $e) {
            return Stat::make('Meilisearch', 'Không kết nối được')
                ->description('PLP/category đang tự động fallback sang SQL — ' . $e->getMessage())
                ->color('danger')
                ->icon('heroicon-o-x-circle');
        }
    }

    private function horizonStat(): Stat
    {
        $masters = app(MasterSupervisorRepository::class)->all();

        $status = match (true) {
            empty($masters) => 'inactive',
            collect($masters)->contains(fn ($m) => $m->status === 'paused') => 'paused',
            default => 'running',
        };

        $pending = collect(['default', 'orders', 'seo', 'notifications'])
            ->sum(fn ($queue) => (int) Redis::llen("queues:{$queue}"));
        $failed = DB::table('failed_jobs')->count();

        [$label, $color, $icon] = match ($status) {
            'running'  => ['Đang chạy', 'success', 'heroicon-o-check-circle'],
            'paused'   => ['Tạm dừng', 'warning', 'heroicon-o-pause-circle'],
            'inactive' => ['Không hoạt động', 'danger', 'heroicon-o-x-circle'],
        };

        return Stat::make('Horizon', $label)
            ->description("{$pending} job đang chờ · {$failed} job lỗi")
            ->color($color)
            ->icon($icon)
            ->url('/horizon')
            ->openUrlInNewTab();
    }
}
