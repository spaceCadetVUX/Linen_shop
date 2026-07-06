@extends('layouts.app')

@section('title', ($seoMeta?->meta_title ?? $fallbackTitle))
@section('meta-description', $seoMeta?->meta_description ?? $fallbackDescription)
@section('body-class', 'page-pd')

@section('content')

<x-ui.breadcrumb :items="[
    ['label' => $locale === 'vi' ? 'Trang chủ' : 'Home', 'url' => route($locale . '.index')],
    ['label' => $locale === 'vi' ? 'Cửa hàng' : 'Shop', 'url' => null],
]" />

{{-- ============================================================
     PLP BANNER — admin-managed via Filament ShopSetting (H1, intro,
     hero image). Falls back to text-only "Tất cả sản phẩm" when unset;
     .plp-banner-info is flex:1 so it fills full width without the img col.
     ============================================================ --}}
<section class="plp-banner">
  @if($shopHero['image_url'])
    <div class="plp-banner-img-col">
      <img src="{{ $shopHero['image_url'] }}" alt="{{ $shopHero['image_alt'] }}" class="plp-banner-img">
    </div>
  @endif
  <div class="plp-banner-info">
    <h1 class="plp-banner-title">{{ $shopHero['title'] }}</h1>
    @if($shopHero['intro'])
      <div class="plp-banner-divider"></div>
      <p class="plp-banner-desc">{{ $shopHero['intro'] }}</p>
    @endif
    <p class="plp-banner-sub">{{ $products->total() }} sản phẩm</p>
  </div>
</section>

{{-- ============================================================
     FILTER TOOLBAR
     Filter groups + price range are wired (x-product.filter-modal,
     ?{group_slug}=value1,value2 + min_price/max_price query params).
     TODO: Sort dropdown has no server-side sort. Brand filter not in UI.
     ============================================================ --}}
@php
  $activeFilterCount = array_sum(array_map('count', $activeValueSlugs))
      + (($minPrice !== null || $maxPrice !== null) ? 1 : 0);
@endphp
<div class="plp-toolbar-wrap">
  <div class="plp-toolbar">

    <div class="plp-toolbar-left">
      <button class="plp-filter-toggle" id="plpFilterToggle" aria-expanded="false">
        <svg width="14" height="10" viewBox="0 0 20 14" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
          <line x1="0" y1="1" x2="20" y2="1"/><line x1="3" y1="7" x2="17" y2="7"/><line x1="7" y1="13" x2="13" y2="13"/>
        </svg>
        Lọc{{ $activeFilterCount ? " ({$activeFilterCount})" : '' }}
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
  <x-product.grid :products="$products" empty-message="Chưa có sản phẩm nào." :auto-load="true" />
</section>

{{-- ============================================================
     FILTER MODAL — shared component, wired to $filterGroups + price
     ============================================================ --}}
<x-product.filter-modal
    :filter-groups="$filterGroups"
    :active-value-slugs="$activeValueSlugs"
    :price-bounds="$priceBounds"
    :min-price="$minPrice"
    :max-price="$maxPrice"
    :locale="$locale"
/>

@endsection

@push('scripts')
<script>
{{-- Filter modal JS lives in x-product.filter-modal — only sort stays here --}}
(function () {
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
})();
</script>
@endpush
