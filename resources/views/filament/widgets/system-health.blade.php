<x-filament-widgets::widget>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach ($this->getServices() as $service)
            <x-filament::section>
                <div class="flex items-start gap-4">
                    <div
                        @class([
                            'flex h-11 w-11 shrink-0 items-center justify-center rounded-lg',
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
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                                {{ $service['name'] }}
                            </h3>

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

                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            {{ $service['description'] }}
                        </p>

                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                            {{ $service['metric'] }}
                        </p>

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
                    </div>
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-widgets::widget>
