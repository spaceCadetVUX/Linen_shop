@props([
    'products',                                // LengthAwarePaginator of ProductTranslation — needs `product.thumbnail`, `product.categories` eager-loaded
    'emptyMessage' => 'Chưa có sản phẩm nào.',
])

@if($products->isEmpty())
  <p class="plp-empty">{{ $emptyMessage }}</p>
@else
  <div class="plp-grid">
    @foreach($products as $product)
      <x-product.card :product="$product" :category="$product->product->categories->first()?->slug" />
    @endforeach
  </div>

  @if($products->hasMorePages())
    <div class="plp-load-more">
      <a href="{{ $products->nextPageUrl() }}" class="plp-load-btn">Xem thêm <span>→</span></a>
    </div>
  @endif
@endif
