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

      <button
        class="nav-btn nav-search-btn"
        id="navSearchBtn"
        aria-label="Tìm kiếm"
        aria-expanded="false"
        data-autocomplete-url="{{ route($currentLocale . '.product.autocomplete') }}"
        data-shop-url="{{ route($currentLocale . '.product.shop') }}"
      >
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <span>{{ $currentLocale === 'en' ? 'Search' : 'Tìm kiếm' }}</span>
      </button>

      <a href="{{ route($currentLocale . '.account.wishlist') }}" class="nav-icon-btn" aria-label="Yêu thích">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
          <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
        </svg>
      </a>

      <a href="{{ route($currentLocale . '.cart') }}" class="nav-icon-btn nav-cart-btn" id="navCartBtn" aria-label="Giỏ hàng">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
          <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4"/>
          <line x1="3" y1="6" x2="21" y2="6"/>
          <path d="M16 10a4 4 0 0 1-8 0"/>
        </svg>
        <span class="nav-cart-count" id="navCartCount" hidden>0</span>
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
     SEARCH OVERLAY — live suggestions via ProductController::autocomplete()
     (LIKE query, locale-aware), "Enter" / "Xem tất cả" → real PLP search
     (ProductSearchService, Meilisearch-backed) at ?q=. JS in app.js.
     ============================================================ --}}
<div class="search-overlay" id="searchOverlay"></div>
<div class="search-wrap" id="searchWrap">
  <div class="search-panel">
    <form class="search-panel-inner" id="searchForm" role="search">
      <svg class="search-panel-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" aria-hidden="true">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
      </svg>
      <input
        type="text"
        id="searchInput"
        class="search-input"
        placeholder="{{ $currentLocale === 'en' ? 'Search products…' : 'Tìm sản phẩm…' }}"
        autocomplete="off"
      >
      <button type="button" class="search-close" id="searchClose" aria-label="Đóng">✕</button>
    </form>
    <div class="search-suggestions" id="searchSuggestions" hidden></div>
  </div>
</div>

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
          <span class="mega-group-name">{{ $megaMenuNewProductsLabel }}</span>
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

      @php
        $megaContactAddress = \App\Models\Setting::get('contact_address');
        $megaContactPhone = \App\Models\Setting::get('contact_phone');
        $megaContactEmail = \App\Models\Setting::get('contact_email');
      @endphp
      @if($megaContactAddress || $megaContactPhone || $megaContactEmail)
        <div class="mega-group">
          <div class="mega-group-hd">
            <span class="mega-group-name">Liên hệ</span>
            <span class="mega-group-plus" aria-hidden="true">+</span>
          </div>
          <div class="mega-group-links">
            @if($megaContactAddress)
              <span class="mega-contact-address">{{ $megaContactAddress }}</span>
            @endif
            @if($megaContactPhone)
              <a href="tel:{{ preg_replace('/[^0-9+]/', '', $megaContactPhone) }}" class="mega-link">{{ $megaContactPhone }}</a>
            @endif
            @if($megaContactEmail)
              <a href="mailto:{{ $megaContactEmail }}" class="mega-link">{{ $megaContactEmail }}</a>
            @endif
          </div>
        </div>
      @endif

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
