<footer class="site-footer">

  {{-- Top: 3-col nav (collections | logo | info) --}}
  <div class="footer-main">

    <div class="footer-col footer-col--left">
      <h4 class="footer-col-title">Bộ sưu tập</h4>
      <nav class="footer-nav">
        <a href="{{ url('/shop/ao-linen') }}">Áo linen</a>
        <a href="{{ url('/shop/quan-vay') }}">Quần &amp; Váy</a>
        <a href="{{ url('/shop/bo-set') }}">Bộ set linen</a>
        <a href="{{ url('/shop/phu-kien') }}">Phụ kiện</a>
      </nav>
    </div>

    <div class="footer-col footer-col--center">
      <a href="{{ url('/') }}" class="footer-logo">CacyLinen</a>
    </div>

    <div class="footer-col footer-col--right">
      <h4 class="footer-col-title">Thông tin</h4>
      <nav class="footer-nav">
        <a href="{{ url('/about') }}">Về CacyLinen</a>
        <a href="{{ url('/contact') }}">Liên hệ</a>
        <a href="{{ url('/privacy-policy') }}">Chính sách bảo mật</a>
        <a href="{{ route(app()->getLocale() . '.size-guide') }}">Hướng dẫn size</a>
      </nav>
    </div>

  </div>

  {{-- Social icons --}}
  <div class="footer-social">

    <a href="#" class="footer-social-link" aria-label="Facebook">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
        <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
      </svg>
    </a>

    <a href="#" class="footer-social-link" aria-label="Instagram">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
        <circle cx="12" cy="12" r="4"/>
        <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>
      </svg>
    </a>

    <a href="#" class="footer-social-link" aria-label="YouTube">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
        <path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46A2.78 2.78 0 0 0 1.46 6.42 29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.95 1.96C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.96-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/>
        <polygon points="9.75 15.02 15.5 12 9.75 8.98 9.75 15.02" fill="white"/>
      </svg>
    </a>

    <a href="#" class="footer-social-link" aria-label="TikTok">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
        <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.32 6.32 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V9.05a8.16 8.16 0 0 0 4.78 1.52V7.12a4.85 4.85 0 0 1-1.01-.43z"/>
      </svg>
    </a>

    <a href="#" class="footer-social-link" aria-label="X (Twitter)">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
      </svg>
    </a>

  </div>

  {{-- Bottom bar: locale selects + payment icons --}}
  <div class="footer-bottom">

    <div class="footer-selects">
      <div class="footer-select-wrap">
        <select class="footer-select" aria-label="Quốc gia / Tiền tệ">
          <option>Việt Nam | VND ₫</option>
        </select>
        <svg class="footer-select-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <polyline points="6 9 12 15 18 9"/>
        </svg>
      </div>
      <div class="footer-select-wrap">
        <select class="footer-select" aria-label="Ngôn ngữ">
          <option>Tiếng Việt</option>
          <option>English</option>
        </select>
        <svg class="footer-select-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <polyline points="6 9 12 15 18 9"/>
        </svg>
      </div>
    </div>

    <div class="footer-payments">
      <span class="footer-pay-icon footer-pay--visa">VISA</span>
      <span class="footer-pay-icon footer-pay--mc">MC</span>
      <span class="footer-pay-icon footer-pay--applepay">⌘Pay</span>
      <span class="footer-pay-icon footer-pay--amex">AMEX</span>
      <span class="footer-pay-icon footer-pay--paypal">PP</span>
      <span class="footer-pay-icon footer-pay--momo">MoMo</span>
      <span class="footer-pay-icon footer-pay--zalopay">Zalo</span>
    </div>

  </div>

  {{-- Legal --}}
  <div class="footer-legal">
    <p>
      &copy; {{ date('Y') }}, CacyLinen &nbsp;&middot;&nbsp;
      <a href="{{ url('/privacy-policy') }}">Chính sách bảo mật</a>
      &nbsp;&middot;&nbsp;
      <a href="{{ url('/terms') }}">Điều khoản dịch vụ</a>
      &nbsp;&middot;&nbsp;
      <a href="{{ url('/contact') }}">Liên hệ</a>
    </p>
  </div>

</footer>
