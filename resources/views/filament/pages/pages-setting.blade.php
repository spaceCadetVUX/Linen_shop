<x-filament-panels::page>
    {{--
        Filament ships one prebuilt, pre-purged app.css scoped to its own
        fi-* classes — raw Tailwind utilities in project Blade files compile
        to nothing. Plain CSS + Filament theme custom properties instead
        (same approach as developer.blade.php).
    --}}
    <style>
        .pgs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1rem;
        }
        .pgs-card {
            display: flex;
            gap: 0.875rem;
            align-items: flex-start;
            padding: 1.125rem;
            border: 1px solid var(--gray-200);
            border-radius: 0.75rem;
            background: #fff;
            text-decoration: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
        }
        html.dark .pgs-card {
            background: color-mix(in oklab, var(--gray-50) 5%, transparent);
            border-color: color-mix(in oklab, #fff 10%, transparent);
        }
        .pgs-card:hover {
            border-color: var(--primary-400);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            transform: translateY(-1px);
        }
        .pgs-card-icon {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.5rem;
            background: color-mix(in oklab, var(--primary-500) 10%, transparent);
            color: var(--primary-600);
        }
        html.dark .pgs-card-icon { color: var(--primary-400); }
        .pgs-card-icon svg { width: 1.25rem; height: 1.25rem; }
        .pgs-card-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-950);
        }
        html.dark .pgs-card-label { color: #fff; }
        .pgs-card-desc {
            margin-top: 0.25rem;
            font-size: 0.8125rem;
            line-height: 1.45;
            color: var(--gray-500);
        }
        html.dark .pgs-card-desc { color: var(--gray-400); }
    </style>

    <div class="pgs-grid">
        @foreach($this->getCards() as $card)
            <a href="{{ $card['url'] }}" class="pgs-card"
               @if($card['newTab'] ?? false) target="_blank" rel="noopener" @endif>
                <span class="pgs-card-icon">
                    <x-filament::icon :icon="$card['icon']" />
                </span>
                <span>
                    <span class="pgs-card-label">{{ $card['label'] }}</span>
                    <span class="pgs-card-desc" style="display:block">{{ $card['description'] }}</span>
                </span>
            </a>
        @endforeach
    </div>
</x-filament-panels::page>
