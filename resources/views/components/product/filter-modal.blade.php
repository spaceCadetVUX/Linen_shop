@props([
    'filterGroups',                 // Collection<FilterGroup> with activeValues eager-loaded
    'activeValueSlugs' => [],       // [group_slug => [value_slug, ...]] — from controller
    'priceBounds' => [],            // ['min' => float, 'max' => float]
    'minPrice' => null,
    'maxPrice' => null,
    'locale' => 'vi',
])

@php
  $priceBoundsMin = (int) floor($priceBounds['min'] ?? 0);
  $priceBoundsMax = (int) max(ceil($priceBounds['max'] ?? 0), $priceBoundsMin + 1);
@endphp

<div class="plp-fmodal" id="plpFmodal" aria-hidden="true">
  <div class="plp-fmodal-overlay" id="plpFmodalOverlay"></div>
  <div class="plp-fmodal-panel" role="dialog" aria-label="Bộ lọc sản phẩm">

    <div class="plp-fmodal-head">
      <span class="plp-fmodal-title">Bộ lọc</span>
      <button class="plp-fmodal-close" id="plpFmodalClose" aria-label="Đóng bộ lọc">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
          <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>

    <div class="plp-fmodal-body">

      @foreach($filterGroups as $group)
        @php
          $values = $group->activeValues;
          $groupLabel = ($locale === 'en' && $group->name_en) ? $group->name_en : $group->name;
          $activeInGroup = $activeValueSlugs[$group->slug] ?? [];
          $isColor = $group->type === \App\Enums\FilterGroupType::Color;
        @endphp

        @if($values->isNotEmpty())
          <div class="plp-fmodal-group" data-filter-group="{{ $group->slug }}">
            <p class="plp-fmodal-group-label">{{ $groupLabel }}</p>

            @if($isColor)
              <div class="plp-fmodal-swatches">
                @foreach($values as $value)
                  @php
                    $valueLabel = ($locale === 'en' && $value->name_en) ? $value->name_en : $value->name;
                    $isActive = in_array($value->slug, $activeInGroup, true);
                  @endphp
                  <button type="button"
                          class="plp-filter-swatch{{ $isActive ? ' active' : '' }}"
                          style="background: {{ $value->color_hex ?: '#ffffff' }}"
                          data-value-slug="{{ $value->slug }}"
                          title="{{ $valueLabel }}"
                          aria-label="{{ $valueLabel }}"
                          aria-pressed="{{ $isActive ? 'true' : 'false' }}"></button>
                @endforeach
              </div>
            @else
              <div class="plp-filter-options">
                @foreach($values as $value)
                  @php
                    $valueLabel = ($locale === 'en' && $value->name_en) ? $value->name_en : $value->name;
                    $isActive = in_array($value->slug, $activeInGroup, true);
                  @endphp
                  <button type="button"
                          class="plp-filter-option{{ mb_strlen($valueLabel) <= 4 ? ' plp-filter-option--size' : '' }}{{ $isActive ? ' active' : '' }}"
                          data-value-slug="{{ $value->slug }}"
                          aria-pressed="{{ $isActive ? 'true' : 'false' }}">{{ $valueLabel }}</button>
                @endforeach
              </div>
            @endif
          </div>
        @endif
      @endforeach

      <div class="plp-fmodal-group">
        <p class="plp-fmodal-group-label">Giá</p>
        <div class="plp-price-range"
             id="plpPriceRange"
             data-bounds-min="{{ $priceBoundsMin }}"
             data-bounds-max="{{ $priceBoundsMax }}"
             data-current-min="{{ $minPrice !== null ? (int) $minPrice : $priceBoundsMin }}"
             data-current-max="{{ $maxPrice !== null ? (int) $maxPrice : $priceBoundsMax }}">
          <div class="plp-price-track-wrap">
            <div class="plp-price-track"></div>
            <div class="plp-price-track-fill" id="plpPriceFill"></div>
            <input type="range" id="plpPriceMin" class="plp-price-input plp-price-input--min" aria-label="Giá tối thiểu">
            <input type="range" id="plpPriceMax" class="plp-price-input plp-price-input--max" aria-label="Giá tối đa">
          </div>
          <div class="plp-price-values">
            <span id="plpPriceMinLabel"></span>
            <span class="plp-price-values-sep">—</span>
            <span id="plpPriceMaxLabel"></span>
          </div>
        </div>
      </div>

    </div>

    <div class="plp-fmodal-foot">
      <button class="plp-fmodal-btn-clear">Xóa tất cả</button>
      <button class="plp-fmodal-btn-apply">Xem kết quả</button>
    </div>

  </div>
</div>

@push('scripts')
<script>
(function () {
  var filterBtn     = document.getElementById('plpFilterToggle');
  var fmodal        = document.getElementById('plpFmodal');
  var fmodalClose   = document.getElementById('plpFmodalClose');
  var fmodalOverlay = document.getElementById('plpFmodalOverlay');

  if (!fmodal) return;

  function openFilter() {
    fmodal.classList.add('open');
    fmodal.setAttribute('aria-hidden', 'false');
    if (filterBtn) filterBtn.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  function closeFilter() {
    fmodal.classList.remove('open');
    fmodal.setAttribute('aria-hidden', 'true');
    if (filterBtn) filterBtn.classList.remove('active');
    document.body.style.overflow = '';
  }

  if (filterBtn)     filterBtn.addEventListener('click', openFilter);
  if (fmodalClose)   fmodalClose.addEventListener('click', closeFilter);
  if (fmodalOverlay) fmodalOverlay.addEventListener('click', closeFilter);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeFilter(); });

  // ---- Filter value toggles (swatches + pills share the same behavior) ----
  fmodal.querySelectorAll('[data-value-slug]').forEach(function (el) {
    el.addEventListener('click', function () {
      var isActive = el.classList.toggle('active');
      el.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
  });

  // ---- Price range slider (dual-thumb, native <input type="range"> pair) ----
  var priceRangeEl = document.getElementById('plpPriceRange');
  var priceBoundsMin = 0;
  var priceBoundsMax = 0;
  var priceMinInput, priceMaxInput;

  function formatVnd(value) {
    return Math.round(value).toLocaleString('vi-VN') + ' ₫';
  }

  if (priceRangeEl) {
    priceBoundsMin = parseInt(priceRangeEl.dataset.boundsMin, 10) || 0;
    priceBoundsMax = parseInt(priceRangeEl.dataset.boundsMax, 10) || (priceBoundsMin + 1);

    priceMinInput = document.getElementById('plpPriceMin');
    priceMaxInput = document.getElementById('plpPriceMax');
    var priceFillEl = document.getElementById('plpPriceFill');
    var priceMinLabel = document.getElementById('plpPriceMinLabel');
    var priceMaxLabel = document.getElementById('plpPriceMaxLabel');
    var priceStep = Math.max(1, Math.round((priceBoundsMax - priceBoundsMin) / 100));

    [priceMinInput, priceMaxInput].forEach(function (input) {
      input.min = priceBoundsMin;
      input.max = priceBoundsMax;
      input.step = priceStep;
    });

    priceMinInput.value = parseInt(priceRangeEl.dataset.currentMin, 10) || priceBoundsMin;
    priceMaxInput.value = parseInt(priceRangeEl.dataset.currentMax, 10) || priceBoundsMax;

    function updatePriceUI() {
      var minVal = parseInt(priceMinInput.value, 10);
      var maxVal = parseInt(priceMaxInput.value, 10);
      var range = (priceBoundsMax - priceBoundsMin) || 1;
      var leftPct = ((minVal - priceBoundsMin) / range) * 100;
      var rightPct = ((maxVal - priceBoundsMin) / range) * 100;

      priceFillEl.style.left = leftPct + '%';
      priceFillEl.style.width = Math.max(0, rightPct - leftPct) + '%';
      priceMinLabel.textContent = formatVnd(minVal);
      priceMaxLabel.textContent = formatVnd(maxVal);
    }

    priceMinInput.addEventListener('input', function () {
      if (parseInt(priceMinInput.value, 10) > parseInt(priceMaxInput.value, 10)) {
        priceMinInput.value = priceMaxInput.value;
      }
      updatePriceUI();
    });
    priceMaxInput.addEventListener('input', function () {
      if (parseInt(priceMaxInput.value, 10) < parseInt(priceMinInput.value, 10)) {
        priceMaxInput.value = priceMinInput.value;
      }
      updatePriceUI();
    });

    updatePriceUI();
  }

  // ---- Clear ----
  var clearBtn = fmodal.querySelector('.plp-fmodal-btn-clear');
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      fmodal.querySelectorAll('[data-value-slug].active').forEach(function (el) {
        el.classList.remove('active');
        el.setAttribute('aria-pressed', 'false');
      });
      if (priceRangeEl) {
        priceMinInput.value = priceBoundsMin;
        priceMaxInput.value = priceBoundsMax;
        priceMinInput.dispatchEvent(new Event('input'));
      }
    });
  }

  // ---- Apply: ?{group_slug}=value1,value2 per group + min_price/max_price ----
  var applyBtn = fmodal.querySelector('.plp-fmodal-btn-apply');
  if (applyBtn) {
    applyBtn.addEventListener('click', function () {
      var url = new URL(window.location.href);

      fmodal.querySelectorAll('.plp-fmodal-group[data-filter-group]').forEach(function (groupEl) {
        var slugs = [];
        groupEl.querySelectorAll('[data-value-slug].active').forEach(function (el) {
          slugs.push(el.dataset.valueSlug);
        });

        if (slugs.length) {
          url.searchParams.set(groupEl.dataset.filterGroup, slugs.join(','));
        } else {
          url.searchParams.delete(groupEl.dataset.filterGroup);
        }
      });

      if (priceRangeEl) {
        var minVal = parseInt(priceMinInput.value, 10);
        var maxVal = parseInt(priceMaxInput.value, 10);

        if (minVal <= priceBoundsMin) {
          url.searchParams.delete('min_price');
        } else {
          url.searchParams.set('min_price', minVal);
        }

        if (maxVal >= priceBoundsMax) {
          url.searchParams.delete('max_price');
        } else {
          url.searchParams.set('max_price', maxVal);
        }
      }

      url.searchParams.delete('page');
      window.location.href = url.toString();
    });
  }
})();
</script>
@endpush
