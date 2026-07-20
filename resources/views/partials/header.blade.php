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
        <a href="{{ $megaMenuBlogUrl }}" class="nav-quick-link">Journal</a>
        <a href="{{ $megaMenuAboutUrl }}" class="nav-quick-link">Về CacyLinen</a>
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

      <a href="{{ url('/account') }}" class="nav-icon-btn nav-account-btn" aria-label="Tài khoản" hidden>
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
          <span class="mega-group-plus" aria-hidden="true">
            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
          </span>
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
          <span class="mega-group-plus" aria-hidden="true">
            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
          </span>
        </div>
        <div class="mega-group-links">
          <a href="{{ $megaMenuBlogUrl }}" class="mega-link">Journal</a>
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
            <span class="mega-group-plus" aria-hidden="true">
            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
          </span>
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

    {{-- Col 4: Sub 1 = Business Setting (contact + social), Sub 2 = footer "Thông tin" column --}}
    <div class="mega-col mega-col--brand">

      @php
        $megaContactAddress = \App\Models\Setting::get('contact_address');
        $megaContactPhone = \App\Models\Setting::get('contact_phone');
        $megaContactEmail = \App\Models\Setting::get('contact_email');
      @endphp

      {{-- Sub 1: contact + social — Business Profile (Setting), same source as footer --}}
      <div class="mega-group">
        <div class="mega-group-hd">
          <span class="mega-group-name">{{ __('footer.legal.contact') }}</span>
          <span class="mega-group-plus" aria-hidden="true">
            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
          </span>
        </div>
        @if($megaContactAddress || $megaContactPhone || $megaContactEmail)
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
        @endif
        <div class="mega-social">
          @if($social = \App\Models\Setting::get('social_facebook'))
            <a href="{{ $social }}" class="footer-social-link" aria-label="Facebook" target="_blank" rel="noopener">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
              </svg>
            </a>
          @endif
          @if($social = \App\Models\Setting::get('social_instagram'))
            <a href="{{ $social }}" class="footer-social-link" aria-label="Instagram" target="_blank" rel="noopener">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                <circle cx="12" cy="12" r="4"/>
                <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>
              </svg>
            </a>
          @endif
          @if($social = \App\Models\Setting::get('social_youtube'))
            <a href="{{ $social }}" class="footer-social-link" aria-label="YouTube" target="_blank" rel="noopener">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 0 0 1.46 6.42 29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.95 1.96C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.96-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/>
                <polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="white"/>
              </svg>
            </a>
          @endif
          @if($social = \App\Models\Setting::get('social_tiktok'))
            <a href="{{ $social }}" class="footer-social-link" aria-label="TikTok" target="_blank" rel="noopener">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.32 6.32 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V9.05a8.16 8.16 0 0 0 4.78 1.52V7.12a4.85 4.85 0 0 1-1.01-.43z"/>
              </svg>
            </a>
          @endif
          @if($social = \App\Models\Setting::get('social_twitter'))
            <a href="{{ $social }}" class="footer-social-link" aria-label="X (Twitter)" target="_blank" rel="noopener">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
              </svg>
            </a>
          @endif
          @if($social = \App\Models\Setting::get('social_zalo'))
            <a href="{{ $social }}" class="footer-social-link" aria-label="Zalo" target="_blank" rel="noopener">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M21 11.5a8.38 8.38 0 0 1-4.7 7.6 8.38 8.38 0 0 1-3.8.9 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.38 8.38 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
              </svg>
            </a>
          @endif
        </div>
      </div>

      {{-- Sub 2: footer "Thông tin" column (About + Size Guide + active static Pages) --}}
      <div class="mega-group">
        <div class="mega-group-hd">
          <span class="mega-group-name">{{ __('footer.info.title') }}</span>
          <span class="mega-group-plus" aria-hidden="true">
            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
          </span>
        </div>
        <div class="mega-group-links">
          <a href="{{ $megaMenuAboutUrl }}" class="mega-link">{{ __('footer.info.about') }}</a>
          <a href="{{ route(app()->getLocale() . '.size-guide') }}" class="mega-link">{{ __('footer.info.size_guide') }}</a>
          @foreach($footerPages as $page)
            <a href="{{ $page['url'] }}" class="mega-link">{{ $page['name'] }}</a>
          @endforeach
        </div>
      </div>
    </div>

    <button class="mega-close" id="megaClose" aria-label="Đóng menu">✕</button>
  </div>
</div>

<div class="mega-overlay" id="megaOverlay"></div>
