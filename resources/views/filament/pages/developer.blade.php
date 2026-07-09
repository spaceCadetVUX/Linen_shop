<x-filament-panels::page>
    {{--
        Filament v3 ships one prebuilt, pre-purged app.css scoped to its own
        component classes (fi-*) — it does NOT scan this project's Blade files
        for Tailwind utilities (no tailwind.config.js/vite.config.js here), so
        raw utility classes like `bg-gray-50` or `rounded-lg` compile to nothing.
        Plain CSS + Filament's own theme custom properties instead.
    --}}
    <style>
        .dev-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.75rem;
        }
        .dev-tile {
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            background: var(--gray-50);
        }
        html.dark .dev-tile {
            background: color-mix(in oklab, var(--gray-50) 8%, transparent);
        }
        .dev-tile-label {
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray-500);
        }
        .dev-tile-value {
            margin-top: 0.25rem;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--gray-950);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        html.dark .dev-tile-value { color: #fff; }

        .dev-feature-list { display: flex; flex-direction: column; gap: 1rem; }
        .dev-feature-card {
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            padding: 1rem;
        }
        html.dark .dev-feature-card {
            border-color: color-mix(in oklab, #fff 10%, transparent);
        }
        .dev-feature-head {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .dev-feature-title { font-size: 0.875rem; font-weight: 600; color: var(--gray-950); }
        html.dark .dev-feature-title { color: #fff; }
        .dev-feature-desc { margin-top: 0.5rem; font-size: 0.875rem; color: var(--gray-600); }
        html.dark .dev-feature-desc { color: var(--gray-300); }
        .dev-feature-detail { margin-top: 0.5rem; font-size: 0.75rem; line-height: 1.55; color: var(--gray-500); }
        html.dark .dev-feature-detail { color: var(--gray-400); }
        .dev-feature-tags { margin-top: 0.75rem; display: flex; flex-wrap: wrap; gap: 0.375rem; }
        .dev-feature-files {
            margin-top: 0.75rem;
            border-top: 1px solid var(--gray-100);
            padding-top: 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        html.dark .dev-feature-files { border-color: color-mix(in oklab, #fff 6%, transparent); }
        .dev-feature-file { font-family: ui-monospace, monospace; font-size: 0.6875rem; color: var(--gray-500); }
        html.dark .dev-feature-file { color: var(--gray-400); }
    </style>

    <div style="display:flex; flex-direction:column; gap:1.5rem;">

        {{-- ── Robots.txt ─────────────────────────────────────────────────── --}}
        <form wire:submit.prevent="saveRobots">
            {{ $this->form }}
        </form>

        {{-- ── Tech stack ─────────────────────────────────────────────────── --}}
        <x-filament::section>
            <x-slot name="heading">Tech stack</x-slot>

            <div class="dev-grid">
                @foreach ($this->getStack() as $label => $value)
                    <div class="dev-tile">
                        <p class="dev-tile-label">{{ $label }}</p>
                        <p class="dev-tile-value">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- ── System info ────────────────────────────────────────────────── --}}
        <x-filament::section>
            <x-slot name="heading">System info</x-slot>

            <div class="dev-grid">
                @foreach ($this->getSystemInfo() as $label => $value)
                    <div class="dev-tile">
                        <p class="dev-tile-label">{{ $label }}</p>
                        <p class="dev-tile-value" title="{{ $value }}">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- ── Features shipped — newest first ────────────────────────────── --}}
        <x-filament::section>
            <x-slot name="heading">Tính năng đã triển khai</x-slot>
            <x-slot name="description">Danh sách theo thứ tự mới nhất trước — mỗi mục ghi rõ công nghệ đứng sau và file liên quan.</x-slot>

            <div class="dev-feature-list">
                @forelse ($this->getFeatures() as $feature)
                    <div class="dev-feature-card">
                        <div class="dev-feature-head">
                            <h3 class="dev-feature-title">{{ $feature['title'] }}</h3>

                            <x-filament::badge
                                :color="match ($feature['status']) {
                                    'done' => 'success',
                                    'in_progress' => 'warning',
                                    'planned' => 'gray',
                                    default => 'gray',
                                }"
                            >
                                {{ match ($feature['status']) {
                                    'done' => 'Đã xong',
                                    'in_progress' => 'Đang làm',
                                    'planned' => 'Dự kiến',
                                    default => $feature['status'],
                                } }}
                            </x-filament::badge>
                        </div>

                        <p class="dev-feature-desc">{{ $feature['description'] }}</p>

                        @if (! empty($feature['detail']))
                            <p class="dev-feature-detail">{{ $feature['detail'] }}</p>
                        @endif

                        @if (! empty($feature['tech']))
                            <div class="dev-feature-tags">
                                @foreach ($feature['tech'] as $tech)
                                    <x-filament::badge color="primary" size="sm">{{ $tech }}</x-filament::badge>
                                @endforeach
                            </div>
                        @endif

                        @if (! empty($feature['files']))
                            <div class="dev-feature-files">
                                @foreach ($feature['files'] as $file)
                                    <span class="dev-feature-file">{{ $file }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <p style="font-size:0.875rem;color:var(--gray-500);">Chưa có mục nào.</p>
                @endforelse
            </div>
        </x-filament::section>

    </div>
</x-filament-panels::page>
