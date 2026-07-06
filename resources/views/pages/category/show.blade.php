@extends('layouts.app')

@section('title', ($seoMeta?->meta_title ?? $fallbackTitle))
@section('meta-description', $seoMeta?->meta_description ?? $fallbackDescription)
@section('body-class', 'page-pd')

@section('content')

<x-ui.breadcrumb :items="$breadcrumbItems" />

{{-- ============================================================
     CATEGORY BANNER
     ============================================================ --}}
<section class="plp-banner">
  <div class="plp-banner-img-col">
    <img
      src="{{ $fallbackImage ?? asset('assets/img/placeholder-category.jpg') }}"
      alt="{{ $translation->name }} - {{ \App\Models\Setting::get('site_name') }}"
      class="plp-banner-img"
    >
  </div>
  <div class="plp-banner-info">
    <h1 class="plp-banner-title">{{ $translation->name }}</h1>
    @if($translation->description)
      <div class="plp-banner-divider"></div>
      <p class="plp-banner-desc">{{ $translation->description }}</p>
    @endif
    <p class="plp-banner-sub">{{ $products->total() }} sản phẩm</p>
  </div>
</section>

{{-- ============================================================
     FILTER TOOLBAR
     Filter groups + price range are wired (x-product.filter-modal).
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
     PRODUCT GRID
     ============================================================ --}}
<section class="plp-grid-section shop-section" id="plpGridSection">
  <x-product.grid :products="$products" empty-message="Chưa có sản phẩm nào trong danh mục này." />
</section>

{{-- ============================================================
     RICH CONTENT — admin-managed (Filament category rich_content),
     rendered at the bottom of the page, below the product grid.
     ============================================================ --}}
@if($richContentHtml)
  <section class="category-rich-content">
    <div class="jnl-post-body">
      {!! $richContentHtml !!}
    </div>
  </section>
@endif

{{-- ============================================================
     FAQ — admin-managed (GeoEntityProfile.faq, falls back to legacy
     faq_items_{locale}). Reuses the PDP accordion pattern (.pd-accordions /
     .pd-acc-trigger) so the toggle behaviour is wired for free by the
     global app.js handler — same look everywhere FAQ shows up.
     ============================================================ --}}
@if(count($faqEntities))
  <section class="category-faq">
    <div class="jnl-post-body">
      <h2 class="jnl-post-related-hd">{{ $locale === 'vi' ? 'Câu hỏi thường gặp' : 'Frequently asked questions' }}</h2>
      <div class="pd-accordions">
        @foreach($faqEntities as $faq)
          <div class="pd-accordion">
            <button class="pd-acc-trigger" aria-expanded="false" type="button">
              <span>{{ $faq['question'] }}</span>
              <span class="pd-acc-icon" aria-hidden="true">+</span>
            </button>
            <div class="pd-acc-body">
              <p>{{ $faq['answer'] }}</p>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>
@endif

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
