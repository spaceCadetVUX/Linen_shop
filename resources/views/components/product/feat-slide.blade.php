@props([
    'product',          // ProductTranslation — needs `product` relation eager-loaded (at least: images)
    'active' => false,
])

@php
    $productModel = $product->product;

    $url    = \App\Support\LocaleUrl::for('product', $product->slug, $product->locale);
    [$cardPrimaryImage] = $productModel->cardImages();
    $imgUrl = $cardPrimaryImage?->url;
    $imgAlt = $cardPrimaryImage?->alt_text ?: $product->name;

    $price        = $product->price ?? $productModel->price;
    $salePriceRaw = $product->sale_price ?? $productModel->sale_price;
    $priceLabel   = number_format($price, 0, ',', '.').' ₫';
    $salePrice    = ($salePriceRaw && $salePriceRaw < $price)
        ? number_format($salePriceRaw, 0, ',', '.').' ₫'
        : null;
@endphp

<div
    class="feat-slide @if($active) is-active @endif"
    data-name="{{ $product->name }}"
    data-price="{{ $salePrice ?? $priceLabel }}"
    data-url="{{ $url }}"
>
    <a href="{{ $url }}">
        <img src="{{ $imgUrl }}" alt="{{ $imgAlt }}" class="feat-slide-img">
    </a>
</div>
