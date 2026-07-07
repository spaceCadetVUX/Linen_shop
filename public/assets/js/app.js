/* ============================================
   CacyLinen — main.js
============================================ */


/* ---------- Hero Logo: shrink on scroll → nav-logo fade in ---------- */
(function () {
  const heroLogoWrap = document.querySelector('.hero-logo-wrap');
  const navLogo      = document.querySelector('.nav-logo');
  if (!heroLogoWrap || !navLogo) return;

  /* Nav logo ẩn lúc đầu — hero logo đang hiện */
  navLogo.style.transition = 'none'; /* tắt CSS transition, JS lo animation */
  navLogo.style.opacity    = '0';

  /* Khoảng scroll để hoàn thành transition: 0 → 40% viewport height */
  const FADE_END = window.innerHeight * 0.40;

  /* Expo-out: nhanh lúc đầu, chậm dần khi settle — feel luxury */
  function expoOut(t) {
    return t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
  }

  function update() {
    const t     = Math.min(1, Math.max(0, window.scrollY / FADE_END));
    const e     = expoOut(t);

    /* Effect B — Blur dissolve:
       opacity tan nhanh, scale thu nhẹ (1→0.93), blur tăng dần (0→12px) */
    heroLogoWrap.style.opacity   = Math.max(0, 1 - e * 1.7).toFixed(4);
    heroLogoWrap.style.transform = `scale(${(1 - e * 0.07).toFixed(4)})`;
    heroLogoWrap.style.filter    = `blur(${(e * 12).toFixed(2)}px)`;

    /* Nav logo fade in sau một chút — tránh overlap lộ liễu */
    navLogo.style.opacity = Math.min(1, Math.max(0, (e - 0.18) * 1.4)).toFixed(4);
  }

  window.addEventListener('scroll', update, { passive: true });
  update(); /* chạy ngay để set trạng thái ban đầu */

  /* Khôi phục transition cho nav logo sau khi JS đã set initial state */
  requestAnimationFrame(() => {
    navLogo.style.transition = '';
  });
}());


/* ---------- Navbar: transparent ↔ filled ---------- */
const navbar = document.getElementById('navbar');

var isStaticPage = !!document.querySelector('.pd-section') || document.body.classList.contains('page-pd');

function updateNav() {
  navbar.classList.toggle('filled', isStaticPage || window.scrollY > 10);
}

window.addEventListener('scroll', updateNav, { passive: true });
updateNav();




/* ---------- Mega menu ---------- */
(function () {
  const navbar      = document.getElementById('navbar');
  const menuBtn     = document.getElementById('menuBtn');
  const menuLabel   = document.getElementById('menuLabel');
  const megaWrap    = document.getElementById('megaWrap');
  const megaClose   = document.getElementById('megaClose');
  const megaOverlay = document.getElementById('megaOverlay');

  if (!menuBtn || !megaWrap) return;

  let open = false;

  function openMenu() {
    open = true;
    megaWrap.classList.add('open');
    navbar.classList.add('menu-open');
    menuBtn.setAttribute('aria-expanded', 'true');
    menuLabel.textContent = 'Fermer';
  }

  function closeMenu() {
    open = false;
    megaWrap.classList.remove('open');
    navbar.classList.remove('menu-open');
    menuBtn.setAttribute('aria-expanded', 'false');
    menuLabel.textContent = 'Menu';
    document.querySelectorAll('.mega-group.open').forEach(g => g.classList.remove('open'));
  }

  menuBtn.addEventListener('click',     () => open ? closeMenu() : openMenu());
  megaClose.addEventListener('click',   closeMenu);
  megaOverlay.addEventListener('click', closeMenu);
  document.addEventListener('keydown',  e => { if (e.key === 'Escape' && open) closeMenu(); });

  /* Mobile accordion — group headers */
  document.querySelectorAll('.mega-group-hd').forEach(hd => {
    hd.addEventListener('click', () => {
      if (window.innerWidth > 768) return;
      const group = hd.closest('.mega-group');
      if (!group.querySelector('.mega-group-links')) return;
      const wasOpen = group.classList.contains('open');
      document.querySelectorAll('.mega-group.open').forEach(g => g.classList.remove('open'));
      if (!wasOpen) group.classList.add('open');
    });
  });
}());

/* ---------- Mega menu: col 3 dynamic products ---------- */
/* DATA comes from #megaWrap[data-mega-products] — a { [categorySlug]: [{name,image,url}] }
   map rendered server-side from real Category/Product data (see CategoryService::getMegaMenuData). */
(function () {
  if (window.innerWidth <= 768) return;

  var megaWrap = document.getElementById('megaWrap');
  var grid     = document.getElementById('megaProductGrid');
  var eyebrow  = document.getElementById('megaProductsEyebrow');
  var empty    = document.getElementById('megaProductsEmpty');
  if (!megaWrap || !grid) return;

  var cards = Array.prototype.slice.call(grid.querySelectorAll('.mega-product-card'));
  if (!cards.length) return;

  var DATA = {};
  try {
    DATA = JSON.parse(megaWrap.dataset.megaProducts || '{}');
  } catch (e) {
    DATA = {};
  }

  var currentCat = null;
  var swapTimer  = null;

  function swap(cat, label) {
    if (cat === currentCat) return;
    currentCat = cat;
    var products = DATA[cat] || [];

    clearTimeout(swapTimer);

    /* Phase 1: fade out */
    cards.forEach(function (c) {
      c.classList.remove('is-entering');
      c.classList.add('is-leaving');
    });

    /* Phase 2: after fade-out completes, update content then animate in */
    swapTimer = setTimeout(function () {
      var hasProducts = products.length > 0;
      grid.hidden = !hasProducts;
      if (empty) empty.hidden = hasProducts;

      cards.forEach(function (card, i) {
        var p = products[i];
        card.style.display = p ? '' : 'none';
        if (!p) return;

        var img  = card.querySelector('.mega-product-img');
        var name = card.querySelector('.mega-product-name');
        img.src  = p.image;
        img.alt  = p.name;
        name.textContent = p.name;
        card.href = p.url;
      });
      if (eyebrow) eyebrow.textContent = label;

      cards.forEach(function (c) { c.classList.remove('is-leaving'); });
      void grid.offsetWidth;
      cards.forEach(function (c) { c.classList.add('is-entering'); });
    }, 190);
  }

  document.querySelectorAll('.mega-col--collection [data-mega-cat]').forEach(function (link) {
    link.addEventListener('mouseenter', function () {
      swap(link.dataset.megaCat, link.dataset.megaLabel || 'Sản phẩm tiêu biểu');
    });
  });
}());

/* ---------- Mega menu: col 1 "Sản phẩm mới" auto-slide (5s loop) ---------- */
(function () {
  var slider = document.getElementById('megaNewSlider');
  if (!slider) return;

  var slides = Array.prototype.slice.call(slider.querySelectorAll('.mega-new-slide'));
  if (slides.length <= 1) return;

  var current = 0;
  setInterval(function () {
    slides[current].classList.remove('is-active');
    current = (current + 1) % slides.length;
    slides[current].classList.add('is-active');
  }, 5000);
}());

/* ---------- Scroll-reveal: cat blocks + section dividers ---------- */
(function () {
  const blockObs = new IntersectionObserver(
    entries => entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('in-view');
        blockObs.unobserve(e.target);
      }
    }),
    { threshold: 0.06 }
  );

  const dividerObs = new IntersectionObserver(
    entries => entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('in-view');
        dividerObs.unobserve(e.target);
      }
    }),
    { threshold: 0, rootMargin: '0px 0px -20px 0px' }
  );

  document.querySelectorAll('.cat-block').forEach(el => blockObs.observe(el));
  document.querySelectorAll('.section-divider').forEach(el => dividerObs.observe(el));
  document.querySelectorAll('.brand-stmt-body, .brand-stmt-cta').forEach(el => blockObs.observe(el));
  document.querySelectorAll('.edit-grid, .feat-product, .shop-section, .dual-edit, .tiktok-section, .journal-section').forEach(el => blockObs.observe(el));
}());

/* ---------- TikTok carousel ---------- */
(function () {
  const section = document.getElementById('tiktokSection');
  if (!section) return;

  const SLIDES = [
    { img: 'https://elleandriley.com/cdn/shop/files/Slim_tee_Pale_Blue.jpg?v=1778216637&width=2160',       brand: 'CacyLinen', name: 'Áo linen cổ chữ V',   price: '660.000 ₫'   },
    { img: 'https://elleandriley.com/cdn/shop/files/Slim_Tee_BrownMelange2.jpg?v=1778217470&width=2160',  brand: 'CacyLinen', name: 'Áo blouse thắt nơ',    price: '720.000 ₫'   },
    { img: 'https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Camel3.jpg?v=1779070696&width=2160',    brand: 'CacyLinen', name: 'Đầm linen cổ chữ V',   price: '1.290.000 ₫' },
    { img: 'https://elleandriley.com/cdn/shop/files/Slim_Tee_BrownMelange.jpg?v=1778217470&width=2160',   brand: 'CacyLinen', name: 'Áo crop linen',         price: '620.000 ₫'   },
    { img: 'https://elleandriley.com/cdn/shop/files/Slim_Tee_Birch.jpg?v=1778217589&width=2160',          brand: 'CacyLinen', name: 'Áo linen oversized',    price: '680.000 ₫'   },
  ];

  const N      = SLIDES.length;
  const CENTER = 2;   // index của DOM item trung tâm trong grid (0-based)
  let   cur    = 2;   // slide index đang active
  let   locked = false;

  const track = section.querySelector('.tiktok-track');
  const items = Array.from(section.querySelectorAll('.tiktok-item'));
  const dots  = Array.from(section.querySelectorAll('.tiktok-dot'));
  const card  = section.querySelector('.tiktok-product-card');
  const thumb = section.querySelector('.tiktok-product-thumb');
  const bEl   = section.querySelector('.tiktok-product-brand');
  const nEl   = section.querySelector('.tiktok-product-name');
  const pEl   = section.querySelector('.tiktok-product-price');

  /* Scale tương ứng với vị trí grid — không đổi suốt vòng đời */
  const SCALES = items.map((_, pos) => pos === CENTER ? 1 : 1.10);

  /* Cập nhật dots + product card (instant, không fade) */
  function syncBottom() {
    dots.forEach((d, i) => d.classList.toggle('tiktok-dot--active', i === cur));
    const s = SLIDES[cur];
    thumb.src       = s.img;
    thumb.alt       = s.name;
    bEl.textContent = s.brand;
    nEl.textContent = s.name;
    pEl.textContent = s.price;
  }

  /* Đồng bộ width của card với center column */
  function syncCardWidth() {
    if (window.innerWidth <= 640 || !card) return;
    card.style.width = items[CENTER].offsetWidth + 'px';
  }

  /* Init — set data-pos + src ngay (không animation) */
  function initData() {
    items.forEach((item, pos) => {
      const idx = ((cur - CENTER + pos) % N + N) % N;
      const s   = SLIDES[idx];
      item.dataset.pos = pos - CENTER;
      const img = item.querySelector('.tiktok-img');
      img.src = s.img;
      img.alt = s.name;
    });
    syncBottom();
  }

  /* Video auto-play — sẵn sàng khi thay <img> bằng <video class="tiktok-video"> */
  function syncVideoPlayback() {
    items.forEach(item => {
      const video = item.querySelector('.tiktok-video');
      if (!video) return;
      parseInt(item.dataset.pos, 10) === 0
        ? video.play().catch(() => {})
        : (video.pause(), (video.currentTime = 0));
    });
  }

  /* Auto-advance */
  let autoTimer = null;
  function startAuto() {
    clearInterval(autoTimer);
    autoTimer = setInterval(() => goTo(cur + 1), 5000);
  }
  function stopAuto() { clearInterval(autoTimer); }

  /* -------------------------------------------------------
     Navigate — crossfade + subtle zoom (editorial style)
  ------------------------------------------------------- */
  const FADE_OUT = 240;
  const FADE_IN  = 500;
  const EASE_IN  = 'cubic-bezier(0.16, 1, 0.3, 1)'; // ease-out-expo
  const DRIFT    = 32; // px — đủ để cảm nhận hướng, không lộ liễu

  function goTo(rawIdx) {
    if (locked) return;
    const next = ((rawIdx % N) + N) % N;
    if (next === cur) return;
    locked = true;

    const dir = rawIdx > cur ? 1 : -1;

    // Phase 1: fade out + drift nhẹ theo hướng
    items.forEach((item, pos) => {
      const img = item.querySelector('.tiktok-img');
      img.style.transition = `opacity ${FADE_OUT}ms ease, transform ${FADE_OUT}ms ease`;
      img.style.opacity    = '0';
      img.style.transform  = `scale(${SCALES[pos]}) translateX(${-dir * DRIFT}px)`;
    });

    setTimeout(() => {
      cur = next;
      items.forEach((item, pos) => {
        const idx = ((cur - CENTER + pos) % N + N) % N;
        const s   = SLIDES[idx];
        const img = item.querySelector('.tiktok-img');
        img.src = s.img;
        img.alt = s.name;
        // đặt sẵn từ phía đối diện, invisible
        img.style.transition = 'none';
        img.style.opacity    = '0';
        img.style.transform  = `scale(${SCALES[pos]}) translateX(${dir * DRIFT}px)`;
      });

      syncBottom();
      syncVideoPlayback();
      startAuto();

      // Phase 2: fade in + drift về 0
      requestAnimationFrame(() => requestAnimationFrame(() => {
        items.forEach((item, pos) => {
          const img = item.querySelector('.tiktok-img');
          img.style.transition = `opacity ${FADE_IN}ms ease, transform ${FADE_IN}ms ${EASE_IN}`;
          img.style.opacity    = '1';
          img.style.transform  = `scale(${SCALES[pos]}) translateX(0)`;
        });

        setTimeout(() => {
          items.forEach(item => {
            const img = item.querySelector('.tiktok-img');
            img.style.transition = 'none';
            img.style.transform  = '';
            img.style.opacity    = '';
            requestAnimationFrame(() => requestAnimationFrame(() => {
              img.style.transition = '';
            }));
          });
          locked = false;
        }, FADE_IN + 20);
      }));
    }, FADE_OUT + 16);
  }

  /* --- Events --- */

  /* Arrows */
  section.querySelector('.tiktok-arrow--prev').addEventListener('click', () => goTo(cur - 1));
  section.querySelector('.tiktok-arrow--next').addEventListener('click', () => goTo(cur + 1));

  /* Dots */
  dots.forEach((d, i) => d.addEventListener('click', () => goTo(i)));

  /* Click side item → navigate đến nó */
  items.forEach(item => {
    item.addEventListener('click', () => {
      const dp = parseInt(item.dataset.pos, 10);
      if (dp !== 0) goTo(cur + dp);
    });
  });

  /* Touch swipe */
  let tx = 0, ty = 0;
  track.addEventListener('touchstart', e => {
    tx = e.touches[0].clientX;
    ty = e.touches[0].clientY;
  }, { passive: true });
  track.addEventListener('touchend', e => {
    const dx = e.changedTouches[0].clientX - tx;
    const dy = e.changedTouches[0].clientY - ty;
    if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 44) {
      goTo(dx < 0 ? cur + 1 : cur - 1);
    }
  }, { passive: true });

  /* Mouse drag (desktop) */
  let dragStart = null;
  track.addEventListener('pointerdown', e => { dragStart = e.clientX; });
  track.addEventListener('pointerup', e => {
    if (dragStart === null) return;
    const dx = e.clientX - dragStart;
    dragStart = null;
    if (Math.abs(dx) > 55) goTo(dx < 0 ? cur + 1 : cur - 1);
  });
  track.addEventListener('pointerleave', () => { dragStart = null; });

  /* Keyboard */
  section.setAttribute('tabindex', '-1');
  section.addEventListener('keydown', e => {
    if (e.key === 'ArrowLeft')  goTo(cur - 1);
    if (e.key === 'ArrowRight') goTo(cur + 1);
  });

  /* Hover: pause auto-advance */
  section.addEventListener('mouseenter', stopAuto, { passive: true });
  section.addEventListener('mouseleave', startAuto, { passive: true });

  /* --- Init --- */
  initData();
  syncCardWidth();
  startAuto();
  window.addEventListener('resize', syncCardWidth, { passive: true });
}());

/* ---------- Shop tabs ---------- */
(function () {
  const tabs = document.querySelectorAll('.shop-tab');
  if (!tabs.length) return;
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
    });
  });
}());

/* ---------- Product Detail Page ---------- */
(function () {
  if (!document.querySelector('.pd-section')) return;

  /* ── Variant data (color/size → price/stock), injected by show.blade.php.
     __pdVariantData is only set when the product has variant-dimension
     values selected — absent for simple products. ── */
  const variantData = window.__pdVariantData || { optionTypes: [], variants: [] };
  const variants    = variantData.variants || [];
  const hasVariants = variants.length > 0;
  const selected    = {}; // { [type_id]: value_id }

  const optionButtons = document.querySelectorAll('.pd-swatch[data-type-id], .pd-size-btn[data-type-id]');
  const colorLabel    = document.getElementById('pdColorLabel');

  /* ── Gallery jump: scroll to the variant's own image (color swatch → its photo).
     Falls back to image index 0 when the variant has no image_id ("same as
     product"), so switching away from a variant that HAD a dedicated photo
     doesn't leave the gallery stuck on it. ── */
  const gallery      = document.getElementById('pdGallery');
  const galleryImgs   = gallery ? Array.from(gallery.querySelectorAll('.pd-gimg-wrap')) : [];
  const galleryDots   = document.querySelectorAll('#pdGalleryDots .pd-gallery-dot');
  const scrollGalleryToImage = (imageId, instant) => {
    if (!gallery || !galleryImgs.length) return;
    const found = imageId ? galleryImgs.findIndex(el => el.dataset.imageId === String(imageId)) : -1;
    const idx = found >= 0 ? found : 0;
    galleryImgs[idx].scrollIntoView({ behavior: instant ? 'auto' : 'smooth', block: 'nearest', inline: 'start' });
    galleryDots.forEach((d, i) => d.classList.toggle('active', i === idx));
  };

  // Reflect the `selected` map onto the DOM: active/aria-checked buttons + color label.
  const applySelectionToDom = () => {
    optionButtons.forEach(btn => {
      const isSelected = String(selected[btn.dataset.typeId]) === String(btn.dataset.valueId);
      btn.classList.toggle('active', isSelected);
      if (btn.classList.contains('pd-swatch')) {
        btn.setAttribute('aria-checked', isSelected ? 'true' : 'false');
        if (isSelected) colorLabel && (colorLabel.textContent = btn.dataset.color);
      }
    });
  };

  // Exact match: the variant whose option set equals every currently selected value.
  const findVariant = () => {
    const typeIds = Object.keys(selected);
    if (!typeIds.length) return null;
    return variants.find(v => v.options.length === typeIds.length
      && v.options.every(o => String(selected[o.type_id]) === String(o.value_id))
    ) || null;
  };

  // If picking `valueId` for `typeId` doesn't form a real in-stock combo with
  // the OTHER currently selected dimensions (e.g. this color only comes in a
  // size other than the one selected), snap those other dimensions to a
  // combo that actually exists instead of leaving the selection dead. Without
  // this, a value that's disabled only because of the CURRENT other-dimension
  // choice could never be reached — clicking it was blocked, and there was no
  // other way to change the conflicting dimension first.
  const reconcileSelection = (typeId) => {
    if (findVariant()) return;

    const candidate = variants.find(v => v.status !== 'out_of_stock'
      && v.options.some(o => String(o.type_id) === String(typeId) && String(o.value_id) === String(selected[typeId]))
    );

    if (candidate) {
      candidate.options.forEach(o => { selected[o.type_id] = String(o.value_id); });
    }
  };

  // A value is truly dead only when NO selectable variant has it at all —
  // "selectable" includes pre-order (status='pre_order'), only a forced/actual
  // out_of_stock variant is greyed out. Regardless of what's currently selected
  // on the other dimensions, since reconcileSelection() adjusts those
  // automatically on click.
  const isValuePossible = (typeId, valueId) => variants.some(v => v.status !== 'out_of_stock'
    && v.options.some(o => String(o.type_id) === String(typeId) && String(o.value_id) === String(valueId))
  );

  // Prefer an IN-STOCK variant, then a pre-order one, over blindly trusting
  // the server's index-0 `.active` default — the first color/size combination
  // isn't necessarily the one actually available, which left buttons greyed
  // out on first load whenever that combo was sold out.
  const defaultVariant = variants.find(v => v.status === 'in_stock')
    || variants.find(v => v.status === 'pre_order')
    || variants[0] || null;

  if (defaultVariant) {
    defaultVariant.options.forEach(o => { selected[o.type_id] = String(o.value_id); });
    applySelectionToDom();
    scrollGalleryToImage(defaultVariant.image_id, true);
  } else {
    // No variant data at all (simple product, or dimensions picked but no
    // combinations generated yet) — fall back to whatever the server rendered.
    optionButtons.forEach(btn => {
      if (btn.classList.contains('active')) selected[btn.dataset.typeId] = btn.dataset.valueId;
    });
  }

  const formatVnd = amount => Math.round(amount).toLocaleString('vi-VN') + ' ₫';
  const formatUsd = amount => '$' + Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  // EN locale shows the variant's own USD price (price_usd/sale_price_usd,
  // set per-variant in Filament) when the admin filled it in. Falls back to
  // the VND fields — same as the rest of the site — when a variant has no
  // USD price set, rather than showing a blank/zero price.
  const isEn = document.documentElement.lang === 'en';

  const updateAvailability = () => {
    optionButtons.forEach(btn => {
      const possible = isValuePossible(btn.dataset.typeId, btn.dataset.valueId);
      btn.classList.toggle('sold-out', !possible);
      btn.disabled = !possible;
    });
  };

  const updateVariantUi = () => {
    const variant        = findVariant();
    const priceEl        = document.getElementById('pdPrice');
    const variantIdInput = document.getElementById('pdVariantId');
    const addBtn         = document.getElementById('pdAddBtn');

    if (variantIdInput) variantIdInput.value = variant ? variant.id : '';
    if (variant) scrollGalleryToImage(variant.image_id, false);

    if (variant && priceEl) {
      const useUsd  = isEn && variant.base_price_usd != null;
      const base    = useUsd ? variant.base_price_usd : variant.base_price;
      const sale    = useUsd ? variant.sale_price_usd : variant.sale_price;
      const format  = useUsd ? formatUsd : formatVnd;
      const hasSale = sale && sale < base;

      priceEl.innerHTML = hasSale
        ? `<span class="t-price-old">${format(base)}</span> ${format(sale)}`
        : format(base);
    }

    if (addBtn && !addBtn.classList.contains('pd-added')) {
      // Pre-order gets its own label but stays disabled — cart/checkout doesn't
      // track variant stock yet, so it can't actually take a pre-order purchase.
      const status = variant ? variant.status : 'out_of_stock';
      addBtn.disabled = status !== 'in_stock';
      addBtn.textContent = status === 'pre_order' ? 'Đặt trước' : (status === 'out_of_stock' ? 'Hết hàng' : 'Thêm vào giỏ hàng');
    }
  };

  /* ── Colour swatches ── */
  document.querySelectorAll('.pd-swatch[data-type-id]').forEach(sw => {
    sw.addEventListener('click', () => {
      if (sw.disabled) return;
      selected[sw.dataset.typeId] = sw.dataset.valueId;
      reconcileSelection(sw.dataset.typeId);
      applySelectionToDom();
      updateAvailability();
      updateVariantUi();
    });
  });

  /* ── Size / other dimension buttons ── */
  document.querySelectorAll('.pd-size-btn[data-type-id]').forEach(btn => {
    btn.addEventListener('click', () => {
      if (btn.disabled) return;
      selected[btn.dataset.typeId] = btn.dataset.valueId;
      reconcileSelection(btn.dataset.typeId);
      applySelectionToDom();
      updateAvailability();
      updateVariantUi();
    });
  });

  if (hasVariants) {
    updateAvailability();
    updateVariantUi();
  }

  /* ── Size guide modal — markup rendered by show.blade.php when the
     product has a guide assigned. Overlay click, × and Escape close it. ── */
  const sgModal = document.getElementById('pdSizeGuideModal');
  if (sgModal) {
    const sgOpen  = () => { sgModal.hidden = false; document.body.style.overflow = 'hidden'; };
    const sgClose = () => { sgModal.hidden = true;  document.body.style.overflow = ''; };
    document.querySelectorAll('[data-sg-open]').forEach(btn => btn.addEventListener('click', sgOpen));
    sgModal.addEventListener('click', e => {
      if (e.target === sgModal || e.target.closest('[data-sg-close]')) sgClose();
    });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && !sgModal.hidden) sgClose();
    });
  }

  /* ── Add to bag ── */
  const addBtn = document.getElementById('pdAddBtn');
  if (addBtn) {
    addBtn.addEventListener('click', () => {
      if (addBtn.disabled) return;
      addBtn.textContent = 'Đã thêm vào giỏ ✓';
      addBtn.classList.add('pd-added');
      addBtn.disabled = true;
      setTimeout(() => {
        addBtn.classList.remove('pd-added');
        updateVariantUi();
      }, 2200);
    });
  }

  /* ── Wishlist toggle ── */
  const wishBtn = document.getElementById('pdWishBtn');
  if (wishBtn) {
    wishBtn.addEventListener('click', () => {
      const wished = wishBtn.classList.toggle('pd-wished');
      wishBtn.setAttribute('aria-pressed', wished ? 'true' : 'false');
    });
  }

  /* ── Mobile gallery: sync dots with scroll-snap ── */
  (function () {
    const gallery = document.getElementById('pdGallery');
    const dots    = document.querySelectorAll('#pdGalleryDots .pd-gallery-dot');
    if (!gallery || !dots.length) return;

    gallery.addEventListener('scroll', function () {
      var idx = Math.round(gallery.scrollLeft / gallery.clientWidth);
      dots.forEach(function (d, i) { d.classList.toggle('active', i === idx); });
    }, { passive: true });
  }());


}());

/* ---------- Accordions — one open at a time ----------
   Shared by PDP specs AND the category-page FAQ (.pd-accordion is a generic
   pattern, not PDP-only) — kept outside the ".pd-section"-guarded IIFE above
   so it also wires up on pages without a PDP. ---------- */
(function () {
  document.querySelectorAll('.pd-acc-trigger').forEach(trigger => {
    trigger.addEventListener('click', () => {
      const acc    = trigger.closest('.pd-accordion');
      const isOpen = acc.classList.contains('pd-open');
      document.querySelectorAll('.pd-accordion.pd-open').forEach(a => {
        a.classList.remove('pd-open');
        a.querySelector('.pd-acc-trigger').setAttribute('aria-expanded', 'false');
      });
      if (!isOpen) {
        acc.classList.add('pd-open');
        trigger.setAttribute('aria-expanded', 'true');
      }
    });
  });
}());

/* ---------- Detailed info — read more / collapse ---------- */
(function () {
  const toggle = document.getElementById('pdDetailInfoToggle');
  const body   = document.getElementById('pdDetailInfoBody');
  if (!toggle || !body) return;

  const isEn = document.documentElement.lang === 'en';
  const moreLabel = isEn ? 'Show more' : 'Xem thêm';
  const lessLabel = isEn ? 'Collapse'  : 'Thu gọn';

  toggle.addEventListener('click', () => {
    const expanded = body.classList.toggle('is-expanded');
    toggle.textContent = expanded ? lessLabel : moreLabel;
    toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
  });
}());

/* ---------- Related posts slider ---------- */
(function () {
  const section = document.querySelector('.jnl-post-related');
  if (!section) return;
  const track   = section.querySelector('.jnl-related-track');
  const prevBtn = section.querySelector('.jnl-related-btn--prev');
  const nextBtn = section.querySelector('.jnl-related-btn--next');
  if (!track) return;

  function cardW() {
    const c = track.querySelector('.journal-card');
    return c ? c.offsetWidth : 0;
  }

  if (prevBtn) prevBtn.addEventListener('click', () => track.scrollBy({ left: -cardW(), behavior: 'smooth' }));
  if (nextBtn) nextBtn.addEventListener('click', () => track.scrollBy({ left:  cardW(), behavior: 'smooth' }));

  let px = null, sx = 0;
  track.addEventListener('pointerdown', e => {
    px = e.clientX; sx = track.scrollLeft;
    track.setPointerCapture(e.pointerId);
    track.style.cursor = 'grabbing';
  });
  track.addEventListener('pointermove', e => {
    if (px === null) return;
    track.scrollLeft = sx - (e.clientX - px);
  });
  track.addEventListener('pointerup',     () => { px = null; track.style.cursor = 'grab'; });
  track.addEventListener('pointercancel', () => { px = null; track.style.cursor = 'grab'; });
}());
