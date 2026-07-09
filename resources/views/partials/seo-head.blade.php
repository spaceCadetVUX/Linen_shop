{{--
    SEO head — canonical, hreflang, OG/Twitter, JSON-LD.
    Null-safe toàn bộ: trang thiếu biến nào thì thẻ tương ứng không render.

    Biến đọc từ view (mọi controller public đã pass sẵn):
      $seoMeta, $fallbackTitle, $fallbackDescription, $fallbackImage, $ogType,
      $alternateUrls (view()->share), $jsonldSchemas, $businessSchemas (home),
      $canonicalUrl (optional override — PLP/category dùng để bỏ query filter),
      $articleMeta (optional, chỉ dùng khi $ogType === 'article' — array:
      published_time/modified_time/author/section/tags, xem BlogController::show())
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

  // JSON_HEX_TAG/JSON_HEX_AMP bắt buộc — content admin nhập chứa "</script>"
  // sẽ breakout khỏi thẻ script nếu không escape "<" ">" "&". UNESCAPED_SLASHES
  // + PRETTY_PRINT chỉ để dễ đọc khi view-source/debug — không escape "/" thành
  // "\/" và xuống dòng có thụt lề, giống hệt payload preview bên Filament admin
  // (CategoryResource, ProductResource... đều dùng cùng flag này).
  $jsonldFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
      | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
  $allSchemas = array_merge($jsonldSchemas ?? [], $businessSchemas ?? []);
@endphp

<link rel="canonical" href="{{ $canonical }}">
<meta name="robots" content="{{ $seoMeta?->robots ?? 'index,follow' }}">

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

@if(($ogType ?? null) === 'article' && !empty($articleMeta))
  @if(!empty($articleMeta['published_time']))<meta property="article:published_time" content="{{ $articleMeta['published_time'] }}">@endif
  @if(!empty($articleMeta['modified_time']))<meta property="article:modified_time" content="{{ $articleMeta['modified_time'] }}">@endif
  @if(!empty($articleMeta['author']))<meta property="article:author" content="{{ $articleMeta['author'] }}">@endif
  @if(!empty($articleMeta['section']))<meta property="article:section" content="{{ $articleMeta['section'] }}">@endif
  @foreach($articleMeta['tags'] ?? [] as $tag)<meta property="article:tag" content="{{ $tag }}">@endforeach
@endif

<meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
@if($ogTitle)<meta name="twitter:title" content="{{ $ogTitle }}">@endif
@if($ogDescription)<meta name="twitter:description" content="{{ $ogDescription }}">@endif
@if($ogImage)<meta name="twitter:image" content="{{ $ogImage }}">@endif

@foreach($allSchemas as $schema)
  @if(!empty($schema))
<script type="application/ld+json">{!! json_encode($schema, $jsonldFlags) !!}</script>
  @endif
@endforeach
