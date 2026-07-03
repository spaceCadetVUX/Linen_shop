@extends('layouts.app')

@section('title', ($seoMeta?->meta_title ?? $fallbackTitle) . ' — LINNÉ')
@section('meta-description', $seoMeta?->meta_description ?? $fallbackDescription)
@section('body-class', 'page-pd')

@php
    // First category (already eager-loaded with translations in ProductController::show)
    $firstCategory = $product->categories->first();
    $catT          = $firstCategory?->translations->firstWhere('locale', $locale);

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
    @if($catT)
      <span class="pd-bc-sep" aria-hidden="true">/</span>
      <a href="{{ \App\Support\LocaleUrl::for('category', $catT->slug, $locale) }}">{{ $catT->name }}</a>
    @endif
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
        <div class="pd-gimg-wrap">
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

        <p class="pd-eyebrow">{{ $catT?->name ?? 'LINNÉ' }}</p>

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
              <span class="pd-price"><span class="t-price-old">{{ $priceLabel }}</span> {{ $salePrice }}</span>
            @else
              <span class="pd-price">{{ $priceLabel }}</span>
            @endif
          </div>
        @endif

        @if($translation->short_description)
          <p class="pd-desc">{{ $translation->short_description }}</p>
        @endif

        {{-- Colour — JS reads data-color to update #pdColorLabel on click --}}
        {{-- TODO(Step 4): map to $optionTypesData + $variantsData --}}
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
        {{-- TODO(Step 4): map to $optionTypesData + $variantsData --}}
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
        {{-- TODO: content still static — data source TBD (product attributes?) --}}
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
            <span>100% Linen thật — nguồn gốc minh bạch</span>
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
     RELATED PRODUCTS — same first category, max 8 (from controller).
     Uses .pd-related (PDP-specific CSS), cards via <x-product.card>.
     Hidden entirely when the product has no related items.
     ============================================================ --}}
@if($relatedProducts->isNotEmpty())
<section class="pd-related" id="pdRelated">
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
     JOURNAL
     TODO: controller does not pass $relatedPosts yet — static until
     decided (add query vs drop section).
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
