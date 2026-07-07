@extends('layouts.app')

@section('title', ($seoMeta?->meta_title ?? $fallbackTitle))
@section('meta-description', $seoMeta?->meta_description ?? $fallbackDescription)
@section('body-class', 'page-pd')

@php
    // Primary category (+ its ancestor chain) — same resolution as JsonldService::buildProductBreadcrumb
    // so the visible breadcrumb never disagrees with the BreadcrumbList JSON-LD.
    $primaryCategory = $product->resolvePrimaryCategory();
    $categoryChain   = $primaryCategory?->ancestorChain() ?? [];
    $catT            = $primaryCategory?->translations->firstWhere('locale', $locale);

    // Price — same logic as <x-product.card>: translation may override, sale shown when lower
    $price        = $translation->price ?? $product->price;
    $salePriceRaw = $translation->sale_price ?? $product->sale_price;
    $priceLabel   = number_format($price, 0, ',', '.') . ' ₫';
    $salePrice    = ($salePriceRaw && $salePriceRaw < $price)
                      ? number_format($salePriceRaw, 0, ',', '.') . ' ₫'
                      : null;
@endphp

@section('content')

{{-- ============================================================
     BREADCRUMB
     ============================================================ --}}
<nav class="pd-breadcrumb" aria-label="Breadcrumb">
  <div class="pd-breadcrumb-inner">
    <a href="{{ route($locale . '.index') }}">{{ $locale === 'vi' ? 'Trang chủ' : 'Home' }}</a>
    <span class="pd-bc-sep" aria-hidden="true">/</span>
    <a href="{{ route($locale . '.product.shop') }}">{{ $locale === 'vi' ? 'Cửa hàng' : 'Shop' }}</a>
    @foreach($categoryChain as $chainCategory)
      @php
        $chainCatT = $chainCategory->translations->firstWhere('locale', $locale);
      @endphp
      @continue(!$chainCatT)
      <span class="pd-bc-sep" aria-hidden="true">/</span>
      <a href="{{ \App\Support\LocaleUrl::for('category', $chainCatT->slug, $locale) }}">{{ $chainCatT->name }}</a>
    @endforeach
    <span class="pd-bc-sep" aria-hidden="true">/</span>
    <span aria-current="page">{{ $translation->name }}</span>
  </div>
</nav>

{{-- ============================================================
     PRODUCT DETAIL
     JS in app.js handles: accordion toggle, swatch selection
     (updates #pdColorLabel via data-color), size selection,
     wishlist toggle (#pdWishBtn), add to cart (#pdAddBtn).
     ============================================================ --}}
<section class="pd-section" aria-label="Thông tin sản phẩm">
  <div class="pd-layout">

    {{-- Gallery: vertical strip — JS handles mobile swipe + dot sync --}}
    <div class="pd-gallery" id="pdGallery">

      @forelse($product->images as $image)
        <div class="pd-gimg-wrap" data-image-id="{{ $image->id }}">
          @if($loop->first && $salePrice && $product->show_price)
            <div class="pd-img-badge"><span class="badge badge-muted">Sale</span></div>
          @endif
          <img
            src="{{ $image->url }}"
            alt="{{ $image->alt_text ?: $translation->name }}"
            loading="{{ $loop->first ? 'eager' : 'lazy' }}"
          >
        </div>
      @empty
        <div class="pd-gimg-wrap">
          <img
            src="{{ asset('assets/img/placeholder-category.jpg') }}"
            alt="{{ $translation->name }}"
            loading="eager"
          >
        </div>
      @endforelse

    </div>{{-- /.pd-gallery --}}

    {{-- Mobile swipe dots — count must match gallery images above --}}
    <div class="pd-gallery-dots" id="pdGalleryDots" aria-hidden="true">
      @for($i = 0; $i < max($product->images->count(), 1); $i++)
        <span class="pd-gallery-dot{{ $i === 0 ? ' active' : '' }}"></span>
      @endfor
    </div>

    {{-- Info panel --}}
    <div class="pd-info">
      <div class="pd-info-inner" id="pdInfoInner">

        <p class="pd-eyebrow">{{ $catT?->name ?? 'CacyLinen' }}</p>

        <div class="pd-title-row">
          <h1 class="pd-title">{{ $translation->name }}</h1>
          <button class="pd-wish-btn" id="pdWishBtn" type="button" aria-label="Thêm vào yêu thích" aria-pressed="false">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
          </button>
        </div>

        @if($product->show_price)
          <div class="pd-price-row">
            @if($salePrice)
              <span class="pd-price" id="pdPrice"><span class="t-price-old">{{ $priceLabel }}</span> {{ $salePrice }}</span>
            @else
              <span class="pd-price" id="pdPrice">{{ $priceLabel }}</span>
            @endif
          </div>
        @endif

        @if($translation->short_description)
          <p class="pd-desc">{{ $translation->short_description }}</p>
        @endif

        @if(!empty($optionTypesData))
          @php
            $colorGroup  = collect($optionTypesData)->firstWhere('is_color', true);
            $otherGroups = collect($optionTypesData)->reject(fn ($g) => $g['is_color']);
          @endphp

          {{-- Data island read by app.js to match the selected combination
               against a real ProductVariant and sync price/stock/SKU. --}}
          <script>
            window.__pdVariantData = @js(['optionTypes' => $optionTypesData, 'variants' => $variantsData]);
          </script>

          {{-- Colour — app.js reads data-color to update #pdColorLabel on click --}}
          @if($colorGroup)
            <div class="pd-option-group">
              <div class="pd-option-label">
                <span>{{ $colorGroup['name'] }}</span>
                <span class="pd-color-name" id="pdColorLabel">{{ $colorGroup['values'][0]['value'] ?? '' }}</span>
              </div>
              <div class="pd-swatches" role="radiogroup" aria-label="Chọn {{ $colorGroup['name'] }}">
                @foreach($colorGroup['values'] as $i => $value)
                  <button
                    class="pd-swatch{{ $i === 0 ? ' active' : '' }}"
                    style="background:{{ $value['color_hex'] ?: '#e5e5e5' }}"
                    data-type-id="{{ $colorGroup['id'] }}"
                    data-value-id="{{ $value['id'] }}"
                    data-color="{{ $value['value'] }}"
                    role="radio"
                    aria-checked="{{ $i === 0 ? 'true' : 'false' }}"
                    aria-label="{{ $value['value'] }}"
                  ></button>
                @endforeach
              </div>
            </div>
          @endif

          {{-- Other dimensions (e.g. Size) — same button style as before, now data-driven --}}
          @foreach($otherGroups as $group)
            <div class="pd-option-group">
              <div class="pd-option-label">
                <span>{{ $group['name'] }}</span>
                @if($loop->first)
                  @if($sizeGuide)
                    <button type="button" class="pd-size-guide" data-sg-open>{{ $locale === 'vi' ? 'Hướng dẫn chọn size' : 'Size guide' }} →</button>
                  @else
                    <a href="{{ route($locale . '.size-guide') }}" class="pd-size-guide">{{ $locale === 'vi' ? 'Hướng dẫn chọn size' : 'Size guide' }} →</a>
                  @endif
                @endif
              </div>
              <div class="pd-sizes" role="radiogroup" aria-label="Chọn {{ $group['name'] }}">
                @foreach($group['values'] as $i => $value)
                  <button
                    class="pd-size-btn{{ $i === 0 ? ' active' : '' }}"
                    data-type-id="{{ $group['id'] }}"
                    data-value-id="{{ $value['id'] }}"
                    data-size="{{ $value['value'] }}"
                  >{{ $value['value'] }}</button>
                @endforeach
              </div>
            </div>
          @endforeach
        @endif

        {{-- Product has a size guide but no non-color option row to hang the
             link on (simple product / color-only) — show the trigger standalone --}}
        @if($sizeGuide && collect($optionTypesData)->reject(fn ($g) => $g['is_color'])->isEmpty())
          <div class="pd-option-group">
            <button type="button" class="pd-size-guide" data-sg-open>{{ $locale === 'vi' ? 'Hướng dẫn chọn size' : 'Size guide' }} →</button>
          </div>
        @endif

        <div class="pd-actions">
          <input type="hidden" id="pdVariantId" value="">
          <button class="pd-add-btn" id="pdAddBtn" type="button">Thêm vào giỏ hàng</button>
        </div>

        {{-- Detailed info — always visible, clamped to ~2 clear lines with a
             fade from line 3, expandable via "Xem thêm"/"Show more". app.js
             toggles .is-expanded + swaps the toggle label. --}}
        @if($translation->description_html)
          <div class="pd-detail-info" id="pdDetailInfo">
            <div class="pd-detail-info-body" id="pdDetailInfoBody">
              {!! $translation->description_html !!}
            </div>
            <button type="button" class="pd-detail-info-toggle" id="pdDetailInfoToggle" aria-expanded="false">
              {{ $locale === 'vi' ? 'Xem thêm' : 'Show more' }}
            </button>
          </div>
        @endif

        {{-- Accordions — dynamic, managed per-locale in Filament (Product edit
             → Content tab → "Thông tin chi tiết bổ sung"). app.js toggles
             aria-expanded + animates height via .pd-acc-body max-height. --}}
        @if(!empty($translation->info_sections_html))
          <div class="pd-accordions">
            @foreach($translation->info_sections_html as $section)
              <div class="pd-accordion">
                <button class="pd-acc-trigger" aria-expanded="false" type="button">
                  <span>{{ $section['title'] }}</span>
                  <span class="pd-acc-icon" aria-hidden="true">+</span>
                </button>
                <div class="pd-acc-body">
                  {!! $section['html'] !!}
                </div>
              </div>
            @endforeach
          </div>{{-- /.pd-accordions --}}
        @endif

      </div>{{-- /.pd-info-inner --}}
    </div>{{-- /.pd-info --}}

  </div>{{-- /.pd-layout --}}
</section>

{{-- ============================================================
     SIZE GUIDE MODAL — opened by [data-sg-open] buttons above.
     app.js handles open/close (overlay click, ×, Escape).
     ============================================================ --}}
@if($sizeGuide)
  <div class="pd-sg-overlay" id="pdSizeGuideModal" hidden>
    <div class="pd-sg-modal" role="dialog" aria-modal="true" aria-labelledby="pdSgTitle">
      <button type="button" class="pd-sg-close" data-sg-close aria-label="{{ $locale === 'vi' ? 'Đóng' : 'Close' }}">&times;</button>
      <h2 class="pd-sg-title" id="pdSgTitle">{{ $sizeGuide['name'] }}</h2>
      <div class="pd-sg-body">{!! $sizeGuide['body'] !!}</div>
      <div class="pd-sg-footer">
        <a href="{{ route($locale . '.size-guide') }}" class="pd-size-guide">
          {{ $locale === 'vi' ? 'Xem trang hướng dẫn đầy đủ' : 'View the full size guide' }} →
        </a>
      </div>
    </div>
  </div>
@endif

{{-- ============================================================
     RELATED PRODUCTS — same first category, max 8 (from controller).
     Uses .pd-related (PDP-specific CSS), cards via <x-product.card>.
     Hidden entirely when the product has no related items.
     ============================================================ --}}
@if($relatedProducts->isNotEmpty())
<section class="pd-related shop-section" id="pdRelated">
  <div class="pd-related-header">
    <div>
      <p class="pd-related-eyebrow">{{ $locale === 'vi' ? 'Có thể bạn thích' : 'You may also like' }}</p>
      <h2 class="pd-related-title">{{ $locale === 'vi' ? 'Sản phẩm liên quan' : 'Related products' }}</h2>
    </div>
    <a href="{{ route($locale . '.product.shop') }}" class="pd-view-all">{{ $locale === 'vi' ? 'Xem tất cả' : 'View all' }} <span>→</span></a>
  </div>

  <div class="pd-related-grid">
    @foreach($relatedProducts as $related)
      <x-product.card :product="$related" />
    @endforeach
  </div>
</section>
@endif

{{-- ============================================================
     JOURNAL — 4 bài viết mới nhất ($latestBlogs, cùng shape với
     homepage). Ẩn hẳn khi chưa có bài published.
     ============================================================ --}}
@if($latestBlogs->isNotEmpty())
<section class="journal-section" id="journalSection">
  <div class="journal-header">
    <p class="journal-eyebrow">{{ $locale === 'vi' ? 'Nhật ký thời trang' : 'Fashion journal' }}</p>
    <h2 class="journal-title">JOURNAL</h2>
    <a href="{{ route($locale . '.blog.index') }}" class="journal-view-all">{{ $locale === 'vi' ? 'Xem tất cả' : 'View all' }} <span class="journal-view-all-arrow">→</span></a>
  </div>

  <div class="journal-grid">
    @foreach($latestBlogs as $post)
      <x-blog.card :post="$post" :locale="$locale" />
    @endforeach
  </div>
</section>
@endif

@endsection
