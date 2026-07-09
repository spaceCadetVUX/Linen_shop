<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($entries as $entry)
    <url>
        <loc>{{ $entry->url }}</loc>
@foreach (($entry->alternate_urls ?? []) as $hreflang => $altUrl)
        <xhtml:link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $altUrl }}" />
@endforeach
@if ($entry->last_modified)
        <lastmod>{{ $entry->last_modified->toAtomString() }}</lastmod>
@endif
        <changefreq>{{ $entry->changefreq?->value ?? 'weekly' }}</changefreq>
        <priority>{{ $entry->priority ?? '0.8' }}</priority>
    </url>
@endforeach
</urlset>
