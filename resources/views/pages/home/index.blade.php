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
        alt="{{ $heroHeadline ?? 'LINNÉ — Bộ sưu tập Thu 2026' }}"
        class="hero-img"
      >
    @else
      <img
        src="https://wonder-theme-fashion.myshopify.com/cdn/shop/files/5-velour-main-banner-image-desktop.jpg?v=1754861835&width=1920"
        alt="LINNÉ — Bộ sưu tập Thu 2026"
        class="hero-img"
      >
    @endif
  </div>
  <div class="hero-overlay"></div>

  <div class="hero-logo-wrap" aria-hidden="true">
    <span class="hero-logo-text">LINNÉ</span>
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

<!-- ==============================  EDITORIAL GRID  ============================== -->
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
  </section>

  <!-- ==============================  FEATURED PRODUCT  ============================== -->
  <section class="feat-product" id="featProduct">

    <!-- Left: editorial + overlay text -->
    <div class="feat-product-left">
      <img
        src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Camel3.jpg?v=1779070696&width=2160"
        alt="LINNÉ — Bộ sưu tập Thu 2026"
        class="feat-product-editorial"
      >
      <div class="feat-product-overlay">
        <p class="feat-product-overlay-eyebrow">Bộ sưu tập · Thu 2026</p>
        <h2 class="feat-product-overlay-title">BỘ SƯU TẬP<br>MÙA THU</h2>
        <a href="#" class="feat-product-overlay-btn">Khám phá</a>
      </div>
    </div>

    <!-- Right: product slider -->
    <div class="feat-product-right">
      <div class="feat-slider-wrap">
        <button class="feat-nav feat-nav--prev" aria-label="Sản phẩm trước">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><polyline points="15 18 9 12 15 6"/></svg>
        </button>

        <div class="feat-slider">
          <div class="feat-slide is-active" data-name="Đầm linen cổ chữ V" data-price="1.290.000 ₫">
            <img src="https://elleandriley.com/cdn/shop/files/Bandana-Scarf-Sand-Cream3.jpg?v=1781216151&width=1200" alt="Đầm linen cổ chữ V" class="feat-slide-img">
          </div>
          <div class="feat-slide" data-name="Áo blouse thắt nơ" data-price="720.000 ₫">
            <img src="https://elleandriley.com/cdn/shop/files/Bandana-Scarf-Black-Sand_80abcf68-ce45-46c1-8665-8419cb22e876.jpg?v=1781216042&width=1200" alt="Áo blouse thắt nơ" class="feat-slide-img">
          </div>
          <div class="feat-slide" data-name="Áo Cashmere cổ tròn" data-price="890.000 ₫">
            <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Black.jpg?v=1779070489&width=1200" alt="Áo Cashmere cổ tròn" class="feat-slide-img">
          </div>
          <div class="feat-slide" data-name="Áo crop linen" data-price="620.000 ₫">
            <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Red.jpg?v=1778217693&width=1200" alt="Áo crop linen" class="feat-slide-img">
          </div>
        </div>

        <button class="feat-nav feat-nav--next" aria-label="Sản phẩm tiếp">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>

      <!-- Caption: updates via JS -->
      <div class="feat-product-caption">
        <p class="feat-product-name" id="featName">Đầm linen cổ chữ V</p>
        <p class="feat-product-price" id="featPrice">1.290.000 ₫</p>
        <div class="feat-dots">
          <span class="feat-dot is-active"></span>
          <span class="feat-dot"></span>
          <span class="feat-dot"></span>
          <span class="feat-dot"></span>
        </div>
      </div>
    </div>

  </section>

  <!-- ==============================  FEATURED PRODUCT 2 (reversed)  ============================== -->
  <section class="feat-product" id="featProduct2">

    <!-- Left: product slider -->
    <div class="feat-product-right">
      <div class="feat-slider-wrap">
        <button class="feat-nav feat-nav--prev" aria-label="Sản phẩm trước">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><polyline points="15 18 9 12 15 6"/></svg>
        </button>

        <div class="feat-slider">
          <div class="feat-slide is-active" data-name="Váy midi linen" data-price="780.000 ₫">
            <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_BrownMelange2.jpg?v=1778217470&width=1200" alt="Váy midi linen" class="feat-slide-img">
          </div>
          <div class="feat-slide" data-name="Áo linen oversized" data-price="680.000 ₫">
            <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Birch.jpg?v=1778217589&width=1200" alt="Áo linen oversized" class="feat-slide-img">
          </div>
          <div class="feat-slide" data-name="Khăn bandana linen" data-price="320.000 ₫">
            <img src="https://elleandriley.com/cdn/shop/files/Bandana-Scarf-Sand-Cream3.jpg?v=1781216151&width=1200" alt="Khăn bandana linen" class="feat-slide-img">
          </div>
          <div class="feat-slide" data-name="Áo linen xanh nhạt" data-price="640.000 ₫">
            <img src="https://elleandriley.com/cdn/shop/files/Slim_tee_Pale_Blue.jpg?v=1778216637&width=1200" alt="Áo linen xanh nhạt" class="feat-slide-img">
          </div>
        </div>

        <button class="feat-nav feat-nav--next" aria-label="Sản phẩm tiếp">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>

      <div class="feat-product-caption">
        <p class="feat-product-name" id="featName2">Váy midi linen</p>
        <p class="feat-product-price" id="featPrice2">780.000 ₫</p>
        <div class="feat-dots">
          <span class="feat-dot is-active"></span>
          <span class="feat-dot"></span>
          <span class="feat-dot"></span>
          <span class="feat-dot"></span>
        </div>
      </div>
    </div>

    <!-- Right: editorial image -->
    <div class="feat-product-left">
      <img
        src="https://elleandriley.com/cdn/shop/files/Slim_Tee_BrownMelange.jpg?v=1778217470&width=2160"
        alt="LINNÉ — Quần &amp; Váy"
        class="feat-product-editorial"
      >
      <div class="feat-product-overlay">
        <p class="feat-product-overlay-eyebrow">Quần &amp; Váy · Thu 2026</p>
        <h2 class="feat-product-overlay-title">PHONG CÁCH<br>TỐI GIẢN</h2>
        <a href="#" class="feat-product-overlay-btn">Khám phá</a>
      </div>
    </div>

  </section>


      <!-- ==============================  DUAL EDITORIAL  ============================== -->
  <section class="dual-edit" id="dualEdit">
    <div class="dual-edit-item">
      <img
        src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Camel3.jpg?v=1779070696&width=2160"
        alt="Bộ sưu tập yêu thích — ấm áp và thoải mái"
        class="dual-edit-img"
      >
      <div class="dual-edit-overlay"></div>
      <div class="dual-edit-content">
        <p class="dual-edit-eyebrow">Bộ sưu tập yêu thích</p>
        <h2 class="dual-edit-title">Cosy &<br>Comfort</h2>
        <a href="#" class="dual-edit-btn">Khám phá ngay</a>
      </div>
    </div>
    <div class="dual-edit-item">
      <img
        src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Birch.jpg?v=1778217589&width=2160"
        alt="Phong cách tối giản — LINNÉ Thu 2026"
        class="dual-edit-img"
      >
    </div>
  </section>

  <!-- ==============================  BRAND STATEMENT  ============================== -->
  <!-- <section class="brand-stmt">
    <div class="brand-stmt-inner">
      <div class="brand-stmt-text">
        <p class="brand-stmt-body">Chúng tôi thiết kế những trang phục linen tinh tế, xây dựng phong cách sống bền vững, và mang đến những trải nghiệm mặc đẹp mỗi ngày. Bắt nguồn từ chất liệu tự nhiên và được định hướng bởi sự tối giản, LINNÉ không ngừng phát triển theo cách có chủ đích.</p>
        <a href="#" class="brand-stmt-cta">Tìm hiểu về LINNÉ</a>
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
