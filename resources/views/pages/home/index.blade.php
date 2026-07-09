@extends('layouts.app')

@section('title', $fallbackTitle)
@section('meta-description', $fallbackDescription)

{{-- No body-class → navbar stays transparent on homepage --}}

@section('content')

{{-- ============================================================
     HERO
     hero-logo-wrap: JS shrinks this on scroll, nav-logo fades in.
     hero-overlay: dark gradient for caption legibility.
     Replace image src + caption copy when CMS is wired up.
     ============================================================ --}}
<section class="hero" id="hero">
  <div class="hero-bg">
    @if($heroImageUrl)
      <img
        src="{{ $heroImageUrl }}"
        alt="{{ $heroHeadline ?? 'CacyLinen - Bộ sưu tập Thu 2026' }}"
        class="hero-img"
      >
    @else
      <img
        src="https://wonder-theme-fashion.myshopify.com/cdn/shop/files/5-velour-main-banner-image-desktop.jpg?v=1754861835&width=1920"
        alt="CacyLinen - Bộ sưu tập Thu 2026"
        class="hero-img"
      >
    @endif
  </div>
  <div class="hero-overlay"></div>

  <div class="hero-logo-wrap" aria-hidden="true">
    <span class="hero-logo-text">CacyLinen</span>
  </div>

  <div class="hero-caption">
    @if($heroEyebrow)
      <p class="hero-eyebrow">{{ $heroEyebrow }}</p>
    @endif
    @if($heroHeadline)
      <h1 class="hero-title">{{ $heroHeadline }}</h1>
    @endif
    <div class="hero-links">
      @if($heroCtaLabel && $heroCtaUrl)
        <a href="{{ url($heroCtaUrl) }}" class="hero-link">{{ $heroCtaLabel }}</a>
      @endif
      @if($heroCtaLabel2 && $heroCtaUrl2)
        <span class="hero-link-sep"></span>
        <a href="{{ url($heroCtaUrl2) }}" class="hero-link">{{ $heroCtaLabel2 }}</a>
      @endif
    </div>
  </div>
</section>

<!-- ==============================  EDITORIAL GRID  ==============================
     Mobile (≤640px): carousel vuốt ngang 100vh/danh mục, dots do JS build
     (app.js) từ số lượng .edit-grid-item — xem #editGridDots dưới đây. -->
  <section class="edit-grid" id="editGrid">
    @foreach($editorialItems as $eg)
    <a href="{{ $eg['url'] }}" class="edit-grid-item">
      @if($eg['image_url'])
        <div class="edit-grid-img" style="background-image:url('{{ $eg['image_url'] }}')"></div>
      @else
        <div class="edit-grid-img {{ $eg['fallback_class'] }}"></div>
      @endif
      <div class="edit-grid-label">
        <span class="edit-grid-name">{{ $eg['name'] }}</span>
        <span class="edit-grid-cta">{{ $eg['cta'] }}</span>
      </div>
    </a>
    @endforeach

    @if(count($editorialItems) > 1)
      <button type="button" class="edit-grid-nav edit-grid-nav--prev" id="editGridPrev" aria-label="Danh mục trước">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <button type="button" class="edit-grid-nav edit-grid-nav--next" id="editGridNext" aria-label="Danh mục tiếp">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    @endif
  </section>
  <div class="edit-grid-dots" id="editGridDots" aria-hidden="true"></div>

  {{-- ==============================  PROMOTIONS  ==============================
       Campaign khuyến mãi đang chạy — quản lý qua PromotionResource (Filament).
       Tái dùng nguyên pattern .feat-product (ảnh editorial trái | slider sản
       phẩm phải, crossfade + dots + prev/next) đã có sẵn cho section Featured
       Product — mỗi campaign = 1 block. Vị trí banner (trái/phải) do admin
       chọn qua field banner_position, không tự động đảo chiều nữa. --}}
  @if(!empty($promotions))
    @foreach($promotions as $promo)
    <section class="feat-product promo-feat @if($promo['banner_position'] === \App\Enums\PromotionBannerPosition::Right) promo-feat--reverse @endif" id="promoFeat{{ $loop->index }}">
      <div class="feat-product-left">
        @if($promo['banner_image_url'])
          <img src="{{ $promo['banner_image_url'] }}" alt="{{ $promo['title'] }}" class="feat-product-editorial">
        @endif
        <div class="feat-product-overlay">
          @if($promo['ends_at'])
            <p class="feat-product-overlay-eyebrow promo-feat-countdown" data-countdown-end="{{ $promo['ends_at']->toIso8601String() }}"></p>
          @endif
          @if($promo['title'])
            <h2 class="feat-product-overlay-title">{{ $promo['title'] }}</h2>
          @endif
          @if($promo['cta_label'] && $promo['cta_url'])
            <a href="{{ url($promo['cta_url']) }}" class="feat-product-overlay-btn">{{ $promo['cta_label'] }}</a>
          @endif
        </div>
      </div>

      <div class="feat-product-right">
        <div class="feat-slider-wrap">
          @if($promo['products']->count() > 1)
          <button class="feat-nav feat-nav--prev" aria-label="Sản phẩm trước" type="button">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          @endif

          <div class="feat-slider">
            @foreach($promo['products'] as $p)
              <x-product.feat-slide :product="$p" :active="$loop->first" />
            @endforeach
          </div>

          @if($promo['products']->count() > 1)
          <button class="feat-nav feat-nav--next" aria-label="Sản phẩm tiếp" type="button">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
          @endif
        </div>

        <div class="feat-product-caption">
          <a href="{{ \App\Support\LocaleUrl::for('product', $promo['products']->first()->slug, $promo['products']->first()->locale) }}" class="feat-product-caption-link">
            <p class="feat-product-name">{{ $promo['products']->first()?->name }}</p>
            <p class="feat-product-price"></p>
          </a>
          @if($promo['products']->count() > 1)
          <div class="feat-dots">
            @foreach($promo['products'] as $p)
              <span class="feat-dot @if($loop->first) is-active @endif"></span>
            @endforeach
          </div>
          @endif
        </div>
      </div>
    </section>
    @endforeach
  @endif

  <!-- ==============================  BRAND STATEMENT  ============================== -->
  <!-- <section class="brand-stmt">
    <div class="brand-stmt-inner">
      <div class="brand-stmt-text">
        <p class="brand-stmt-body">Chúng tôi thiết kế những trang phục linen tinh tế, xây dựng phong cách sống bền vững, và mang đến những trải nghiệm mặc đẹp mỗi ngày. Bắt nguồn từ chất liệu tự nhiên và được định hướng bởi sự tối giản, CacyLinen không ngừng phát triển theo cách có chủ đích.</p>
        <a href="#" class="brand-stmt-cta">Tìm hiểu về CacyLinen</a>
      </div>
    </div>
  </section> -->



  {{-- ==============================  SHOP GRID  ==============================
       Sản phẩm mới nhất (8) — bật/tắt + tiêu đề chỉnh trong Landing Page
       setting (featured_enabled / featured_title). Ẩn hẳn khi tắt hoặc
       chưa có sản phẩm. --}}
  @if($featuredEnabled && $featuredProducts->isNotEmpty())
  <section class="shop-section" id="shopSection">
    <div class="shop-header shop-header--featured">
      <h2 class="shop-header-title">{{ $featuredTitle }}</h2>
      <a href="{{ route($locale . '.product.shop') }}" class="shop-view-all">{{ $locale === 'vi' ? 'Xem tất cả' : 'View all' }} →</a>
    </div>

    <div class="shop-grid">
      @foreach($featuredProducts as $p)
        <x-product.card :product="$p" />
      @endforeach
    </div>
  </section>
  @endif

  <!-- ==============================  TIKTOK INSPIRATION  ============================== -->
  <!--
    VIDEO INTEGRATION GUIDE (future):
    • Thay <img class="tiktok-img"> bằng <video class="tiktok-video" muted playsinline preload="metadata" poster="thumbnail.jpg">
    • JS đã có sẵn syncVideoPlayback() — chỉ video ở data-pos="0" được play()
    • Dùng IntersectionObserver để auto-play khi section vào viewport, pause khi ra
  -->
  <section class="tiktok-section" id="tiktokSection">
    <div class="tiktok-header">
      <p class="tiktok-eyebrow">Tik Tok</p>
      <h2 class="tiktok-title">Inspiration</h2>
    </div>

    <div class="tiktok-stage">
      <button class="tiktok-arrow tiktok-arrow--prev" aria-label="Trước">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="15 18 9 12 15 6"/></svg>
      </button>

      <div class="tiktok-track">
        <div class="tiktok-item" data-pos="-2">
          <img src="" alt="" class="tiktok-img">
        </div>
        <div class="tiktok-item" data-pos="-1">
          <img src="" alt="" class="tiktok-img">
        </div>
        <div class="tiktok-item" data-pos="0">
          <img src="" alt="" class="tiktok-img">
          <!-- Video controls — visible only when data-pos="0" via CSS -->
          <button class="tiktok-ctrl tiktok-ctrl--play" aria-label="Tạm dừng">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/></svg>
          </button>
          <button class="tiktok-ctrl tiktok-ctrl--mute" aria-label="Tắt tiếng">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>
          </button>
        </div>
        <div class="tiktok-item" data-pos="1">
          <img src="" alt="" class="tiktok-img">
        </div>
        <div class="tiktok-item" data-pos="2">
          <img src="" alt="" class="tiktok-img">
        </div>
      </div>

      <button class="tiktok-arrow tiktok-arrow--next" aria-label="Tiếp">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>

    <div class="tiktok-bottom">
      <div class="tiktok-product-card">
        <img src="" alt="" class="tiktok-product-thumb">
        <div class="tiktok-product-info">
          <span class="tiktok-product-brand"></span>
          <span class="tiktok-product-name"></span>
          <span class="tiktok-product-price"></span>
        </div>
      </div>
      <div class="tiktok-dots">
        <span class="tiktok-dot"></span>
        <span class="tiktok-dot"></span>
        <span class="tiktok-dot"></span>
        <span class="tiktok-dot"></span>
        <span class="tiktok-dot"></span>
      </div>
    </div>
  </section>

  {{-- ==============================  JOURNAL / BLOG  ==============================
       4 bài viết mới nhất ($latestBlogs — HomeController, khớp 4 cột grid).
       Ẩn hẳn khi chưa có bài published. --}}
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
