<footer class="site-footer">

  {{-- Top: 3-col nav (collections | logo | info) --}}
  <div class="footer-main">

    <div class="footer-col footer-col--left">
      <h4 class="footer-col-title">{{ __('footer.collections.title') }}</h4>
      <nav class="footer-nav">
        @forelse($footerCategories as $cat)
          <a href="{{ $cat['url'] }}">{{ $cat['name'] }}</a>
        @empty
          <a href="{{ $footerShopUrl }}">{{ __('footer.collections.view_all') }}</a>
        @endforelse
      </nav>
    </div>

    <div class="footer-col footer-col--center">
      <a href="{{ url('/') }}" class="footer-logo">CacyLinen</a>
    </div>

    <div class="footer-col footer-col--right">
      <h4 class="footer-col-title">{{ __('footer.info.title') }}</h4>
      <nav class="footer-nav">
        <a href="{{ route(app()->getLocale() . '.about') }}">{{ __('footer.info.about') }}</a>
        <a href="{{ route(app()->getLocale() . '.size-guide') }}">{{ __('footer.info.size_guide') }}</a>
        @foreach($footerPages as $page)
          <a href="{{ $page['url'] }}">{{ $page['name'] }}</a>
        @endforeach
      </nav>
    </div>

  </div>

  {{-- Social icons — hidden per-platform when no URL set in Business Profile --}}
  <div class="footer-social">

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

  {{-- Shipping carriers — logos only, order = admin drag order in Business Profile --}}
  @if(($shippingCarriers = \App\Models\Setting::get('shipping_carriers')) && count($shippingCarriers))
    <div class="footer-shipping">
      @foreach($shippingCarriers as $carrier)
        @continue(empty($carrier['logo']))
        @php
          $logoUrl = str_starts_with((string) $carrier['logo'], 'http')
              ? $carrier['logo']
              : asset('storage/' . ltrim((string) $carrier['logo'], '/'));
          $carrierName = $carrier['name'] ?? 'Đơn vị vận chuyển';
        @endphp
        @if(! empty($carrier['url']))
          <a href="{{ $carrier['url'] }}" class="footer-shipping-link" aria-label="{{ $carrierName }}" target="_blank" rel="noopener">
            <img src="{{ $logoUrl }}" alt="{{ $carrierName }}" class="footer-shipping-logo" loading="lazy">
          </a>
        @else
          <span class="footer-shipping-link" aria-label="{{ $carrierName }}">
            <img src="{{ $logoUrl }}" alt="{{ $carrierName }}" class="footer-shipping-logo" loading="lazy">
          </span>
        @endif
      @endforeach
    </div>
  @endif

  {{-- Bottom bar: locale/currency switch + payment icons --}}
  <div class="footer-bottom">

    @php
      $currentLocale = app()->getLocale();
      $viUrl = $alternateUrls['vi'] ?? '/vi/';
      $enUrl = $alternateUrls['en'] ?? '/en/';
    @endphp
    <div class="mega-intl-switch">
      <button type="button" class="mega-intl-trigger" aria-haspopup="true">
        <svg class="mega-intl-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
          <circle cx="12" cy="12" r="9"/>
          <path d="M3 12h18M12 3c2.4 2.6 3.7 5.7 3.7 9s-1.3 6.4-3.7 9c-2.4-2.6-3.7-5.7-3.7-9s1.3-6.4 3.7-9z"/>
        </svg>
        <span class="mega-intl-current">{{ $currentLocale === 'en' ? 'English · USD $' : 'Tiếng Việt · VND ₫' }}</span>
        <svg class="mega-intl-chevron" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path d="m6 9 6 6 6-6"/>
        </svg>
      </button>
      <div class="mega-intl-panel" role="menu">
        <a href="{{ $viUrl }}" class="mega-intl-option @if($currentLocale === 'vi') is-active @endif" role="menuitem">Tiếng Việt · VND ₫</a>
        <a href="{{ $enUrl }}" class="mega-intl-option @if($currentLocale === 'en') is-active @endif" role="menuitem">English · USD $</a>
      </div>
    </div>

    <div class="footer-payments">
      @foreach(\App\Models\Setting::get('payment_methods') as $method)
        @continue(empty($method['logo']))
        @php
          $methodLogoUrl = str_starts_with((string) $method['logo'], 'http')
              ? $method['logo']
              : asset('storage/' . ltrim((string) $method['logo'], '/'));
          $methodName = $method['name'] ?? 'Phương thức thanh toán';
        @endphp
        @if(! empty($method['url']))
          <a href="{{ $method['url'] }}" class="footer-payment-link" aria-label="{{ $methodName }}" target="_blank" rel="noopener">
            <img src="{{ $methodLogoUrl }}" alt="{{ $methodName }}" class="footer-payment-logo" loading="lazy">
          </a>
        @else
          <span class="footer-payment-link" aria-label="{{ $methodName }}">
            <img src="{{ $methodLogoUrl }}" alt="{{ $methodName }}" class="footer-payment-logo" loading="lazy">
          </span>
        @endif
      @endforeach
    </div>

  </div>

  {{-- Legal --}}
  <div class="footer-legal">
    @if($legalName = \App\Models\Setting::get('company_legal_name'))
      <p class="footer-legal-company">
        {{ $legalName }}
        @if($taxCode = \App\Models\Setting::get('company_tax_code'))
          &nbsp;&middot;&nbsp;MST: {{ $taxCode }}
        @endif
        @if($addr = \App\Models\Setting::get('contact_address'))
          &nbsp;&middot;&nbsp;{{ $addr }}
        @endif
        @if($phone = \App\Models\Setting::get('contact_phone'))
          &nbsp;&middot;&nbsp;{{ $phone }}
        @endif
        @if($email = \App\Models\Setting::get('contact_email'))
          &nbsp;&middot;&nbsp;{{ $email }}
        @endif
      </p>
    @endif
    <p>
      &copy; {{ date('Y') }}, CacyLinen &nbsp;&middot;&nbsp;
      <a href="{{ route(app()->getLocale() . '.page.show', ['slug' => 'privacy-policy']) }}">{{ __('footer.legal.privacy') }}</a>
      &nbsp;&middot;&nbsp;
      <a href="{{ route(app()->getLocale() . '.page.show', ['slug' => 'terms']) }}">{{ __('footer.legal.terms') }}</a>
      &nbsp;&middot;&nbsp;
      <a href="{{ route(app()->getLocale() . '.page.show', ['slug' => 'contact']) }}">{{ __('footer.legal.contact') }}</a>
    </p>
  </div>

</footer>
