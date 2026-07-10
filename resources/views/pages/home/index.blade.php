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
     Desktop/tablet: mỗi nhóm tối đa 3 danh mục render thành 1 .edit-grid-row
     riêng (grid auto-fit) để luôn full-width dù dòng còn lại 1/2/3 mục —
     auto-fit chỉ collapse track rỗng trong CÙNG 1 grid, gộp chung 1 grid
     nhiều dòng sẽ để trống ô lẻ ở dòng cuối khi tổng số mục không chia hết 3.
     Mobile (≤640px): .edit-grid-row {display:contents} để item thoát khỏi
     wrapper, quay lại thành 1 dải flex carousel liên tục như cũ — dots do
     JS build (app.js) từ số lượng .edit-grid-item — xem #editGridDots. -->
  <section class="edit-grid" id="editGrid">
    @foreach(array_chunk($editorialItems, 3) as $row)
    <div class="edit-grid-row">
      @foreach($row as $eg)
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
    </div>
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
       Mỗi danh mục có show_on_landing=true (Category tab General) → 1 hàng
       carousel riêng, thứ tự theo sort_order. Bật/tắt + tiêu đề tổng chỉnh
       trong Landing Page setting (featured_enabled / featured_title). Ẩn hẳn
       khi tắt hoặc không có danh mục nào có sản phẩm. --}}
  @if($featuredEnabled && $featuredCategoryRows->isNotEmpty())
  <section class="shop-section" id="shopSection">
    <div class="shop-header shop-header--featured">
      <h2 class="shop-header-title">{{ $featuredTitle }}</h2>
    </div>

    @foreach($featuredCategoryRows as $row)
      <div class="cat-row" id="catRow{{ $loop->index }}">
        <div class="cat-row-header">
          <h3 class="cat-row-title">{{ $row['title'] }}</h3>
          <a href="{{ $row['url'] }}" class="shop-view-all">{{ $locale === 'vi' ? 'Xem tất cả' : 'View all' }} →</a>
        </div>

        <div class="cat-row-wrap">
          @if($row['products']->count() > 1)
          <button type="button" class="cat-row-nav cat-row-nav--prev" aria-label="{{ $locale === 'vi' ? 'Sản phẩm trước' : 'Previous products' }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          @endif

          <div class="cat-row-track">
            @foreach($row['products'] as $p)
              <div class="cat-row-item">
                <x-product.card :product="$p" />
              </div>
            @endforeach
          </div>

          @if($row['products']->count() > 1)
          <button type="button" class="cat-row-nav cat-row-nav--next" aria-label="{{ $locale === 'vi' ? 'Sản phẩm tiếp' : 'Next products' }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
          @endif
        </div>
      </div>
    @endforeach
  </section>
  @endif

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
