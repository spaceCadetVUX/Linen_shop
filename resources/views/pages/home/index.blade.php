@extends('layouts.app')

@section('title', 'LINNÉ — Thời trang linen tối giản, bền vững')
@section('meta-description', 'LINNÉ — Thời trang linen tối giản, bền vững. Bộ sưu tập Thu 2026.')

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



  <!-- ==============================  SHOP GRID  ============================== -->
  <section class="shop-section" id="shopSection">
    <div class="shop-header">
      <nav class="shop-tabs">
        <button class="shop-tab active">Áo linen</button>
        <button class="shop-tab">Áo khoác</button>
        <button class="shop-tab">Quần &amp; Váy</button>
      </nav>
    </div>

    <div class="shop-grid">

      <div class="shop-card">
        <div class="shop-card-img-wrap">
          <img src="https://elleandriley.com/cdn/shop/files/Luxe_Cardigan_Seafoam4.jpg?v=1778219735&width=2160" alt="Áo linen cổ chữ V" class="shop-card-img">
          <img src="https://elleandriley.com/cdn/shop/files/Luxe_Cardigan_Seafoam3.jpg?v=1778219735&width=2160" alt="" class="shop-card-img-alt" aria-hidden="true">
          <span class="shop-badge">Mới</span>
        </div>
        <div class="shop-card-info">
          <span class="shop-card-name">Áo linen cổ chữ V</span>
          <p class="shop-card-meta">100% Linen · Cổ chữ V</p>
          <span class="shop-card-price">660.000 ₫</span>
          <div class="shop-card-swatches">
            <span class="shop-swatch swatch-cream"></span>
            <span class="shop-swatch swatch-forest"></span>
            <span class="shop-swatch swatch-slate"></span>
          </div>
        </div>
      </div>

      <div class="shop-card">
        <div class="shop-card-img-wrap">
          <img src="https://elleandriley.com/cdn/shop/files/thumbnail-2_4cf766ce-5b06-4193-a934-16b9810f4d7f.jpg?v=1760544267&width=2160" alt="Áo blouse thắt nơ" class="shop-card-img">
          <img src="https://elleandriley.com/cdn/shop/files/thumbnail-4_02c534f0-4e07-437b-bf55-1a30ceefa45c.jpg?v=1760544267&width=2160" alt="" class="shop-card-img-alt" aria-hidden="true">
          <span class="shop-badge">Mới</span>
        </div>
        <div class="shop-card-info">
          <span class="shop-card-name">Áo blouse thắt nơ</span>
          <p class="shop-card-meta">100% Linen · Thắt nơ</p>
          <span class="shop-card-price">720.000 ₫</span>
          <div class="shop-card-swatches">
            <span class="shop-swatch swatch-cream"></span>
            <span class="shop-swatch swatch-camel"></span>
            <span class="shop-swatch swatch-cognac"></span>
            <span class="shop-swatch swatch-noir"></span>
          </div>
        </div>
      </div>

      <div class="shop-card">
        <div class="shop-card-img-wrap">
          <img src="https://elleandriley.com/cdn/shop/files/thumbnail-3_b566575e-e85c-4d05-8881-d0aed54285d7.jpg?v=1760544198&width=2160" alt="Áo crop linen" class="shop-card-img">
          <img src="https://elleandriley.com/cdn/shop/files/thumbnail-5.jpg?v=1760544198&width=2160" alt="" class="shop-card-img-alt" aria-hidden="true">
          <span class="shop-badge">Mới</span>
        </div>
        <div class="shop-card-info">
          <span class="shop-card-name">Áo crop linen</span>
          <p class="shop-card-meta">100% Linen · Crop fit</p>
          <span class="shop-card-price">620.000 ₫</span>
          <div class="shop-card-swatches">
            <span class="shop-swatch swatch-cream"></span>
            <span class="shop-swatch swatch-rust"></span>
            <span class="shop-swatch swatch-noir"></span>
            <span class="shop-swatch swatch-forest"></span>
          </div>
        </div>
      </div>

      <div class="shop-card">
        <div class="shop-card-img-wrap">
          <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Red.jpg?v=1778217693&width=2160" alt="Áo linen oversized" class="shop-card-img">
          <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Red3.jpg?v=1778217693&width=2160" alt="" class="shop-card-img-alt" aria-hidden="true">
          <span class="shop-badge">Mới</span>
        </div>
        <div class="shop-card-info">
          <span class="shop-card-name">Áo linen oversized</span>
          <p class="shop-card-meta">100% Linen · Oversized</p>
          <span class="shop-card-price">680.000 ₫</span>
          <div class="shop-card-swatches">
            <span class="shop-swatch swatch-cream"></span>
            <span class="shop-swatch swatch-slate"></span>
            <span class="shop-swatch swatch-noir"></span>
          </div>
        </div>
      </div>

    </div>
  </section>

    <!-- ==============================  SHOP GRID  ============================== -->
  <section class="shop-section" id="shopSection">
    <div class="shop-header">
      <nav class="shop-tabs">
        <button class="shop-tab active">Áo linen</button>
        <button class="shop-tab">Áo khoác</button>
        <button class="shop-tab">Quần &amp; Váy</button>
      </nav>
    </div>

    <div class="shop-grid">
      <div class="shop-card">
        <div class="shop-card-img-wrap">
          <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Black.jpg?v=1779070489&width=2160" alt="Áo linen cổ chữ V" class="shop-card-img">
          <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Black4.jpg?v=1779070489&width=2160" alt="" class="shop-card-img-alt" aria-hidden="true">
          <span class="shop-badge">Mới</span>
        </div>
        <div class="shop-card-info">
          <span class="shop-card-name">Áo linen cổ chữ V</span>
          <p class="shop-card-meta">100% Linen · Cổ chữ V</p>
          <span class="shop-card-price">660.000 ₫</span>
          <div class="shop-card-swatches">
            <span class="shop-swatch swatch-cream"></span>
            <span class="shop-swatch swatch-forest"></span>
            <span class="shop-swatch swatch-slate"></span>
          </div>
        </div>
      </div>

      <div class="shop-card">
        <div class="shop-card-img-wrap">
          <img src="https://elleandriley.com/cdn/shop/files/Bandana-Scarf-Black-Sand_80abcf68-ce45-46c1-8665-8419cb22e876.jpg?v=1781216042&width=2160" alt="Áo blouse thắt nơ" class="shop-card-img">
          <img src="https://elleandriley.com/cdn/shop/files/Bandana-Scarf-Black-Sand3.jpg?v=1781216042&width=2160" alt="" class="shop-card-img-alt" aria-hidden="true">
          <span class="shop-badge">Mới</span>
        </div>
        <div class="shop-card-info">
          <span class="shop-card-name">Áo blouse thắt nơ</span>
          <p class="shop-card-meta">100% Linen · Thắt nơ</p>
          <span class="shop-card-price">720.000 ₫</span>
          <div class="shop-card-swatches">
            <span class="shop-swatch swatch-cream"></span>
            <span class="shop-swatch swatch-camel"></span>
            <span class="shop-swatch swatch-cognac"></span>
            <span class="shop-swatch swatch-noir"></span>
          </div>
        </div>
      </div>

      <div class="shop-card">
        <div class="shop-card-img-wrap">
          <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Red.jpg?v=1778217693&width=2160" alt="Áo crop linen" class="shop-card-img">
          <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Red2.jpg?v=1778217693&width=2160" alt="" class="shop-card-img-alt" aria-hidden="true">
          <span class="shop-badge">Mới</span>
        </div>
        <div class="shop-card-info">
          <span class="shop-card-name">Áo crop linen</span>
          <p class="shop-card-meta">100% Linen · Crop fit</p>
          <span class="shop-card-price">620.000 ₫</span>
          <div class="shop-card-swatches">
            <span class="shop-swatch swatch-cream"></span>
            <span class="shop-swatch swatch-rust"></span>
            <span class="shop-swatch swatch-noir"></span>
            <span class="shop-swatch swatch-forest"></span>
          </div>
        </div>
      </div>

      <div class="shop-card">
        <div class="shop-card-img-wrap">
          <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Birch.jpg?v=1778217589&width=2160" alt="Áo linen oversized" class="shop-card-img">
          <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Birch3.jpg?v=1778217589&width=2160" alt="" class="shop-card-img-alt" aria-hidden="true">
          <span class="shop-badge">Mới</span>
        </div>
        <div class="shop-card-info">
          <span class="shop-card-name">Áo linen oversized</span>
          <p class="shop-card-meta">100% Linen · Oversized</p>
          <span class="shop-card-price">680.000 ₫</span>
          <div class="shop-card-swatches">
            <span class="shop-swatch swatch-cream"></span>
            <span class="shop-swatch swatch-slate"></span>
            <span class="shop-swatch swatch-noir"></span>
          </div>
        </div>
      </div>

    </div>
  </section>

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

  <!-- ==============================  JOURNAL / BLOG  ============================== -->
  <section class="journal-section" id="journalSection">
    <div class="journal-header">
      <p class="journal-eyebrow">Nhật ký thời trang</p>
      <h2 class="journal-title">JOURNAL</h2>
      <a href="#" class="journal-view-all">Xem tất cả <span class="journal-view-all-arrow">→</span></a>
    </div>

    <div class="journal-grid">

      <article class="journal-card">
        <a href="journal.html" class="journal-card-img-link">
          <div class="journal-card-img-wrap">
            <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Camel3.jpg?v=1779070696&width=2160"
                 alt="Mặc gì khi đi làm?" class="journal-card-img">
          </div>
        </a>
        <div class="journal-card-body">
          <div class="jnl-card-meta"><span class="jnl-tag">Phong cách</span><span class="jnl-date">12/06/2026</span></div>
          <h3 class="journal-card-title">Mặc gì khi đi làm?</h3>
          <p class="journal-card-excerpt">Chọn trang phục phù hợp khi đi làm đôi khi là một thử thách...</p>
          <a href="journal.html" class="journal-card-cta">Đọc thêm</a>
        </div>
      </article>

      <article class="journal-card">
        <a href="journal.html" class="journal-card-img-link">
          <div class="journal-card-img-wrap">
            <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_BrownMelange2.jpg?v=1778217470&width=2160"
                 alt="Chọn độ dài váy phù hợp" class="journal-card-img">
          </div>
        </a>
        <div class="journal-card-body">
          <div class="jnl-card-meta"><span class="jnl-tag">Phong cách</span><span class="jnl-date">05/06/2026</span></div>
          <h3 class="journal-card-title">Chọn độ dài váy phù hợp</h3>
          <p class="journal-card-excerpt">Độ dài váy phù hợp có thể tôn lên vóc dáng của bạn...</p>
          <a href="journal.html" class="journal-card-cta">Đọc thêm</a>
        </div>
      </article>

      <article class="journal-card">
        <a href="journal.html" class="journal-card-img-link">
          <div class="journal-card-img-wrap">
            <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Birch.jpg?v=1778217589&width=2160"
                 alt="Blazer và phong cách văn phòng" class="journal-card-img">
          </div>
        </a>
        <div class="journal-card-body">
          <div class="jnl-card-meta"><span class="jnl-tag">Xu hướng</span><span class="jnl-date">28/05/2026</span></div>
          <h3 class="journal-card-title">Blazer và phong cách văn phòng</h3>
          <p class="journal-card-excerpt">Blazer là một item không thể thiếu trong tủ đồ thời trang...</p>
          <a href="journal.html" class="journal-card-cta">Đọc thêm</a>
        </div>
      </article>

      <article class="journal-card">
        <a href="journal.html" class="journal-card-img-link">
          <div class="journal-card-img-wrap">
            <img src="https://elleandriley.com/cdn/shop/files/Slim_tee_Pale_Blue.jpg?v=1778216637&width=2160"
                 alt="Chăm sóc vải linen đúng cách" class="journal-card-img">
          </div>
        </a>
        <div class="journal-card-body">
          <div class="jnl-card-meta"><span class="jnl-tag">Chất liệu</span><span class="jnl-date">20/05/2026</span></div>
          <h3 class="journal-card-title">Chăm sóc vải linen đúng cách</h3>
          <p class="journal-card-excerpt">Vải linen bền đẹp hơn nếu bạn biết cách giặt và bảo quản...</p>
          <a href="journal.html" class="journal-card-cta">Đọc thêm</a>
        </div>
      </article>

    </div>
  </section>

@endsection
