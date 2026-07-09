@extends('layouts.app')

@section('title', $fallbackTitle)
@section('meta-description', $fallbackDescription)
@section('body-class', 'page-pd')

@push('meta')
  {{-- Personal, guest-session-based content — nothing here is stable/indexable per visitor. --}}
  <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')

{{-- ============================================================
     WISHLIST — guest-session based (X-Session-ID in localStorage,
     same pattern as Cart). Server has no way to know the visitor's
     items at request time, so this is a static shell; app.js fetches
     GET /api/v1/wishlist on load and hydrates #wishlistGrid.
     ============================================================ --}}
<div class="fav-page" id="wishlistPage" data-locale="{{ $locale }}">

  <div class="fav-hd">
    <p class="fav-hd-eyebrow">CacyLinen · {{ $locale === 'vi' ? 'Yêu thích' : 'Wishlist' }}</p>
    <h1 class="fav-hd-title">
      {{ $fallbackTitle }} <em id="favCountLabel"></em>
    </h1>
  </div>

  <div class="fav-grid" id="wishlistGrid"></div>

  <p class="fav-empty" id="wishlistEmpty" hidden>
    {{ $locale === 'vi'
        ? 'Chưa có sản phẩm yêu thích nào. Bấm biểu tượng ♡ trên trang sản phẩm để lưu lại.'
        : 'No saved products yet. Tap the ♡ icon on any product page to save it here.' }}
  </p>

  <div class="fav-continue">
    <a href="{{ route($locale . '.product.shop') }}" class="cart-continue-link">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
        <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
      </svg>
      {{ $locale === 'vi' ? 'Khám phá thêm' : 'Continue shopping' }}
    </a>
  </div>

</div>

@endsection
