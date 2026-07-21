@props([
    'products',                                // LengthAwarePaginator of ProductTranslation — needs `product.thumbnail`, `product.categories` eager-loaded
    'emptyMessage' => null,                    // callers should always pass a locale-aware message; this default only covers a missed call site
    'autoLoad' => false,                       // true = auto-fetch next page on scroll (PLP); false = click "Xem thêm" (category)
])

@php
  $emptyMessage ??= app()->getLocale() === 'vi' ? 'Chưa có sản phẩm nào.' : 'No products yet.';
@endphp

@if($products->isEmpty())
  <p class="plp-empty">{{ $emptyMessage }}</p>
@else
  <div class="plp-grid" id="plpGrid">
    @foreach($products as $product)
      <x-product.card :product="$product" :category="$product->product->categories->first()?->slug" />
    @endforeach
  </div>

  @if($products->hasMorePages())
    <div class="plp-load-more" id="plpLoadMore" data-autoload="{{ $autoLoad ? '1' : '0' }}">
      <a href="{{ $products->nextPageUrl() }}" class="plp-load-btn">{{ app()->getLocale() === 'vi' ? 'Xem thêm' : 'Load more' }} <span>→</span></a>
    </div>
  @endif
@endif

@once
@push('scripts')
<script>
{{-- Progressive enhancement: "Xem thêm" is a real <a href> to a real paginated
     URL (SEO-crawlable, works with JS off). With JS on, we intercept it and
     fetch/append via AJAX instead of a full page reload. `data-autoload="1"`
     (PLP only, see product/index.blade.php) also fires this on scroll via
     IntersectionObserver — category keeps click-to-load. --}}
(function () {
  function initPlpLoadMore() {
    var grid = document.getElementById('plpGrid');
    var loadMore = document.getElementById('plpLoadMore');
    if (!grid || !loadMore) return;

    var loading = false;
    var observer = null;

    function loadNext() {
      var link = loadMore.querySelector('a.plp-load-btn');
      if (!link || loading) return;
      var url = link.getAttribute('href');

      loading = true;
      loadMore.classList.add('is-loading');

      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          grid.insertAdjacentHTML('beforeend', data.html);
          history.replaceState(null, '', url);

          if (data.hasMore && data.nextPageUrl) {
            link.setAttribute('href', data.nextPageUrl);
          } else {
            if (observer) observer.disconnect();
            loadMore.remove();
          }
        })
        .catch(function () {
          // Network/JSON failure — leave the real link in place so the user
          // can still click through to a full-page load.
        })
        .finally(function () {
          loading = false;
          loadMore.classList.remove('is-loading');
        });
    }

    var link = loadMore.querySelector('a.plp-load-btn');
    if (link) {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        loadNext();
      });
    }

    if (loadMore.dataset.autoload === '1' && 'IntersectionObserver' in window) {
      observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) loadNext();
        });
      }, { rootMargin: '600px 0px' });
      observer.observe(loadMore);
    }
  }

  document.addEventListener('DOMContentLoaded', initPlpLoadMore);
})();
</script>
@endpush
@endonce
