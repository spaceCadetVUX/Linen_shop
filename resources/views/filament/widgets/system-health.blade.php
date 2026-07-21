<x-filament-widgets::widget>
    {{--
        Filament ships one prebuilt app.css scoped to its own fi-* component
        classes — no tailwind.config.js/vite.config.js in this repo scans this
        file, so raw utility classes (`grid-cols-2`, `bg-success-50`, `h-6 w-6`...)
        compile to nothing and silently render unstyled. Plain CSS + Filament's
        own --success-*/--gray-* custom properties instead — same convention
        already used in resources/views/filament/pages/developer.blade.php.
    --}}
    <style>
        .shw-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
        }

        .shw-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .shw-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .shw-icon-wrap {
            position: relative;
            display: flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.75rem;
        }

        .shw-icon-wrap.is-online { background: var(--success-50); }
        .shw-icon-wrap.is-degraded { background: var(--warning-50); }
        .shw-icon-wrap.is-offline { background: var(--danger-50); }
        html.dark .shw-icon-wrap.is-online { background: color-mix(in oklab, var(--success-400) 10%, transparent); }
        html.dark .shw-icon-wrap.is-degraded { background: color-mix(in oklab, var(--warning-400) 10%, transparent); }
        html.dark .shw-icon-wrap.is-offline { background: color-mix(in oklab, var(--danger-400) 10%, transparent); }

        .shw-icon { width: 1.5rem; height: 1.5rem; }
        .shw-icon-wrap.is-online .shw-icon { color: var(--success-600); }
        .shw-icon-wrap.is-degraded .shw-icon { color: var(--warning-600); }
        .shw-icon-wrap.is-offline .shw-icon { color: var(--danger-600); }
        html.dark .shw-icon-wrap.is-online .shw-icon { color: var(--success-400); }
        html.dark .shw-icon-wrap.is-degraded .shw-icon { color: var(--warning-400); }
        html.dark .shw-icon-wrap.is-offline .shw-icon { color: var(--danger-400); }

        .shw-dot-wrap {
            position: absolute;
            top: -0.125rem;
            right: -0.125rem;
            display: flex;
            width: 0.75rem;
            height: 0.75rem;
        }
        .shw-dot-ping {
            position: absolute;
            inset: 0;
            border-radius: 9999px;
            background: var(--success-400);
            opacity: 0.75;
            animation: shw-ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
        }
        .shw-dot {
            position: relative;
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 9999px;
            background: var(--success-500);
            box-shadow: 0 0 0 2px #fff;
        }
        html.dark .shw-dot { box-shadow: 0 0 0 2px var(--gray-900); }
        @keyframes shw-ping {
            75%, 100% { transform: scale(2); opacity: 0; }
        }

        .shw-name { font-size: 0.875rem; font-weight: 600; color: var(--gray-950); }
        html.dark .shw-name { color: #fff; }
        .shw-desc { font-size: 0.75rem; color: var(--gray-500); }
        html.dark .shw-desc { color: var(--gray-400); }

        .shw-stat-row {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            background: var(--gray-50);
        }
        html.dark .shw-stat-row { background: color-mix(in oklab, #fff 5%, transparent); }

        .shw-headline { font-size: 1.5rem; font-weight: 700; line-height: 1; color: var(--gray-950); font-variant-numeric: tabular-nums; }
        html.dark .shw-headline { color: #fff; }
        .shw-headline-label { margin-top: 0.375rem; font-size: 0.75rem; color: var(--gray-500); }
        html.dark .shw-headline-label { color: var(--gray-400); }

        .shw-stats { display: flex; flex-shrink: 0; gap: 0.5rem; }
        .shw-stat-pill { border-radius: 0.375rem; padding: 0.375rem 0.75rem; text-align: center; background: var(--gray-100); }
        .shw-stat-pill.is-danger { background: var(--danger-50); }
        html.dark .shw-stat-pill { background: color-mix(in oklab, #fff 10%, transparent); }
        html.dark .shw-stat-pill.is-danger { background: color-mix(in oklab, var(--danger-400) 10%, transparent); }

        .shw-stat-value { font-size: 0.875rem; font-weight: 600; color: var(--gray-700); font-variant-numeric: tabular-nums; }
        .shw-stat-pill.is-danger .shw-stat-value { color: var(--danger-600); }
        html.dark .shw-stat-value { color: var(--gray-300); }
        html.dark .shw-stat-pill.is-danger .shw-stat-value { color: var(--danger-400); }
        .shw-stat-label { margin-top: 0.125rem; font-size: 0.625rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--gray-500); }
        html.dark .shw-stat-label { color: var(--gray-400); }

        .shw-link {
            margin-top: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--primary-600);
            text-decoration: none;
        }
        .shw-link:hover { color: var(--primary-500); }
        html.dark .shw-link { color: var(--primary-400); }
        .shw-link-icon { width: 0.75rem; height: 0.75rem; }
    </style>

    <div class="shw-grid">
        @foreach ($this->getServices() as $service)
            <x-filament::section>
                <div class="shw-head">
                    <div class="shw-left">
                        <span class="shw-icon-wrap is-{{ $service['status'] }}">
                            <x-filament::icon :icon="$service['icon']" class="shw-icon" />

                            @if ($service['status'] === 'online')
                                <span class="shw-dot-wrap">
                                    <span class="shw-dot-ping"></span>
                                    <span class="shw-dot"></span>
                                </span>
                            @endif
                        </span>

                        <div>
                            <h3 class="shw-name">{{ $service['name'] }}</h3>
                            <p class="shw-desc">{{ $service['description'] }}</p>
                        </div>
                    </div>

                    <x-filament::badge
                        :color="match ($service['status']) {
                            'online' => 'success',
                            'degraded' => 'warning',
                            'offline' => 'danger',
                        }"
                    >
                        {{ $service['statusLabel'] }}
                    </x-filament::badge>
                </div>

                <div class="shw-stat-row">
                    <div style="min-width: 0;">
                        <p class="shw-headline">{{ $service['headline'] }}</p>
                        <p class="shw-headline-label">{{ $service['headlineLabel'] }}</p>
                    </div>

                    @if (! empty($service['stats']))
                        <div class="shw-stats">
                            @foreach ($service['stats'] as $stat)
                                <div class="shw-stat-pill @if ($stat['tone'] === 'danger') is-danger @endif">
                                    <p class="shw-stat-value">{{ $stat['value'] }}</p>
                                    <p class="shw-stat-label">{{ $stat['label'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($service['url'])
                    <a href="{{ $service['url'] }}" target="_blank" rel="noopener noreferrer" class="shw-link">
                        Xem chi tiết
                        <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="shw-link-icon" />
                    </a>
                @endif
            </x-filament::section>
        @endforeach
    </div>
</x-filament-widgets::widget>
