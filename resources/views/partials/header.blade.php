{{-- ============================================================
     NAVBAR
     Transparent on pages without @section('body-class', 'page-pd').
     Immediately filled when body has class="page-pd" (set via JS).
     ============================================================ --}}
<header class="navbar" id="navbar">
  <div class="nav-inner">

    {{-- Left: hamburger + quick nav --}}
    <div class="nav-left">
      <button class="nav-btn" id="menuBtn" aria-expanded="false" aria-label="Menu">
        <span class="hbg" aria-hidden="true">
          <span></span><span></span><span></span>
        </span>
        <span id="menuLabel">Menu</span>
      </button>
      <nav class="nav-quick" aria-label="Quick navigation">
        <a href="{{ url('/collections') }}" class="nav-quick-link">Bộ sưu tập</a>
        <a href="{{ url('/blog') }}" class="nav-quick-link">Journal</a>
        <a href="{{ url('/about') }}" class="nav-quick-link">Về LINNÉ</a>
      </nav>
    </div>

    {{-- Center: logo --}}
    <a href="{{ url('/') }}" class="nav-logo">LINNÉ</a>

    {{-- Right: lang switcher + search + wishlist + account --}}
    <div class="nav-right">
      @php
        $currentLocale = app()->getLocale();
        $viUrl = $alternateUrls['vi'] ?? '/vi/';
        $enUrl = $alternateUrls['en'] ?? '/en/';
      @endphp
      <div class="nav-lang" aria-label="Chọn ngôn ngữ">
        <a href="{{ $viUrl }}" class="nav-lang-btn @if($currentLocale === 'vi') is-active @endif">Tiếng Việt</a>
        <span class="nav-lang-sep">/</span>
        <a href="{{ $enUrl }}" class="nav-lang-btn @if($currentLocale === 'en') is-active @endif">English</a>
      </div>

      <button class="nav-btn nav-search-btn" aria-label="Tìm kiếm">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <span>Tìm kiếm</span>
      </button>

      <a href="{{ url('/account/wishlist') }}" class="nav-icon-btn" aria-label="Yêu thích">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
          <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
        </svg>
      </a>

      <a href="{{ url('/account') }}" class="nav-icon-btn" aria-label="Tài khoản">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
          <circle cx="12" cy="7" r="4"/>
        </svg>
      </a>
    </div>

  </div>
</header>

{{-- ============================================================
     MEGA MENU
     Col 3 product grid swaps dynamically via JS when hovering
     .mega-link[data-mega-cat] links in col 2.
     ============================================================ --}}
<div class="mega-wrap" id="megaWrap">
  <div class="mega-panel">

    {{-- Col 1: Mới — editorial image + featured links --}}
    <div class="mega-col mega-col--new">
      <h3 class="mega-col-title">Mới</h3>

      <div class="mega-group mega-group--img">
        <div class="mega-group-hd">
          <span class="mega-group-name">Sản phẩm mới</span>
          <span class="mega-group-plus" aria-hidden="true">+</span>
        </div>
        <a href="{{ url('/collections/new') }}" class="mega-feature-img-wrap">
          <img
            src="{{ asset('assets/images/collections/new-arrivals.jpg') }}"
            alt="Sản phẩm mới — LINNÉ"
            class="mega-feature-img"
          >
        </a>
      </div>

      <div class="mega-group">
        <div class="mega-group-hd">
          <span class="mega-group-name">Nổi bật</span>
          <span class="mega-group-plus" aria-hidden="true">+</span>
        </div>
        <div class="mega-group-links">
          <a href="{{ url('/collections/lookbook') }}" class="mega-link">Lookbook</a>
          <a href="{{ url('/products/dam-linen-co-chu-v') }}" class="mega-link">Đầm linen cổ chữ V</a>
          <a href="{{ url('/collections/bo-set') }}" class="mega-link">Bộ set mùa thu</a>
          <a href="{{ url('/products/ao-blouse-gioi-han') }}" class="mega-link">Áo blouse phiên bản giới hạn</a>
        </div>
      </div>
    </div>

    {{-- Col 2: Bộ sưu tập — category links with data-mega-cat for dynamic col 3 --}}
    <div class="mega-col mega-col--collection">
      <h3 class="mega-col-title">Bộ sưu tập</h3>

      <div class="mega-group">
        <div class="mega-group-hd">
          <span class="mega-group-name">Áo</span>
          <span class="mega-group-plus" aria-hidden="true">+</span>
        </div>
        <div class="mega-group-links">
          <a href="{{ url('/shop/ao-linen') }}"  class="mega-link" data-mega-cat="ao-linen"  data-mega-label="Tất cả áo linen">Tất cả áo linen</a>
          <a href="{{ url('/shop/ao-blouse') }}" class="mega-link" data-mega-cat="ao-blouse" data-mega-label="Áo blouse">Áo blouse</a>
          <a href="{{ url('/shop/ao-phong') }}"  class="mega-link" data-mega-cat="ao-phong"  data-mega-label="Áo phông">Áo phông</a>
          <a href="{{ url('/shop/ao-khoac') }}"  class="mega-link" data-mega-cat="ao-khoac"  data-mega-label="Áo khoác">Áo khoác</a>
        </div>
      </div>

      <div class="mega-group">
        <div class="mega-group-hd">
          <span class="mega-group-name">Quần &amp; Váy</span>
          <span class="mega-group-plus" aria-hidden="true">+</span>
        </div>
        <div class="mega-group-links">
          <a href="{{ url('/shop/quan-au') }}"   class="mega-link" data-mega-cat="quan-au"  data-mega-label="Quần âu">Quần âu</a>
          <a href="{{ url('/shop/wide-leg') }}"  class="mega-link" data-mega-cat="wide-leg" data-mega-label="Wide leg">Wide leg</a>
          <a href="{{ url('/shop/vay-midi') }}"  class="mega-link" data-mega-cat="vay-midi" data-mega-label="Váy midi">Váy midi</a>
          <a href="{{ url('/shop/vay-maxi') }}"  class="mega-link" data-mega-cat="vay-maxi" data-mega-label="Váy maxi">Váy maxi</a>
        </div>
      </div>

      <div class="mega-group">
        <div class="mega-group-hd">
          <span class="mega-group-name">Bộ set linen</span>
          <span class="mega-group-plus" aria-hidden="true">+</span>
        </div>
        <div class="mega-group-links">
          <a href="{{ url('/shop/set-quan') }}"      class="mega-link" data-mega-cat="set-quan"      data-mega-label="Set áo + quần">Set áo + quần</a>
          <a href="{{ url('/shop/set-vay') }}"       class="mega-link" data-mega-cat="set-vay"       data-mega-label="Set blouse + váy">Set blouse + váy</a>
          <a href="{{ url('/shop/set-oversized') }}" class="mega-link" data-mega-cat="set-oversized" data-mega-label="Set oversized">Set oversized</a>
        </div>
      </div>

      <div class="mega-group">
        <div class="mega-group-hd">
          <span class="mega-group-name">Phụ kiện</span>
          <span class="mega-group-plus" aria-hidden="true">+</span>
        </div>
        <div class="mega-group-links">
          <a href="{{ url('/shop/tui-tote') }}" class="mega-link" data-mega-cat="tui-tote" data-mega-label="Túi tote">Túi tote</a>
          <a href="{{ url('/shop/mu-linen') }}" class="mega-link" data-mega-cat="mu-linen" data-mega-label="Mũ linen">Mũ linen</a>
          <a href="{{ url('/shop/khan') }}"     class="mega-link" data-mega-cat="khan"     data-mega-label="Khăn &amp; Túi đeo">Khăn &amp; Túi đeo</a>
        </div>
      </div>
    </div>

    {{-- Col 3: Dynamic product grid — swapped by JS on col 2 hover --}}
    <div class="mega-col mega-col--products">
      <span class="mega-group-name mega-products-eyebrow" id="megaProductsEyebrow">Sản phẩm tiêu biểu</span>
      <div class="mega-product-grid" id="megaProductGrid">
        <a href="#" class="mega-product-card">
          <div class="mega-product-img-wrap">
            <img src="" alt="" class="mega-product-img">
          </div>
          <span class="mega-product-name"></span>
        </a>
        <a href="#" class="mega-product-card">
          <div class="mega-product-img-wrap">
            <img src="" alt="" class="mega-product-img">
          </div>
          <span class="mega-product-name"></span>
        </a>
        <a href="#" class="mega-product-card">
          <div class="mega-product-img-wrap">
            <img src="" alt="" class="mega-product-img">
          </div>
          <span class="mega-product-name"></span>
        </a>
        <a href="#" class="mega-product-card">
          <div class="mega-product-img-wrap">
            <img src="" alt="" class="mega-product-img">
          </div>
          <span class="mega-product-name"></span>
        </a>
      </div>
    </div>

    {{-- Col 4: La Maison LINNÉ --}}
    <div class="mega-col mega-col--brand">
      <h3 class="mega-col-title">La Maison LINNÉ</h3>

      <div class="mega-group">
        <div class="mega-group-hd">
          <span class="mega-group-name">Héritage &amp; Savoir-faire</span>
          <span class="mega-group-plus" aria-hidden="true">+</span>
        </div>
        <div class="mega-group-links">
          <a href="{{ url('/about') }}"          class="mega-link">Về chúng tôi</a>
          <a href="{{ url('/about/linen') }}"    class="mega-link">Câu chuyện linen</a>
          <a href="{{ url('/about/sustainability') }}" class="mega-link">Sustainability</a>
        </div>
      </div>

      <div class="mega-group">
        <div class="mega-group-hd">
          <span class="mega-group-name">Dịch vụ</span>
          <span class="mega-group-plus" aria-hidden="true">+</span>
        </div>
        <div class="mega-group-links">
          <a href="{{ url('/size-guide') }}" class="mega-link">Hướng dẫn size</a>
          <a href="{{ url('/care-guide') }}" class="mega-link">Chăm sóc vải</a>
          <a href="{{ url('/contact') }}"   class="mega-link">Liên hệ</a>
        </div>
      </div>

      <div class="mega-brand-footer">
        <a href="#" class="mega-intl-link">Việt Nam (VND ₫) ↗</a>
      </div>
    </div>

    <button class="mega-close" id="megaClose" aria-label="Đóng menu">✕</button>
  </div>
</div>

<div class="mega-overlay" id="megaOverlay"></div>
