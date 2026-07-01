@props([
    'product',           // Eloquent model: name, slug, price, sale_price?, thumbnail, thumbnail_hover?, meta?, badge?, swatches[]
    'category' => null,  // string — data-category for PLP filter JS (omit on homepage grid)
])

@php
    $url        = url('/products/' . $product->slug);
    $imgPrimary = $product->thumbnail;
    $imgHover   = $product->thumbnail_hover ?? $product->thumbnail;
    $name       = $product->name;
    $meta       = $product->meta ?? '';      // always render the element — CSS hides it (opacity:0), shows on hover
    $price      = number_format($product->price, 0, ',', '.') . ' ₫';
    $salePrice  = $product->sale_price
                    ? number_format($product->sale_price, 0, ',', '.') . ' ₫'
                    : null;
    $badge      = $product->badge ?? null;   // 'new' | 'limited' | 'sale' | null
    $swatches   = $product->swatches ?? [];  // iterable, each item: ->color_class (string), ->active (bool)
@endphp

<div class="shop-card"@if($category) data-category="{{ $category }}"@endif>

  {{-- Image: primary slides out left, hover slides in from right --}}
  <a href="{{ $url }}" style="display:block">
    <div class="shop-card-img-wrap">

      <img src="{{ $imgPrimary }}" alt="{{ $name }}" class="shop-card-img">
      <img src="{{ $imgHover }}"   alt=""             class="shop-card-img-alt" aria-hidden="true">

      @if($badge)
        @php
          $badgeLabel = match($badge) {
              'new'     => 'Mới',
              'limited' => 'Giới hạn',
              'sale'    => 'Sale',
              default   => $badge,
          };
        @endphp
        <span class="shop-badge">{{ $badgeLabel }}</span>
      @endif

    </div>
  </a>

  {{-- Info --}}
  <div class="shop-card-info">

    <a href="{{ $url }}" class="shop-card-name">{{ $name }}</a>

    {{-- Always rendered — position:absolute, opacity:0 by default, visible on hover --}}
    <p class="shop-card-meta">{{ $meta }}</p>

    {{-- Price: show sale price as current, original price struck through --}}
    <span class="shop-card-price">
      @if($salePrice)
        <span class="t-price-old">{{ $price }}</span>
        <span>{{ $salePrice }}</span>
      @else
        {{ $price }}
      @endif
    </span>

    @if($swatches && count($swatches))
      <div class="shop-card-swatches">
        @foreach($swatches as $swatch)
          <span class="shop-swatch {{ $swatch->color_class }} {{ ($swatch->active ?? $loop->first) ? 'active' : '' }}"></span>
        @endforeach
      </div>
    @endif

  </div>

</div>
