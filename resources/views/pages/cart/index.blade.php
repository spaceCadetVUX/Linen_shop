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
     CART — guest-session based (X-Session-ID in localStorage, same
     pattern as Wishlist). Server has no way to know the visitor's cart
     at request time, so this is a static shell; app.js fetches
     GET /api/v1/cart on load and hydrates #cartItemsCol / #cartSummary.

     No promo-code field — there is no coupon/discount backend yet.
     Checkout button opens the "Liên hệ đặt hàng" popup instead of real
     checkout — the orders API requires login (no login UI exists) and
     there's no payment gateway integration yet. See doc/todo.md.
     ============================================================ --}}
<div class="cart-page" id="cartPage" data-locale="{{ $locale }}" data-shop-phone="{{ $shopPhone }}">

  <div class="cart-hd">
    <p class="cart-hd-eyebrow">CacyLinen · {{ $locale === 'vi' ? 'Giỏ hàng' : 'Cart' }}</p>
    <h1 class="cart-hd-title">
      {{ $fallbackTitle }} <em id="cartCountLabel"></em>
    </h1>
  </div>

  <div class="cart-layout" id="cartLayout" hidden>

    <div class="cart-items-col" id="cartItemsCol">
      {{-- items injected by JS --}}
    </div>

    <aside class="cart-summary">

      <h2 class="cart-summary-hd">{{ $locale === 'vi' ? 'Đơn hàng' : 'Order Summary' }}</h2>

      <div class="cart-totals">
        <div class="cart-totals-row">
          <span>{{ $locale === 'vi' ? 'Tạm tính' : 'Subtotal' }} <span class="cart-totals-count" id="cartTotalsCount"></span></span>
          <span id="cartSubtotal">—</span>
        </div>
        <div class="cart-totals-row">
          <span>{{ $locale === 'vi' ? 'Phí vận chuyển' : 'Shipping' }}</span>
          <span class="cart-ship-free">{{ $locale === 'vi' ? 'Tính ở bước thanh toán' : 'Calculated at checkout' }}</span>
        </div>
      </div>

      <div class="cart-grand">
        <div class="cart-grand-label">
          <span class="cart-grand-title">{{ $locale === 'vi' ? 'Tổng cộng' : 'Total' }}</span>
          <span class="cart-grand-vat">{{ $locale === 'vi' ? 'Đã bao gồm VAT' : 'VAT included' }}</span>
        </div>
        <span class="cart-grand-amt" id="cartGrandTotal">—</span>
      </div>

      <button class="cart-checkout" id="cartCheckoutBtn" type="button" disabled>
        {{ $locale === 'vi' ? 'Liên hệ đặt hàng' : 'Contact to Order' }}
      </button>
      <p class="cart-checkout-note">
        {{ $locale === 'vi'
            ? 'Thanh toán online đang được hoàn thiện — chọn Zalo, gọi điện hoặc email để đặt hàng ngay.'
            : 'Online payment is still being built — use Zalo, phone, or email to order now.' }}
      </p>

      <ul class="cart-trust">
        <li class="cart-trust-item">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
            <rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
          </svg>
          {{ $locale === 'vi' ? 'Miễn phí giao hàng từ 500.000 ₫' : 'Free shipping over 500,000 ₫' }}
        </li>
        <li class="cart-trust-item">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
            <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
          </svg>
          {{ $locale === 'vi' ? 'Đổi trả miễn phí trong 30 ngày' : 'Free returns within 30 days' }}
        </li>
        <li class="cart-trust-item">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
            <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          {{ $locale === 'vi' ? 'Thanh toán an toàn & bảo mật' : 'Safe & secure checkout' }}
        </li>
      </ul>

    </aside>

  </div>

  <p class="fav-empty" id="cartEmpty" hidden>
    {{ $locale === 'vi'
        ? 'Giỏ hàng đang trống. Bấm "Thêm vào giỏ hàng" trên trang sản phẩm để bắt đầu.'
        : 'Your cart is empty. Tap "Add to bag" on any product page to get started.' }}
  </p>

  <div class="cart-continue">
    <a href="{{ route($locale . '.product.shop') }}" class="cart-continue-link">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
        <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
      </svg>
      {{ $locale === 'vi' ? 'Tiếp tục mua sắm' : 'Continue shopping' }}
    </a>
  </div>

</div>

{{-- ============================================================
     "LIÊN HỆ ĐẶT HÀNG" POPUP — stand-in for real checkout/payment.
     3 tabs: Zalo (link + copy order summary to clipboard — zalo.me has
     no URL prefill param, unlike wa.me), Phone (tel: link), Email (real
     form → POST /api/v1/order-inquiries, server rebuilds the order
     summary from the resolved cart, never trusts client-sent totals).
     ============================================================ --}}
<div class="inquiry-overlay" id="inquiryOverlay"></div>
<div class="inquiry-modal" id="inquiryModal" hidden>
  <div class="inquiry-panel">
    <button class="inquiry-close" id="inquiryClose" type="button" aria-label="{{ $locale === 'vi' ? 'Đóng' : 'Close' }}">✕</button>

    <h2 class="inquiry-title">{{ $locale === 'vi' ? 'Liên hệ đặt hàng' : 'Contact to Order' }}</h2>
    <p class="inquiry-sub">
      {{ $locale === 'vi'
          ? 'Thanh toán online đang được hoàn thiện — chọn 1 trong 3 cách dưới đây để đặt hàng ngay.'
          : 'Online payment is still being built — pick one of the 3 ways below to order now.' }}
    </p>

    <div class="inquiry-tabs" role="tablist">
      <button class="inquiry-tab is-active" type="button" data-tab="zalo">Zalo</button>
      <button class="inquiry-tab" type="button" data-tab="phone">{{ $locale === 'vi' ? 'Gọi điện' : 'Call' }}</button>
      <button class="inquiry-tab" type="button" data-tab="email">Email</button>
    </div>

    <div class="inquiry-pane" data-pane="zalo">
      <p class="inquiry-pane-hint">
        {{ $locale === 'vi'
            ? 'Nội dung đơn hàng đã được sao vào clipboard — dán (Ctrl+V) vào khung chat Zalo sau khi mở.'
            : 'Your order summary has been copied — paste it (Ctrl+V) into the Zalo chat once it opens.' }}
      </p>
      <a href="#" id="inquiryZaloLink" class="inquiry-cta" target="_blank" rel="noopener">
        {{ $locale === 'vi' ? 'Mở Zalo' : 'Open Zalo' }}
      </a>
    </div>

    <div class="inquiry-pane" data-pane="phone" hidden>
      <p class="inquiry-pane-hint">{{ $locale === 'vi' ? 'Gọi trực tiếp cho shop:' : 'Call the shop directly:' }}</p>
      <a href="#" id="inquiryPhoneLink" class="inquiry-cta"></a>
    </div>

    <div class="inquiry-pane" data-pane="email" hidden>
      <form id="inquiryEmailForm">
        <input type="text" name="name" class="inquiry-input" placeholder="{{ $locale === 'vi' ? 'Tên của bạn' : 'Your name' }}" required>
        <input type="tel" name="phone" class="inquiry-input" placeholder="{{ $locale === 'vi' ? 'Số điện thoại' : 'Phone number' }}" required>
        <input type="email" name="email" class="inquiry-input" placeholder="{{ $locale === 'vi' ? 'Email (không bắt buộc)' : 'Email (optional)' }}">
        <textarea name="message" id="inquiryMessage" class="inquiry-textarea" rows="8"></textarea>
        <button type="submit" class="inquiry-submit">{{ $locale === 'vi' ? 'Gửi yêu cầu đặt hàng' : 'Send order request' }}</button>
        <p class="inquiry-form-msg" id="inquiryFormMsg" hidden></p>
      </form>
    </div>

  </div>
</div>

@endsection
