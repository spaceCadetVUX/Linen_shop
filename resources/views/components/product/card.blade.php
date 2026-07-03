@props([
    'product',           // ProductTranslation — needs `product` relation eager-loaded (at least: thumbnail)
    'category' => null,  // string — data-category for PLP filter JS (omit on homepage grid)
])

@php
    // $product here is a ProductTranslation (name/slug/short_description are per-locale).
    // Real Product fields (price fallback, thumbnail, show_price) live on $product->product.
    $productModel = $product->product;

    $url        = \App\Support\LocaleUrl::for('product', $product->slug, $product->locale);
    $imgPrimary = $productModel->thumbnail?->url;
    $imgAlt     = $productModel->thumbnail?->alt_text ?: $product->name;
    // No distinct hover image wired yet (would need `product.images` eager-loaded — avoid N+1 here).
    $imgHover   = $imgPrimary;
    $name       = $product->name;

    $price      = $product->price ?? $productModel->price;
    $salePriceRaw = $product->sale_price ?? $productModel->sale_price;
    $priceLabel = number_format($price, 0, ',', '.') . ' ₫';
    $salePrice  = ($salePriceRaw && $salePriceRaw < $price)
                    ? number_format($salePriceRaw, 0, ',', '.') . ' ₫'
                    : null;

    $badge      = $product->badge ?? ($salePrice ? 'sale' : null);  // 'new' | 'limited' | 'sale' | null
    $swatches   = $product->swatches ?? [];  // iterable, each item: ->color_class (string), ->active (bool) — not wired yet (needs variant/option data)
@endphp

<div class="shop-card"@if($category) data-category="{{ $category }}"@endif>

  {{-- Image: primary slides out left, hover slides in from right --}}
  <a href="{{ $url }}" style="display:block">
    <div class="shop-card-img-wrap">

      <img src="{{ $imgPrimary }}" alt="{{ $imgAlt }}" class="shop-card-img">
      <img src="{{ $imgHover }}"   alt=""              class="shop-card-img-alt" aria-hidden="true">

      @if($badge)
        @php
          $badgeLabels = [
              'vi' => ['new' => 'Mới', 'limited' => 'Giới hạn', 'sale' => 'Sale'],
              'en' => ['new' => 'New', 'limited' => 'Limited', 'sale' => 'Sale'],
          ];
          $badgeLabel = $badgeLabels[$product->locale][$badge] ?? $badge;
        @endphp
        <span class="shop-badge">{{ $badgeLabel }}</span>
      @endif

    </div>
  </a>

  {{-- Info --}}
  <div class="shop-card-info">

    <a href="{{ $url }}" class="shop-card-name">{{ $name }}</a>

    {{-- Price: show sale price as current, original price struck through --}}
    @if($productModel->show_price)
      <span class="shop-card-price">
        @if($salePrice)
          <span class="t-price-old">{{ $priceLabel }}</span>
          <span>{{ $salePrice }}</span>
        @else
          {{ $priceLabel }}
        @endif
      </span>
    @endif

    @if($swatches && count($swatches))
      <div class="shop-card-swatches">
        @foreach($swatches as $swatch)
          <span class="shop-swatch {{ $swatch->color_class }} {{ ($swatch->active ?? $loop->first) ? 'active' : '' }}"></span>
        @endforeach
      </div>
    @endif

  </div>

</div>
