@extends('layouts.app')

@section('title', ($category->name ?? 'Cửa hàng') . ' — LINNÉ')
@section('meta-description', 'Khám phá bộ sưu tập ' . ($category->name ?? '') . ' của LINNÉ — thời trang linen tối giản, bền vững.')
@section('body-class', 'page-pd')

@section('content')

{{-- ============================================================
     PLP BANNER
     Replace: $category->banner_image, $category->name, $category->product_count
     ============================================================ --}}
<section class="plp-banner">
  <img
    src="{{ $category->banner_image ?? 'https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Camel3.jpg?v=1779070696&width=2400' }}"
    alt="{{ $category->name ?? 'Bộ sưu tập' }} — LINNÉ"
    class="plp-banner-img"
  >
  <div class="plp-banner-overlay"></div>
  <div class="plp-banner-caption">
    <p class="plp-banner-eyebrow">Bộ sưu tập · Thu 2026</p>
    <h1 class="plp-banner-title">{{ $category->name ?? 'Cửa hàng' }}</h1>
    <p class="plp-banner-sub">{{ $products->total() }} sản phẩm</p>
  </div>
</section>

{{-- ============================================================
     FILTER TOOLBAR
     plp-cats: client-side JS filter by data-category on cards.
     plp-count: updated by JS when a cat pill is clicked.
     plp-sort-dropdown: JS toggles, no server-side sort yet.
     ============================================================ --}}
<div class="plp-toolbar-wrap">
  <div class="plp-toolbar">

    <div class="plp-toolbar-left">
      <button class="plp-filter-toggle" id="plpFilterToggle" aria-expanded="false">
        <svg width="14" height="10" viewBox="0 0 20 14" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
          <line x1="0" y1="1" x2="20" y2="1"/><line x1="3" y1="7" x2="17" y2="7"/><line x1="7" y1="13" x2="13" y2="13"/>
        </svg>
        Lọc
      </button>
      <nav class="plp-cats" aria-label="Lọc theo danh mục">
        <button class="plp-cat active" data-cat="all">Tất cả</button>
        <button class="plp-cat" data-cat="ao">Áo</button>
        <button class="plp-cat" data-cat="quan-vay">Quần &amp; Váy</button>
        <button class="plp-cat" data-cat="bo-set">Bộ set</button>
        <button class="plp-cat" data-cat="phu-kien">Phụ kiện</button>
      </nav>
    </div>

    <div class="plp-toolbar-right">
      <span class="plp-count" id="plpCount">{{ $products->total() }} sản phẩm</span>
      <div class="plp-sort-wrap">
        <span class="plp-sort-label">Sắp xếp:</span>
        <button class="plp-sort-btn" id="plpSortBtn">
          <span class="plp-sort-label-text">Nổi bật</span>
          <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="plp-sort-chevron" aria-hidden="true">
            <polyline points="6 9 12 15 18 9"/>
          </svg>
        </button>
        <div class="plp-sort-dropdown" id="plpSortDropdown">
          <button class="plp-sort-option active">Nổi bật</button>
          <button class="plp-sort-option">Mới nhất</button>
          <button class="plp-sort-option">Giá: Thấp → Cao</button>
          <button class="plp-sort-option">Giá: Cao → Thấp</button>
        </div>
      </div>
    </div>

  </div>
</div>

{{-- ============================================================
     PRODUCT GRID
     data-category on each card is used by JS cat-pill filter.
     Pass category prop = product's top-level category slug
     so the filter pill can show/hide it client-side.
     ============================================================ --}}
<section class="plp-grid-section shop-section" id="plpGridSection">
  <div class="plp-grid">
    @foreach($products as $product)
      <x-product.card :product="$product" :category="$product->category_slug ?? null" />
    @endforeach
  </div>

  @if($products->hasMorePages())
    <div class="plp-load-more">
      <button class="plp-load-btn">Xem thêm <span>→</span></button>
    </div>
  @endif
</section>

{{-- ============================================================
     FILTER MODAL
     position: fixed in CSS — DOM placement doesn't affect display.
     JS targets: #plpFmodal, #plpFmodalClose, #plpFmodalOverlay
     ============================================================ --}}
<div class="plp-fmodal" id="plpFmodal" aria-hidden="true">
  <div class="plp-fmodal-overlay" id="plpFmodalOverlay"></div>
  <div class="plp-fmodal-panel" role="dialog" aria-label="Bộ lọc sản phẩm">

    <div class="plp-fmodal-head">
      <span class="plp-fmodal-title">Bộ lọc</span>
      <button class="plp-fmodal-close" id="plpFmodalClose" aria-label="Đóng bộ lọc">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>

    <div class="plp-fmodal-body">

      <div class="plp-fmodal-group">
        <p class="plp-fmodal-group-label">Màu sắc</p>
        <div class="plp-fmodal-swatches">
          <button class="plp-filter-swatch swatch-cream"  title="Cream"  aria-label="Cream"></button>
          <button class="plp-filter-swatch swatch-camel"  title="Camel"  aria-label="Camel"></button>
          <button class="plp-filter-swatch swatch-cognac" title="Cognac" aria-label="Cognac"></button>
          <button class="plp-filter-swatch swatch-rust"   title="Rust"   aria-label="Rust"></button>
          <button class="plp-filter-swatch swatch-forest" title="Forest" aria-label="Forest"></button>
          <button class="plp-filter-swatch swatch-slate"  title="Slate"  aria-label="Slate"></button>
          <button class="plp-filter-swatch swatch-noir"   title="Noir"   aria-label="Noir"></button>
        </div>
      </div>

      <div class="plp-fmodal-group">
        <p class="plp-fmodal-group-label">Size</p>
        <div class="plp-filter-options">
          <button class="plp-filter-option plp-filter-option--size">XS</button>
          <button class="plp-filter-option plp-filter-option--size">S</button>
          <button class="plp-filter-option plp-filter-option--size">M</button>
          <button class="plp-filter-option plp-filter-option--size">L</button>
          <button class="plp-filter-option plp-filter-option--size">XL</button>
        </div>
      </div>

      <div class="plp-fmodal-group">
        <p class="plp-fmodal-group-label">Giá</p>
        <div class="plp-filter-options">
          <button class="plp-filter-option">Dưới 500K</button>
          <button class="plp-filter-option">500K – 800K</button>
          <button class="plp-filter-option">Trên 800K</button>
        </div>
      </div>

    </div>

    <div class="plp-fmodal-foot">
      <button class="plp-fmodal-btn-clear">Xóa tất cả</button>
      <button class="plp-fmodal-btn-apply">Xem kết quả</button>
    </div>

  </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
  // Category pills — client-side show/hide
  var cats  = document.querySelectorAll('.plp-cat');
  var cards = document.querySelectorAll('.plp-grid .shop-card');
  cats.forEach(function (cat) {
    cat.addEventListener('click', function () {
      cats.forEach(function (c) { c.classList.remove('active'); });
      cat.classList.add('active');
      var sel = cat.dataset.cat;
      var visible = 0;
      cards.forEach(function (card) {
        var show = sel === 'all' || card.dataset.category === sel;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
      });
      document.getElementById('plpCount').textContent = visible + ' sản phẩm';
    });
  });

  // Filter modal
  var filterBtn     = document.getElementById('plpFilterToggle');
  var fmodal        = document.getElementById('plpFmodal');
  var fmodalClose   = document.getElementById('plpFmodalClose');
  var fmodalOverlay = document.getElementById('plpFmodalOverlay');

  function openFilter() {
    fmodal.classList.add('open');
    fmodal.setAttribute('aria-hidden', 'false');
    filterBtn.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeFilter() {
    fmodal.classList.remove('open');
    fmodal.setAttribute('aria-hidden', 'true');
    filterBtn.classList.remove('active');
    document.body.style.overflow = '';
  }

  if (filterBtn)     filterBtn.addEventListener('click', openFilter);
  if (fmodalClose)   fmodalClose.addEventListener('click', closeFilter);
  if (fmodalOverlay) fmodalOverlay.addEventListener('click', closeFilter);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeFilter(); });

  // Sort dropdown
  var sortBtn  = document.getElementById('plpSortBtn');
  var sortDrop = document.getElementById('plpSortDropdown');
  if (sortBtn && sortDrop) {
    sortBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      var isOpen = sortDrop.classList.toggle('open');
      sortBtn.classList.toggle('open', isOpen);
    });
    sortDrop.querySelectorAll('.plp-sort-option').forEach(function (opt) {
      opt.addEventListener('click', function () {
        sortDrop.querySelectorAll('.plp-sort-option').forEach(function (o) { o.classList.remove('active'); });
        opt.classList.add('active');
        sortBtn.querySelector('.plp-sort-label-text').textContent = opt.textContent;
        sortDrop.classList.remove('open');
        sortBtn.classList.remove('open');
      });
    });
    document.addEventListener('click', function () {
      sortDrop.classList.remove('open');
      sortBtn.classList.remove('open');
    });
  }

  // Filter pills / swatches toggle
  document.querySelectorAll('.plp-filter-option, .plp-filter-swatch').forEach(function (el) {
    el.addEventListener('click', function () { el.classList.toggle('active'); });
  });

  // Clear + apply
  var clearBtn = document.querySelector('.plp-fmodal-btn-clear');
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      document.querySelectorAll('.plp-filter-option.active, .plp-filter-swatch.active').forEach(function (el) {
        el.classList.remove('active');
      });
    });
  }
  var applyBtn = document.querySelector('.plp-fmodal-btn-apply');
  if (applyBtn) applyBtn.addEventListener('click', closeFilter);
})();
</script>
@endpush
