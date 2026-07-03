<x-filament-widgets::widget>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        @foreach ($this->getServices() as $service)
            <x-filament::section>
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span
                            @class([
                                'relative flex h-11 w-11 shrink-0 items-center justify-center rounded-xl',
                                'bg-success-50 dark:bg-success-400/10' => $service['status'] === 'online',
                                'bg-warning-50 dark:bg-warning-400/10' => $service['status'] === 'degraded',
                                'bg-danger-50 dark:bg-danger-400/10' => $service['status'] === 'offline',
                            ])
                        >
                            <x-filament::icon
                                :icon="$service['icon']"
                                @class([
                                    'h-6 w-6',
                                    'text-success-600 dark:text-success-400' => $service['status'] === 'online',
                                    'text-warning-600 dark:text-warning-400' => $service['status'] === 'degraded',
                                    'text-danger-600 dark:text-danger-400' => $service['status'] === 'offline',
                                ])
                            />

                            @if ($service['status'] === 'online')
                                <span class="absolute -right-0.5 -top-0.5 flex h-3 w-3">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success-400 opacity-75"></span>
                                    <span class="relative inline-flex h-3 w-3 rounded-full bg-success-500 ring-2 ring-white dark:ring-gray-900"></span>
                                </span>
                            @endif
                        </span>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                                {{ $service['name'] }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $service['description'] }}
                            </p>
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

                <div class="mt-4 flex items-end justify-between gap-4 rounded-lg bg-gray-50 px-4 py-3 dark:bg-white/5">
                    <div class="min-w-0">
                        <p class="text-2xl font-bold tabular-nums leading-none text-gray-950 dark:text-white">
                            {{ $service['headline'] }}
                        </p>
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                            {{ $service['headlineLabel'] }}
                        </p>
                    </div>

                    @if (! empty($service['stats']))
                        <div class="flex shrink-0 gap-2">
                            @foreach ($service['stats'] as $stat)
                                <div
                                    @class([
                                        'rounded-md px-3 py-1.5 text-center',
                                        'bg-danger-50 dark:bg-danger-400/10' => $stat['tone'] === 'danger',
                                        'bg-gray-100 dark:bg-white/10' => $stat['tone'] === 'gray',
                                    ])
                                >
                                    <p
                                        @class([
                                            'text-sm font-semibold tabular-nums',
                                            'text-danger-600 dark:text-danger-400' => $stat['tone'] === 'danger',
                                            'text-gray-700 dark:text-gray-300' => $stat['tone'] === 'gray',
                                        ])
                                    >
                                        {{ $stat['value'] }}
                                    </p>
                                    <p class="text-[10px] uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ $stat['label'] }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($service['url'])
                    <a
                        href="{{ $service['url'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
                    >
                        Xem chi tiết
                        <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-3 w-3" />
                    </a>
                @endif
            </x-filament::section>
        @endforeach
    </div>
</x-filament-widgets::widget>
