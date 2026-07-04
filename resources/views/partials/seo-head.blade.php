{{--
    SEO head — canonical, hreflang, OG/Twitter, JSON-LD.
    Null-safe toàn bộ: trang thiếu biến nào thì thẻ tương ứng không render.

    Biến đọc từ view (mọi controller public đã pass sẵn):
      $seoMeta, $fallbackTitle, $fallbackDescription, $fallbackImage, $ogType,
      $alternateUrls (view()->share), $jsonldSchemas, $businessSchemas (home),
      $canonicalUrl (optional override — PLP/category dùng để bỏ query filter)
--}}
@php
  $seoMeta = $seoMeta ?? null;
  $pageLocale = $locale ?? app()->getLocale();

  $headTitle = $seoMeta?->meta_title ?? ($fallbackTitle ?? null);
  $headDescription = $seoMeta?->meta_description ?? ($fallbackDescription ?? null);

  $ogTitle = $seoMeta?->og_title ?? $headTitle;
  $ogDescription = $seoMeta?->og_description ?? $headDescription;
  $ogImageRaw = $seoMeta?->og_image ?? ($fallbackImage ?? null);
  $ogImage = $ogImageRaw
      ? (str_starts_with($ogImageRaw, 'http') ? $ogImageRaw : asset('storage/'.ltrim($ogImageRaw, '/')))
      : null;

  $canonical = $canonicalUrl ?? url()->current();
  $siteName = \App\Models\Setting::get('site_name');

  // JSON_HEX_TAG bắt buộc — content admin nhập chứa "</script>" sẽ breakout
  // khỏi thẻ script nếu không escape "<" ">".
  $jsonldFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP;
  $allSchemas = array_merge($jsonldSchemas ?? [], $businessSchemas ?? []);
@endphp

<link rel="canonical" href="{{ $canonical }}">

@if(!empty($alternateUrls))
  @foreach($alternateUrls as $hreflang => $url)
    <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $url }}">
  @endforeach
  <link rel="alternate" hreflang="x-default" href="{{ $alternateUrls['vi'] ?? reset($alternateUrls) }}">
@endif

@if($ogTitle)<meta property="og:title" content="{{ $ogTitle }}">@endif
@if($ogDescription)<meta property="og:description" content="{{ $ogDescription }}">@endif
@if($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif
<meta property="og:type" content="{{ $ogType ?? 'website' }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:locale" content="{{ $pageLocale === 'en' ? 'en_US' : 'vi_VN' }}">
@if($siteName)<meta property="og:site_name" content="{{ $siteName }}">@endif

<meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
@if($ogTitle)<meta name="twitter:title" content="{{ $ogTitle }}">@endif
@if($ogDescription)<meta name="twitter:description" content="{{ $ogDescription }}">@endif
@if($ogImage)<meta name="twitter:image" content="{{ $ogImage }}">@endif

@foreach($allSchemas as $schema)
  @if(!empty($schema))
<script type="application/ld+json">{!! json_encode($schema, $jsonldFlags) !!}</script>
  @endif
@endforeach
