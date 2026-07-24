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

        {{-- ── MCP integration guide ──────────────────────────────────────── --}}
        @php $mcp = $this->getMcpConfig(); @endphp
        <x-filament::section>
            <x-slot name="heading">Tích hợp MCP vào Claude Desktop</x-slot>
            <x-slot name="description">Cho phép Claude Desktop đọc/ghi content (product, category, blog...) qua bộ 31 MCP tool. Chỉ admin thấy được trang này.</x-slot>

            <div style="display:flex; flex-direction:column; gap:1rem;">

                {{-- URL --}}
                <div x-data="{ copied: false }">
                    <p class="dev-tile-label">MCP Server URL</p>
                    <div style="display:flex; align-items:center; gap:0.5rem; margin-top:0.25rem;">
                        <code style="font-family:ui-monospace,monospace; font-size:0.8125rem; background:var(--gray-50); padding:0.375rem 0.625rem; border-radius:0.375rem;">{{ $mcp['url'] }}</code>
                        <x-filament::button
                            size="xs"
                            color="gray"
                            x-on:click="navigator.clipboard.writeText('{{ $mcp['url'] }}'); copied = true; setTimeout(() => copied = false, 1500)"
                        >
                            <span x-show="!copied">Copy</span>
                            <span x-show="copied" style="display:none;">Đã copy!</span>
                        </x-filament::button>
                    </div>
                </div>

                {{-- API key --}}
                <div x-data="{ show: false, copied: false }">
                    <p class="dev-tile-label">MCP_API_KEY (X-Api-Key header)</p>
                    <div style="display:flex; align-items:center; gap:0.5rem; margin-top:0.25rem;">
                        <code
                            style="font-family:ui-monospace,monospace; font-size:0.8125rem; background:var(--gray-50); padding:0.375rem 0.625rem; border-radius:0.375rem;"
                            x-text="show ? '{{ $mcp['api_key'] }}' : '••••••••••••••••••••••••••••••••••••'"
                        ></code>
                        <x-filament::button size="xs" color="gray" x-on:click="show = !show">
                            <span x-text="show ? 'Ẩn' : 'Hiện'"></span>
                        </x-filament::button>
                        <x-filament::button
                            size="xs"
                            color="gray"
                            x-on:click="navigator.clipboard.writeText('{{ $mcp['api_key'] }}'); copied = true; setTimeout(() => copied = false, 1500)"
                        >
                            <span x-show="!copied">Copy</span>
                            <span x-show="copied" style="display:none;">Đã copy!</span>
                        </x-filament::button>
                    </div>
                    <p class="dev-feature-detail">Key dùng chung cho mọi máy tích hợp — không phải token cá nhân. Ai cầm key này gọi được cả tool ghi/publish (sửa product, blog... thật trên site), không chỉ đọc. Không chia sẻ ra ngoài công ty.</p>
                </div>

                {{-- Cách 1 --}}
                <div>
                    <p class="dev-feature-title">Cách 1 — Custom Connector (không cần cài gì thêm)</p>
                    <p class="dev-feature-desc">Claude Desktop → Settings → Connectors → Browse connectors → Add custom connector. Điền URL ở trên, mở mục "Request headers", thêm header <code>x-api-key</code> = key ở trên. Lưu ý: tính năng này đang beta, có máy chưa thấy mục "Request headers" — dùng Cách 2 nếu vậy.</p>
                </div>

                {{-- Cách 2 --}}
                <div>
                    <p class="dev-feature-title">Cách 2 — File claude_desktop_config.json (cần Node.js)</p>
                    <p class="dev-feature-desc">Windows: <code>%APPDATA%\Claude\claude_desktop_config.json</code> — Mac: <code>~/Library/Application Support/Claude/claude_desktop_config.json</code></p>

                    <div x-data="{ copied: false }" style="position:relative; margin-top:0.5rem;">
                        <pre style="font-family:ui-monospace,monospace; font-size:0.75rem; line-height:1.5; background:var(--gray-50); padding:0.75rem; border-radius:0.5rem; overflow-x:auto;">{{ $this->getMcpConfigJson() }}</pre>
                        <x-filament::button
                            size="xs"
                            color="gray"
                            style="position:absolute; top:0.5rem; right:0.5rem;"
                            x-on:click="navigator.clipboard.writeText(`{{ $this->getMcpConfigJson() }}`); copied = true; setTimeout(() => copied = false, 1500)"
                        >
                            <span x-show="!copied">Copy JSON</span>
                            <span x-show="copied" style="display:none;">Đã copy!</span>
                        </x-filament::button>
                    </div>

                    <p class="dev-feature-detail">Nếu PATH không nhận lệnh <code>npx</code> trực tiếp (thường gặp trên Windows), đổi <code>"command": "npx"</code> thành đường dẫn đầy đủ tới <code>npx.cmd</code>, ví dụ <code>C:\\PROGRA~1\\nodejs\\npx.cmd</code>. Sau khi sửa file, tắt hẳn và mở lại Claude Desktop để nó đọc config mới.</p>
                </div>

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
