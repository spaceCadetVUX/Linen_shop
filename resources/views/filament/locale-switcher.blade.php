@php
    $currentLocale = app()->getLocale();
@endphp

<div class="flex items-center gap-x-1 px-2">
    @foreach (config('app.supported_locales') as $locale)
        <a
            href="{{ route('filament.admin.locale.switch', ['locale' => $locale]) }}"
            @class([
                'fi-btn fi-color-gray rounded-md px-2 py-1 text-xs font-semibold uppercase transition-colors',
                'bg-primary-600 text-white' => $locale === $currentLocale,
                'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' => $locale !== $currentLocale,
            ])
        >
            {{ $locale }}
        </a>
    @endforeach
</div>
