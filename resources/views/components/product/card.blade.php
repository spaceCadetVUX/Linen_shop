@props([
    'product',           // Eloquent model — name, slug, price, sale_price?, thumbnail, thumbnail_hover?, meta?, badge?, swatches[]
    'category' => null,  // string — data-category value for PLP filter JS
])

@php
    $url        = url('/products/' . $product->slug);
    $imgPrimary = $product->thumbnail;
    $imgHover   = $product->thumbnail_hover ?? $product->thumbnail;
    $name       = $product->name;
    $meta       = $product->meta ?? null;        // e.g. "100% Linen · Cổ chữ V"
    $price      = number_format($product->price, 0, ',', '.') . ' ₫';
    $salePrice  = $product->sale_price
                    ? number_format($product->sale_price, 0, ',', '.') . ' ₫'
                    : null;
    $badge      = $product->badge ?? null;       // 'new' | 'limited' | 'sale' | null
    $swatches   = $product->swatches ?? [];      // collection/array of objects with ->color_class
@endphp

<div class="shop-card"@if($category) data-category="{{ $category }}"@endif>

  {{-- Image area: primary slides left, hover slides in from right --}}
  <a href="{{ $url }}" class="shop-card-img-link">
    <div class="shop-card-img-wrap">

      <img src="{{ $imgPrimary }}" alt="{{ $name }}" class="shop-card-img">
      <img src="{{ $imgHover }}"   alt=""             class="shop-card-img-alt" aria-hidden="true">

      @if($badge)
        @php
          $badgeText = match($badge) {
              'new'     => 'Mới',
              'limited' => 'Giới hạn',
              'sale'    => 'Sale',
              default   => $badge,
          };
          $badgeClass = $badge === 'limited' ? 'shop-badge shop-badge--limited' : 'shop-badge';
        @endphp
        <span class="{{ $badgeClass }}">{{ $badgeText }}</span>
      @endif

    </div>
  </a>

  {{-- Info block --}}
  <div class="shop-card-info">

    <a href="{{ $url }}" class="shop-card-name">{{ $name }}</a>

    @if($meta)
      <p class="shop-card-meta">{{ $meta }}</p>
    @endif

    <span class="shop-card-price">
      @if($salePrice)
        <span class="t-price-old">{{ $price }}</span> {{ $salePrice }}
      @else
        {{ $price }}
      @endif
    </span>

    @if(count($swatches))
      <div class="shop-card-swatches">
        @foreach($swatches as $i => $swatch)
          <span class="shop-swatch {{ $swatch->color_class }}{{ $i === 0 ? ' active' : '' }}"></span>
        @endforeach
      </div>
    @endif

  </div>

</div>
