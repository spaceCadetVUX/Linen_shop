@extends('layouts.app')

@section('title', 'Áo Tank Cashmere Lurex')
@section('meta-description', 'Áo Tank Cashmere Lurex CacyLinen — dệt từ cashmere Mông Cổ Grade A kết hợp lurex ánh kim. Dáng fitted, subtle luxury.')
@section('body-class', 'page-pd')

@section('content')

{{-- ============================================================
     BREADCRUMB
     ============================================================ --}}
<nav class="pd-breadcrumb" aria-label="Breadcrumb">
  <div class="pd-breadcrumb-inner">
    <a href="{{ url('/') }}">Trang chủ</a>
    <span class="pd-bc-sep" aria-hidden="true">/</span>
    <a href="{{ url('/collections') }}">Bộ sưu tập</a>
    <span class="pd-bc-sep" aria-hidden="true">/</span>
    <a href="{{ url('/shop/ao-cashmere') }}">Áo Cashmere</a>
    <span class="pd-bc-sep" aria-hidden="true">/</span>
    <span aria-current="page">Áo Tank Cashmere Lurex</span>
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

      <div class="pd-gimg-wrap">
        <div class="pd-img-badge"><span class="badge badge-muted">Mới về</span></div>
        <img
          src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Camel3.jpg?v=1779070696&width=1400"
          alt="Áo Tank Cashmere Lurex CacyLinen — nhìn chính diện"
          loading="eager"
        >
      </div>

      <div class="pd-gimg-wrap">
        <img
          src="https://elleandriley.com/cdn/shop/files/Slim_tee_Pale_Blue.jpg?v=1778216637&width=1400"
          alt="Áo Tank Cashmere Lurex — chi tiết lurex ánh kim"
          loading="lazy"
        >
      </div>

      <div class="pd-gimg-wrap">
        <img
          src="https://elleandriley.com/cdn/shop/files/Slim_Tee_BrownMelange2.jpg?v=1778217470&width=1400"
          alt="Áo Tank Cashmere Lurex — nhìn từ sau"
          loading="lazy"
        >
      </div>

      <div class="pd-gimg-wrap">
        <img
          src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Birch.jpg?v=1778217589&width=1400"
          alt="Áo Tank Cashmere Lurex — phối cùng quần"
          loading="lazy"
        >
      </div>

      <div class="pd-gimg-wrap">
        <img
          src="https://elleandriley.com/cdn/shop/files/Bandana-Scarf-Sand-Cream3.jpg?v=1781216151&width=1400"
          alt="Áo Tank Cashmere Lurex — flat lay texture"
          loading="lazy"
        >
      </div>

      <div class="pd-gimg-wrap">
        <img
          src="https://elleandriley.com/cdn/shop/files/Slim_Tee_BrownMelange.jpg?v=1778217470&width=1400"
          alt="Áo Tank Cashmere Lurex — styled look"
          loading="lazy"
        >
      </div>

    </div>{{-- /.pd-gallery --}}

    {{-- Mobile swipe dots — count must match gallery images above --}}
    <div class="pd-gallery-dots" id="pdGalleryDots" aria-hidden="true">
      <span class="pd-gallery-dot active"></span>
      <span class="pd-gallery-dot"></span>
      <span class="pd-gallery-dot"></span>
      <span class="pd-gallery-dot"></span>
      <span class="pd-gallery-dot"></span>
      <span class="pd-gallery-dot"></span>
    </div>

    {{-- Info panel --}}
    <div class="pd-info">
      <div class="pd-info-inner" id="pdInfoInner">

        <p class="pd-eyebrow">Cashmere Collection</p>

        <div class="pd-title-row">
          <h1 class="pd-title">Áo Tank Cashmere Lurex</h1>
          <button class="pd-wish-btn" id="pdWishBtn" type="button" aria-label="Thêm vào yêu thích" aria-pressed="false">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3">
              <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
          </button>
        </div>

        <div class="pd-price-row">
          <span class="pd-price">3.290.000 ₫</span>
        </div>

        <p class="pd-desc">
          Phiên bản tinh tế của chiếc tank top kinh điển — dệt từ sợi cashmere Mông Cổ Grade A kết hợp lurex ánh kim tạo nên vẻ đẹp subtle luxury. Dáng fitted, mặc đơn hoặc layering đều hoàn hảo.
        </p>

        {{-- Colour — JS reads data-color to update #pdColorLabel on click --}}
        <div class="pd-option-group">
          <div class="pd-option-label">
            <span>Màu sắc</span>
            <span class="pd-color-name" id="pdColorLabel">Ivory</span>
          </div>
          <div class="pd-swatches" role="radiogroup" aria-label="Chọn màu">
            <button class="pd-swatch active" style="background:var(--swatch-cream)"  data-color="Ivory"    role="radio" aria-checked="true"  aria-label="Ivory"></button>
            <button class="pd-swatch"        style="background:var(--swatch-camel)"  data-color="Cognac"   role="radio" aria-checked="false" aria-label="Cognac"></button>
            <button class="pd-swatch"        style="background:var(--swatch-slate)"  data-color="Sapphire" role="radio" aria-checked="false" aria-label="Sapphire"></button>
            <button class="pd-swatch"        style="background:var(--swatch-noir)"   data-color="Noir"     role="radio" aria-checked="false" aria-label="Noir"></button>
          </div>
        </div>

        {{-- Size --}}
        <div class="pd-option-group">
          <div class="pd-option-label">
            <span>Kích cỡ</span>
            <a href="{{ url('/size-guide') }}" class="pd-size-guide">Hướng dẫn chọn size →</a>
          </div>
          <div class="pd-sizes" role="radiogroup" aria-label="Chọn size">
            <button class="pd-size-btn"          data-size="XS">XS</button>
            <button class="pd-size-btn active"   data-size="S">S</button>
            <button class="pd-size-btn"          data-size="M">M</button>
            <button class="pd-size-btn"          data-size="L">L</button>
            <button class="pd-size-btn sold-out" data-size="XL" disabled aria-label="XL — hết hàng">XL</button>
          </div>
        </div>

        <div class="pd-actions">
          <button class="pd-add-btn" id="pdAddBtn" type="button">Thêm vào giỏ hàng</button>
        </div>

        {{-- Accordions — app.js toggles aria-expanded + animates height --}}
        <div class="pd-accordions">

          <div class="pd-accordion">
            <button class="pd-acc-trigger" aria-expanded="false" type="button">
              <span>Chất liệu &amp; Thành phần</span>
              <span class="pd-acc-icon" aria-hidden="true">+</span>
            </button>
            <div class="pd-acc-body">
              <ul>
                <li>70% Cashmere Mông Cổ Grade A</li>
                <li>28% Lurex (sợi kim loại mạ bạc)</li>
                <li>2% Elastane</li>
                <li>Độ mịn: 2-ply fine gauge</li>
                <li>Sản xuất tại Ý</li>
              </ul>
            </div>
          </div>

          <div class="pd-accordion">
            <button class="pd-acc-trigger" aria-expanded="false" type="button">
              <span>Hướng dẫn bảo quản</span>
              <span class="pd-acc-icon" aria-hidden="true">+</span>
            </button>
            <div class="pd-acc-body">
              <ul>
                <li>Giặt tay nước lạnh, không vắt mạnh</li>
                <li>Không giặt máy — không sấy</li>
                <li>Phơi phẳng nằm ngang</li>
                <li>Ủi mặt trái, nhiệt độ thấp</li>
                <li>Không tẩy — không giặt khô</li>
              </ul>
            </div>
          </div>

          <div class="pd-accordion">
            <button class="pd-acc-trigger" aria-expanded="false" type="button">
              <span>Kích cỡ &amp; Dáng dệt</span>
              <span class="pd-acc-icon" aria-hidden="true">+</span>
            </button>
            <div class="pd-acc-body">
              <p style="margin-bottom:10px;">Người mẫu cao 175 cm, mặc size S.</p>
              <ul>
                <li>Dáng fitted — ôm nhẹ</li>
                <li>Chiều dài thân: 54 cm (size S)</li>
                <li>Ngang vai: 34 cm (size S)</li>
                <li>Phù hợp mặc tucked-in hoặc tự nhiên</li>
              </ul>
            </div>
          </div>

          <div class="pd-accordion">
            <button class="pd-acc-trigger" aria-expanded="false" type="button">
              <span>Giao hàng &amp; Đổi trả</span>
              <span class="pd-acc-icon" aria-hidden="true">+</span>
            </button>
            <div class="pd-acc-body">
              <ul>
                <li>Nội thành HCM &amp; HN: 1–2 ngày làm việc</li>
                <li>Toàn quốc: 2–4 ngày làm việc</li>
                <li>Miễn phí vận chuyển từ 1.500.000 ₫</li>
                <li>Đổi trả 14 ngày — nguyên tag, chưa qua sử dụng</li>
                <li>Không áp dụng đổi trả cho hàng sale</li>
              </ul>
            </div>
          </div>

        </div>{{-- /.pd-accordions --}}

        <div class="pd-trust">
          <div class="pd-trust-item">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            <span>100% Cashmere thật — chứng nhận GCS</span>
          </div>
          <div class="pd-trust-item">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
              <rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
              <circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
            </svg>
            <span>Miễn phí giao hàng từ 1.500.000 ₫</span>
          </div>
          <div class="pd-trust-item">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
              <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
              <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
            </svg>
            <span>Đổi trả dễ dàng trong 14 ngày</span>
          </div>
        </div>

      </div>{{-- /.pd-info-inner --}}
    </div>{{-- /.pd-info --}}

  </div>{{-- /.pd-layout --}}
</section>

{{-- ============================================================
     RELATED PRODUCTS
     TODO: replace with @foreach($relatedProducts as $product)
           <x-product.card :product="$product" />
     Note: PDP cards use .shop-card-row (name + price inline),
     different from the stacked layout on PLP/homepage.
     ============================================================ --}}
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
        <div class="shop-card-row">
          <span class="shop-card-name">Áo linen cổ chữ V</span>
          <span class="shop-card-price">660.000 ₫</span>
        </div>
        <div class="shop-card-swatches">
          <span class="shop-swatch swatch-cream active"></span>
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
        <div class="shop-card-row">
          <span class="shop-card-name">Áo blouse thắt nơ</span>
          <span class="shop-card-price">720.000 ₫</span>
        </div>
        <div class="shop-card-swatches">
          <span class="shop-swatch swatch-cream"></span>
          <span class="shop-swatch swatch-camel active"></span>
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
        <div class="shop-card-row">
          <span class="shop-card-name">Áo crop linen</span>
          <span class="shop-card-price">620.000 ₫</span>
        </div>
        <div class="shop-card-swatches">
          <span class="shop-swatch swatch-cream"></span>
          <span class="shop-swatch swatch-rust active"></span>
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
        <div class="shop-card-row">
          <span class="shop-card-name">Áo linen oversized</span>
          <span class="shop-card-price">680.000 ₫</span>
        </div>
        <div class="shop-card-swatches">
          <span class="shop-swatch swatch-cream active"></span>
          <span class="shop-swatch swatch-slate"></span>
          <span class="shop-swatch swatch-noir"></span>
        </div>
      </div>
    </div>

  </div>
</section>

{{-- ============================================================
     JOURNAL
     TODO: replace with @foreach($relatedPosts as $post)
           <x-blog.card :post="$post" />
     ============================================================ --}}
<section class="journal-section" id="journalSection">
  <div class="journal-header">
    <p class="journal-eyebrow">Nhật ký thời trang</p>
    <h2 class="journal-title">JOURNAL</h2>
    <a href="{{ url('/blog') }}" class="journal-view-all">Xem tất cả <span class="journal-view-all-arrow">→</span></a>
  </div>

  <div class="journal-grid">

    <article class="journal-card">
      <a href="{{ url('/blog/mac-gi-khi-di-lam') }}" class="journal-card-img-link">
        <div class="journal-card-img-wrap">
          <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Camel3.jpg?v=1779070696&width=2160" alt="Mặc gì khi đi làm?" class="journal-card-img">
        </div>
      </a>
      <div class="journal-card-body">
        <a href="{{ url('/blog/mac-gi-khi-di-lam') }}" class="journal-card-title">Mặc gì khi đi làm?</a>
        <p class="journal-card-excerpt">Chọn trang phục phù hợp khi đi làm đôi khi là một thử thách...</p>
        <a href="{{ url('/blog/mac-gi-khi-di-lam') }}" class="journal-card-cta">Đọc thêm</a>
      </div>
    </article>

    <article class="journal-card">
      <a href="{{ url('/blog/chon-do-dai-vay-phu-hop') }}" class="journal-card-img-link">
        <div class="journal-card-img-wrap">
          <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_BrownMelange2.jpg?v=1778217470&width=2160" alt="Chọn độ dài váy phù hợp" class="journal-card-img">
        </div>
      </a>
      <div class="journal-card-body">
        <a href="{{ url('/blog/chon-do-dai-vay-phu-hop') }}" class="journal-card-title">Chọn độ dài váy phù hợp</a>
        <p class="journal-card-excerpt">Độ dài váy phù hợp có thể tôn lên vóc dáng của bạn...</p>
        <a href="{{ url('/blog/chon-do-dai-vay-phu-hop') }}" class="journal-card-cta">Đọc thêm</a>
      </div>
    </article>

    <article class="journal-card">
      <a href="{{ url('/blog/blazer-va-phong-cach-van-phong') }}" class="journal-card-img-link">
        <div class="journal-card-img-wrap">
          <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Birch.jpg?v=1778217589&width=2160" alt="Blazer và phong cách văn phòng" class="journal-card-img">
        </div>
      </a>
      <div class="journal-card-body">
        <a href="{{ url('/blog/blazer-va-phong-cach-van-phong') }}" class="journal-card-title">Blazer và phong cách văn phòng</a>
        <p class="journal-card-excerpt">Blazer là một item không thể thiếu trong tủ đồ thời trang...</p>
        <a href="{{ url('/blog/blazer-va-phong-cach-van-phong') }}" class="journal-card-cta">Đọc thêm</a>
      </div>
    </article>

    <article class="journal-card">
      <a href="{{ url('/blog/cham-soc-vai-linen-dung-cach') }}" class="journal-card-img-link">
        <div class="journal-card-img-wrap">
          <img src="https://elleandriley.com/cdn/shop/files/Slim_tee_Pale_Blue.jpg?v=1778216637&width=2160" alt="Chăm sóc vải linen đúng cách" class="journal-card-img">
        </div>
      </a>
      <div class="journal-card-body">
        <a href="{{ url('/blog/cham-soc-vai-linen-dung-cach') }}" class="journal-card-title">Chăm sóc vải linen đúng cách</a>
        <p class="journal-card-excerpt">Vải linen bền đẹp hơn nếu bạn biết cách giặt và bảo quản...</p>
        <a href="{{ url('/blog/cham-soc-vai-linen-dung-cach') }}" class="journal-card-cta">Đọc thêm</a>
      </div>
    </article>

  </div>
</section>

@endsection
