@extends('layouts.app')

@section('title', 'Giỏ hàng — LINNÉ')
@section('meta-description', 'Xem lại các sản phẩm trong giỏ hàng và tiến hành thanh toán.')
@section('body-class', 'page-pd')

@section('content')

    <!-- ==============================  CART PAGE  ============================== -->
    <div class="cart-page">

      <!-- Header -->
      <div class="cart-hd">
        <p class="cart-hd-eyebrow">LINNÉ · Giỏ hàng</p>
        <h1 class="cart-hd-title">Giỏ hàng <em id="cartCountLabel">(3)</em></h1>
      </div>

      <!-- 2-col layout: items + summary -->
      <div class="cart-layout">

        <!-- LEFT: cart items -->
        <div class="cart-items-col">

          <!-- Item 1 -->
          <article class="cart-item">
            <a href="{{ url('/products/ao-linen-co-chu-v') }}" class="cart-item-thumb">
              <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Black.jpg?v=1779070489&width=400"
                   alt="Áo linen cổ chữ V" class="cart-item-thumb-img">
            </a>
            <div class="cart-item-body">
              <div class="cart-item-top">
                <div class="cart-item-meta">
                  <a href="{{ url('/products/ao-linen-co-chu-v') }}" class="cart-item-name">Áo linen cổ chữ V</a>
                  <p class="cart-item-variant">Ivory · Size S</p>
                  <p class="cart-item-material">100% Linen</p>
                </div>
                <button class="cart-item-del" aria-label="Xóa sản phẩm">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                  </svg>
                </button>
              </div>
              <div class="cart-item-foot">
                <div class="cart-qty">
                  <button class="cart-qty-btn" aria-label="Giảm số lượng">−</button>
                  <span class="cart-qty-val">1</span>
                  <button class="cart-qty-btn" aria-label="Tăng số lượng">+</button>
                </div>
                <span class="cart-item-price">660.000 ₫</span>
              </div>
            </div>
          </article>

          <!-- Item 2 -->
          <article class="cart-item">
            <a href="{{ url('/products/ao-blouse-that-no') }}" class="cart-item-thumb">
              <img src="https://elleandriley.com/cdn/shop/files/Bandana-Scarf-Black-Sand_80abcf68-ce45-46c1-8665-8419cb22e876.jpg?v=1781216042&width=400"
                   alt="Áo blouse thắt nơ" class="cart-item-thumb-img">
            </a>
            <div class="cart-item-body">
              <div class="cart-item-top">
                <div class="cart-item-meta">
                  <a href="{{ url('/products/ao-blouse-that-no') }}" class="cart-item-name">Áo blouse thắt nơ</a>
                  <p class="cart-item-variant">Cognac · Size M</p>
                  <p class="cart-item-material">100% Linen</p>
                </div>
                <button class="cart-item-del" aria-label="Xóa sản phẩm">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                  </svg>
                </button>
              </div>
              <div class="cart-item-foot">
                <div class="cart-qty">
                  <button class="cart-qty-btn" aria-label="Giảm số lượng">−</button>
                  <span class="cart-qty-val">1</span>
                  <button class="cart-qty-btn" aria-label="Tăng số lượng">+</button>
                </div>
                <span class="cart-item-price">720.000 ₫</span>
              </div>
            </div>
          </article>

          <!-- Item 3 -->
          <article class="cart-item">
            <a href="{{ url('/products/ao-crop-linen') }}" class="cart-item-thumb">
              <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Red.jpg?v=1778217693&width=400"
                   alt="Áo crop linen" class="cart-item-thumb-img">
            </a>
            <div class="cart-item-body">
              <div class="cart-item-top">
                <div class="cart-item-meta">
                  <a href="{{ url('/products/ao-crop-linen') }}" class="cart-item-name">Áo crop linen</a>
                  <p class="cart-item-variant">Rouge · Size XS</p>
                  <p class="cart-item-material">100% Linen · Crop fit</p>
                </div>
                <button class="cart-item-del" aria-label="Xóa sản phẩm">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                  </svg>
                </button>
              </div>
              <div class="cart-item-foot">
                <div class="cart-qty">
                  <button class="cart-qty-btn" aria-label="Giảm số lượng">−</button>
                  <span class="cart-qty-val">2</span>
                  <button class="cart-qty-btn" aria-label="Tăng số lượng">+</button>
                </div>
                <span class="cart-item-price">1.240.000 ₫</span>
              </div>
            </div>
          </article>

          <!-- Continue shopping -->
          <div class="cart-continue">
            <a href="{{ url('/collections') }}" class="cart-continue-link">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
              </svg>
              Tiếp tục mua sắm
            </a>
          </div>

        </div><!-- /.cart-items-col -->

        <!-- RIGHT: order summary (sticky) -->
        <aside class="cart-summary">

          <h2 class="cart-summary-hd">Đơn hàng</h2>

          <!-- Promo code -->
          <div class="cart-promo">
            <div class="cart-promo-field">
              <input type="text" class="cart-promo-input" placeholder="Mã giảm giá">
              <button class="cart-promo-apply">Áp dụng</button>
            </div>
          </div>

          <!-- Totals rows -->
          <div class="cart-totals">
            <div class="cart-totals-row">
              <span>Tạm tính <span class="cart-totals-count">(4 sản phẩm)</span></span>
              <span>2.620.000 ₫</span>
            </div>
            <div class="cart-totals-row">
              <span>Phí vận chuyển</span>
              <span class="cart-ship-free">Miễn phí</span>
            </div>
          </div>

          <!-- Grand total -->
          <div class="cart-grand">
            <div class="cart-grand-label">
              <span class="cart-grand-title">Tổng cộng</span>
              <span class="cart-grand-vat">Đã bao gồm VAT</span>
            </div>
            <span class="cart-grand-amt">2.620.000 ₫</span>
          </div>

          <!-- Checkout CTA -->
          <button class="cart-checkout">Thanh toán</button>

          <!-- Trust signals -->
          <ul class="cart-trust">
            <li class="cart-trust-item">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                <rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
              </svg>
              Miễn phí giao hàng từ 500.000 ₫
            </li>
            <li class="cart-trust-item">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
              </svg>
              Đổi trả miễn phí trong 30 ngày
            </li>
            <li class="cart-trust-item">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
              Thanh toán an toàn &amp; bảo mật
            </li>
          </ul>

        </aside><!-- /.cart-summary -->

      </div><!-- /.cart-layout -->

    </div><!-- /.cart-page -->

@endsection
