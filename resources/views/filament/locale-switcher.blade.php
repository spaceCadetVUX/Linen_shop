@php
    $currentLocale = app()->getLocale();
    $locales = config('app.supported_locales');
@endphp

{{--
    Plain inline styles on purpose: this admin panel has no custom Filament
    theme build (no Vite/Tailwind content-scan over resources/views/filament),
    so arbitrary utility classes here (bg-primary-600, etc.) never make it
    into the compiled CSS and silently render as nothing — which is why the
    active state previously looked identical to the inactive one.
--}}
<style>
    .admin-locale-switch {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        margin-inline: 0.75rem;
        padding: 3px;
        border-radius: 8px;
        background-color: rgba(120, 120, 120, 0.12);
    }

    .admin-locale-switch a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2.25rem;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1;
        text-transform: uppercase;
        text-decoration: none;
        color: #6b7280;
        transition: background-color .15s ease, color .15s ease;
    }

    .admin-locale-switch a:hover {
        background-color: rgba(120, 120, 120, 0.18);
        color: #374151;
    }

    .admin-locale-switch a.is-active,
    .admin-locale-switch a.is-active:hover {
        background-color: #2563eb;
        color: #fff;
    }

    @media (prefers-color-scheme: dark) {
        .admin-locale-switch a {
            color: #9ca3af;
        }

        .admin-locale-switch a:hover {
            background-color: rgba(255, 255, 255, 0.08);
            color: #e5e7eb;
        }
    }
</style>

<div class="admin-locale-switch">
    @foreach ($locales as $locale)
        <a
            href="{{ route('filament.admin.locale.switch', ['locale' => $locale]) }}"
            @class(['is-active' => $locale === $currentLocale])
            @if ($locale === $currentLocale) aria-current="true" @endif
        >
            {{ $locale }}
        </a>
    @endforeach
</div>
