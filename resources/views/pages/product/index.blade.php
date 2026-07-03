@extends('layouts.app')

@section('title', ($seoMeta?->meta_title ?? $fallbackTitle) . ' — LINNÉ')
@section('meta-description', $seoMeta?->meta_description ?? $fallbackDescription)
@section('body-class', 'page-pd')

@section('content')

<x-ui.breadcrumb :items="[
    ['label' => $locale === 'vi' ? 'Trang chủ' : 'Home', 'url' => route($locale . '.index')],
    ['label' => $locale === 'vi' ? 'Cửa hàng' : 'Shop', 'url' => null],
]" />

{{-- ============================================================
     PLP BANNER — text-only (no per-catalog hero image on this route,
     .plp-banner-info is flex:1 so it fills full width without the img col)
     ============================================================ --}}
<section class="plp-banner">
  <div class="plp-banner-info">
    <h1 class="plp-banner-title">{{ $locale === 'vi' ? 'Tất cả sản phẩm' : 'All Products' }}</h1>
    <p class="plp-banner-sub">{{ $products->total() }} sản phẩm</p>
  </div>
</section>

{{-- ============================================================
     FILTER TOOLBAR
     TODO: plp-fmodal color/size are still static mockup — real filter
     data is in $filterGroups/$brands, not wired yet. Price range IS wired
     (min_price/max_price query params, see script below).
     Sort dropdown has no server-side sort.
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
     PRODUCT GRID — shared component, same as pages/category/show.blade.php
     ============================================================ --}}
<section class="plp-grid-section shop-section" id="plpGridSection">
  <x-product.grid :products="$products" empty-message="Chưa có sản phẩm nào." />
</section>

{{-- ============================================================
     FILTER MODAL
     TODO: color/size groups still static mockup — not wired to
     $filterGroups/$brands yet. Price range group below IS wired.
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

      @php
        $priceBoundsMin = (int) floor($priceBounds['min'] ?? 0);
        $priceBoundsMax = (int) max(ceil($priceBounds['max'] ?? 0), $priceBoundsMin + 1);
      @endphp
      <div class="plp-fmodal-group">
        <p class="plp-fmodal-group-label">Giá</p>
        <div class="plp-price-range"
             id="plpPriceRange"
             data-bounds-min="{{ $priceBoundsMin }}"
             data-bounds-max="{{ $priceBoundsMax }}"
             data-current-min="{{ $minPrice !== null ? (int) $minPrice : $priceBoundsMin }}"
             data-current-max="{{ $maxPrice !== null ? (int) $maxPrice : $priceBoundsMax }}">
          <div class="plp-price-track-wrap">
            <div class="plp-price-track"></div>
            <div class="plp-price-track-fill" id="plpPriceFill"></div>
            <input type="range" id="plpPriceMin" class="plp-price-input plp-price-input--min" aria-label="Giá tối thiểu">
            <input type="range" id="plpPriceMax" class="plp-price-input plp-price-input--max" aria-label="Giá tối đa">
          </div>
          <div class="plp-price-values">
            <span id="plpPriceMinLabel"></span>
            <span class="plp-price-values-sep">—</span>
            <span id="plpPriceMaxLabel"></span>
          </div>
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

  document.querySelectorAll('.plp-filter-option, .plp-filter-swatch').forEach(function (el) {
    el.addEventListener('click', function () { el.classList.toggle('active'); });
  });

  var clearBtn = document.querySelector('.plp-fmodal-btn-clear');
  var applyBtn = document.querySelector('.plp-fmodal-btn-apply');

  // ---- Price range slider (dual-thumb, native <input type="range"> pair) ----
  var priceRangeEl = document.getElementById('plpPriceRange');
  var priceBoundsMin = 0;
  var priceBoundsMax = 0;
  var priceMinInput, priceMaxInput;

  function formatVnd(value) {
    return Math.round(value).toLocaleString('vi-VN') + ' ₫';
  }

  if (priceRangeEl) {
    priceBoundsMin = parseInt(priceRangeEl.dataset.boundsMin, 10) || 0;
    priceBoundsMax = parseInt(priceRangeEl.dataset.boundsMax, 10) || (priceBoundsMin + 1);

    priceMinInput = document.getElementById('plpPriceMin');
    priceMaxInput = document.getElementById('plpPriceMax');
    var priceFillEl = document.getElementById('plpPriceFill');
    var priceMinLabel = document.getElementById('plpPriceMinLabel');
    var priceMaxLabel = document.getElementById('plpPriceMaxLabel');
    var priceStep = Math.max(1, Math.round((priceBoundsMax - priceBoundsMin) / 100));

    [priceMinInput, priceMaxInput].forEach(function (input) {
      input.min = priceBoundsMin;
      input.max = priceBoundsMax;
      input.step = priceStep;
    });

    priceMinInput.value = parseInt(priceRangeEl.dataset.currentMin, 10) || priceBoundsMin;
    priceMaxInput.value = parseInt(priceRangeEl.dataset.currentMax, 10) || priceBoundsMax;

    function updatePriceUI() {
      var minVal = parseInt(priceMinInput.value, 10);
      var maxVal = parseInt(priceMaxInput.value, 10);
      var range = (priceBoundsMax - priceBoundsMin) || 1;
      var leftPct = ((minVal - priceBoundsMin) / range) * 100;
      var rightPct = ((maxVal - priceBoundsMin) / range) * 100;

      priceFillEl.style.left = leftPct + '%';
      priceFillEl.style.width = Math.max(0, rightPct - leftPct) + '%';
      priceMinLabel.textContent = formatVnd(minVal);
      priceMaxLabel.textContent = formatVnd(maxVal);
    }

    priceMinInput.addEventListener('input', function () {
      if (parseInt(priceMinInput.value, 10) > parseInt(priceMaxInput.value, 10)) {
        priceMinInput.value = priceMaxInput.value;
      }
      updatePriceUI();
    });
    priceMaxInput.addEventListener('input', function () {
      if (parseInt(priceMaxInput.value, 10) < parseInt(priceMinInput.value, 10)) {
        priceMaxInput.value = priceMinInput.value;
      }
      updatePriceUI();
    });

    updatePriceUI();
  }

  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      document.querySelectorAll('.plp-filter-option.active, .plp-filter-swatch.active').forEach(function (el) {
        el.classList.remove('active');
      });
      if (priceRangeEl) {
        priceMinInput.value = priceBoundsMin;
        priceMaxInput.value = priceBoundsMax;
        priceMinInput.dispatchEvent(new Event('input'));
      }
    });
  }

  if (applyBtn) {
    applyBtn.addEventListener('click', function () {
      var url = new URL(window.location.href);

      if (priceRangeEl) {
        var minVal = parseInt(priceMinInput.value, 10);
        var maxVal = parseInt(priceMaxInput.value, 10);

        if (minVal <= priceBoundsMin) {
          url.searchParams.delete('min_price');
        } else {
          url.searchParams.set('min_price', minVal);
        }

        if (maxVal >= priceBoundsMax) {
          url.searchParams.delete('max_price');
        } else {
          url.searchParams.set('max_price', maxVal);
        }
      }

      url.searchParams.delete('page');
      window.location.href = url.toString();
    });
  }
})();
</script>
@endpush
