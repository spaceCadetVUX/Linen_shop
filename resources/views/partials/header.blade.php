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
        <a href="{{ $megaMenuCollectionUrl }}" class="nav-quick-link">{{ $megaMenuCollectionLabel }}</a>
        <a href="{{ url('/blog') }}" class="nav-quick-link">Journal</a>
        <a href="{{ url('/about') }}" class="nav-quick-link">Về CacyLinen</a>
      </nav>
    </div>

    {{-- Center: logo --}}
    <a href="{{ url('/') }}" class="nav-logo">CacyLinen</a>

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
<div class="mega-wrap" id="megaWrap" data-mega-products="{{ json_encode($megaMenuProductsByCat, JSON_UNESCAPED_UNICODE) }}">
  <div class="mega-panel">

    {{-- Col 1: Mới — editorial image + featured links --}}
    <div class="mega-col mega-col--new">
      <h3 class="mega-col-title">Mới</h3>

      <div class="mega-group mega-group--img">
        <div class="mega-group-hd">
          <span class="mega-group-name">Sản phẩm mới</span>
          <span class="mega-group-plus" aria-hidden="true">+</span>
        </div>
        {{-- Auto-slide every 5s, loops — JS in app.js toggles .is-active --}}
        <div class="mega-feature-img-wrap" id="megaNewSlider">
          @forelse ($megaMenuNewProducts as $i => $p)
            <a href="{{ $p['url'] }}" class="mega-new-slide{{ $i === 0 ? ' is-active' : '' }}">
              <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}" class="mega-feature-img">
            </a>
          @empty
            <a href="{{ url('/collections/new') }}" class="mega-new-slide is-active">
              <img
                src="{{ asset('assets/images/collections/new-arrivals.jpg') }}"
                alt="Sản phẩm mới - CacyLinen"
                class="mega-feature-img"
              >
            </a>
          @endforelse
        </div>
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
      <a href="{{ $megaMenuCollectionUrl }}" class="mega-col-title mega-col-title--link">{{ $megaMenuCollectionLabel }}</a>

      @forelse ($megaMenuGroups as $group)
        <div class="mega-group">
          <div class="mega-group-hd">
            <a href="{{ $group['url'] }}" class="mega-group-name mega-group-name--link" data-mega-cat="{{ $group['mega_cat'] }}" data-mega-label="{{ $group['name'] }}">{{ $group['name'] }}</a>
            <span class="mega-group-plus" aria-hidden="true">+</span>
          </div>
          @if (count($group['children']))
            <div class="mega-group-links">
              @foreach ($group['children'] as $child)
                <a href="{{ $child['url'] }}" class="mega-link" data-mega-cat="{{ $child['mega_cat'] }}" data-mega-label="{{ $child['label'] }}">{{ $child['label'] }}</a>
              @endforeach
            </div>
          @endif
        </div>
      @empty
        {{-- No Category data mapped yet — admin adds these under Catalog → Categories --}}
        <div class="mega-group">
          <div class="mega-group-links">
            <a href="{{ $megaMenuCollectionUrl }}" class="mega-link">{{ $megaMenuCollectionLabel }}</a>
          </div>
        </div>
      @endforelse
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
      <p class="mega-products-empty" id="megaProductsEmpty" hidden>Chưa có sản phẩm trong danh mục này.</p>
    </div>

    {{-- Col 4: La Maison CacyLinen --}}
    <div class="mega-col mega-col--brand">
      <h3 class="mega-col-title">La Maison CacyLinen</h3>

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
          <a href="{{ route(app()->getLocale() . '.size-guide') }}" class="mega-link">Hướng dẫn size</a>
          <a href="{{ url('/care-guide') }}" class="mega-link">Chăm sóc vải</a>
          <a href="{{ url('/contact') }}"   class="mega-link">Liên hệ</a>
        </div>
      </div>

      <div class="mega-brand-footer">
        <div class="mega-intl-switch">
          <button type="button" class="mega-intl-trigger" aria-haspopup="true">
            <svg class="mega-intl-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
              <circle cx="12" cy="12" r="9"/>
              <path d="M3 12h18M12 3c2.4 2.6 3.7 5.7 3.7 9s-1.3 6.4-3.7 9c-2.4-2.6-3.7-5.7-3.7-9s1.3-6.4 3.7-9z"/>
            </svg>
            <span class="mega-intl-current">{{ $currentLocale === 'en' ? 'English · USD $' : 'Tiếng Việt · VND ₫' }}</span>
            <svg class="mega-intl-chevron" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="m6 9 6 6 6-6"/>
            </svg>
          </button>
          <div class="mega-intl-panel" role="menu">
            <a href="{{ $viUrl }}" class="mega-intl-option @if($currentLocale === 'vi') is-active @endif" role="menuitem">Tiếng Việt · VND ₫</a>
            <a href="{{ $enUrl }}" class="mega-intl-option @if($currentLocale === 'en') is-active @endif" role="menuitem">English · USD $</a>
          </div>
        </div>
      </div>
    </div>

    <button class="mega-close" id="megaClose" aria-label="Đóng menu">✕</button>
  </div>
</div>

<div class="mega-overlay" id="megaOverlay"></div>
