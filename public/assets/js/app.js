/* ============================================
   LINNÉ — main.js
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
(function () {
  if (window.innerWidth <= 768) return;

  var grid    = document.getElementById('megaProductGrid');
  var eyebrow = document.getElementById('megaProductsEyebrow');
  if (!grid) return;

  var cards = Array.prototype.slice.call(grid.querySelectorAll('.mega-product-card'));
  if (!cards.length) return;

  var B  = 'https://elleandriley.com/cdn/shop/files/';
  var qPB  = 'Slim_tee_Pale_Blue.jpg?v=1778216637&width=600';
  var qPM2 = 'Slim_Tee_BrownMelange2.jpg?v=1778217470&width=600';
  var qPM  = 'Slim_Tee_BrownMelange.jpg?v=1778217470&width=600';
  var qBi  = 'Slim_Tee_Birch.jpg?v=1778217589&width=600';
  var qSc  = 'Bandana-Scarf-Sand-Cream3.jpg?v=1781216151&width=600';
  var qCa  = 'Cashmere_Crew_Camel3.jpg?v=1779070696&width=600';

  var DATA = {
    'ao-linen':      [{ img: qPB,  name: 'Áo linen cổ chữ V' },   { img: qPM2, name: 'Áo blouse thắt nơ' },    { img: qBi,  name: 'Áo linen oversized' },    { img: qCa,  name: 'Áo cashmere camel' }],
    'ao-blouse':     [{ img: qPM2, name: 'Áo blouse thắt nơ' },   { img: qPB,  name: 'Áo blouse xanh nhạt' },  { img: qPM,  name: 'Áo blouse nâu' },          { img: qBi,  name: 'Áo blouse trắng' }],
    'ao-phong':      [{ img: qPB,  name: 'Áo phông xanh nhạt' },  { img: qPM,  name: 'Áo phông nâu' },         { img: qBi,  name: 'Áo phông trắng' },         { img: qCa,  name: 'Áo phông camel' }],
    'ao-khoac':      [{ img: qCa,  name: 'Áo khoác cashmere' },   { img: qBi,  name: 'Áo khoác linen trắng' }, { img: qPM2, name: 'Cardigan nâu đậm' },        { img: qPB,  name: 'Áo khoác xanh nhạt' }],
    'quan-au':       [{ img: qPM,  name: 'Quần âu nâu' },         { img: qBi,  name: 'Quần âu trắng' },        { img: qCa,  name: 'Quần âu camel' },           { img: qPB,  name: 'Quần âu xanh' }],
    'wide-leg':      [{ img: qPM2, name: 'Wide leg nâu đậm' },    { img: qPB,  name: 'Wide leg xanh nhạt' },   { img: qCa,  name: 'Wide leg camel' },          { img: qBi,  name: 'Wide leg trắng' }],
    'vay-midi':      [{ img: qSc,  name: 'Váy midi sand' },       { img: qPB,  name: 'Váy midi xanh nhạt' },   { img: qPM,  name: 'Váy midi nâu' },            { img: qBi,  name: 'Váy midi trắng' }],
    'vay-maxi':      [{ img: qBi,  name: 'Váy maxi trắng' },      { img: qPM2, name: 'Váy maxi nâu đậm' },    { img: qPB,  name: 'Váy maxi xanh' },           { img: qCa,  name: 'Váy maxi camel' }],
    'set-quan':      [{ img: qPB,  name: 'Set linen xanh nhạt' }, { img: qBi,  name: 'Set linen trắng' },      { img: qPM,  name: 'Set linen nâu' },           { img: qCa,  name: 'Set linen camel' }],
    'set-vay':       [{ img: qPM2, name: 'Set blouse + váy nâu' },{ img: qSc,  name: 'Set blouse + váy sand' },{ img: qBi,  name: 'Set blouse trắng' },        { img: qPB,  name: 'Set blouse xanh' }],
    'set-oversized': [{ img: qCa,  name: 'Set oversized camel' },  { img: qPM,  name: 'Set oversized nâu' },   { img: qPB,  name: 'Set oversized xanh' },      { img: qBi,  name: 'Set oversized trắng' }],
    'tui-tote':      [{ img: qSc,  name: 'Túi tote sand' },       { img: qBi,  name: 'Túi tote trắng' },       { img: qPB,  name: 'Túi tote xanh' },           { img: qCa,  name: 'Túi tote camel' }],
    'mu-linen':      [{ img: qSc,  name: 'Mũ linen sand' },       { img: qPB,  name: 'Mũ linen xanh' },        { img: qBi,  name: 'Mũ linen trắng' },          { img: qPM,  name: 'Mũ linen nâu' }],
    'khan':          [{ img: qSc,  name: 'Khăn bandana sand' },   { img: qCa,  name: 'Khăn cashmere camel' },  { img: qPB,  name: 'Túi đeo xanh nhạt' },       { img: qBi,  name: 'Túi đeo trắng' }],
  };

  var currentCat = null;
  var swapTimer  = null;

  function swap(cat, label) {
    if (cat === currentCat) return;
    currentCat = cat;
    var products = DATA[cat];
    if (!products) return;

    clearTimeout(swapTimer);

    /* Phase 1: fade out */
    cards.forEach(function (c) {
      c.classList.remove('is-entering');
      c.classList.add('is-leaving');
    });

    /* Phase 2: after fade-out completes, update content then animate in */
    swapTimer = setTimeout(function () {
      cards.forEach(function (card, i) {
        var p    = products[i];
        var img  = card.querySelector('.mega-product-img');
        var name = card.querySelector('.mega-product-name');
        img.src  = B + p.img;
        img.alt  = p.name;
        name.textContent = p.name;
      });
      if (eyebrow) eyebrow.textContent = label;

      cards.forEach(function (c) { c.classList.remove('is-leaving'); });
      void grid.offsetWidth;
      cards.forEach(function (c) { c.classList.add('is-entering'); });
    }, 190);
  }

  document.querySelectorAll('.mega-col--collection .mega-link[data-mega-cat]').forEach(function (link) {
    link.addEventListener('mouseenter', function () {
      swap(link.dataset.megaCat, link.dataset.megaLabel || 'Sản phẩm tiêu biểu');
    });
  });
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
    { img: 'https://elleandriley.com/cdn/shop/files/Slim_tee_Pale_Blue.jpg?v=1778216637&width=2160',       brand: 'LINNÉ', name: 'Áo linen cổ chữ V',   price: '660.000 ₫'   },
    { img: 'https://elleandriley.com/cdn/shop/files/Slim_Tee_BrownMelange2.jpg?v=1778217470&width=2160',  brand: 'LINNÉ', name: 'Áo blouse thắt nơ',    price: '720.000 ₫'   },
    { img: 'https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Camel3.jpg?v=1779070696&width=2160',    brand: 'LINNÉ', name: 'Đầm linen cổ chữ V',   price: '1.290.000 ₫' },
    { img: 'https://elleandriley.com/cdn/shop/files/Slim_Tee_BrownMelange.jpg?v=1778217470&width=2160',   brand: 'LINNÉ', name: 'Áo crop linen',         price: '620.000 ₫'   },
    { img: 'https://elleandriley.com/cdn/shop/files/Slim_Tee_Birch.jpg?v=1778217589&width=2160',          brand: 'LINNÉ', name: 'Áo linen oversized',    price: '680.000 ₫'   },
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


  /* ── Colour swatches ── */
  const swatches   = document.querySelectorAll('.pd-swatch');
  const colorLabel = document.getElementById('pdColorLabel');
  swatches.forEach(sw => {
    sw.addEventListener('click', () => {
      swatches.forEach(s => { s.classList.remove('active'); s.setAttribute('aria-checked', 'false'); });
      sw.classList.add('active');
      sw.setAttribute('aria-checked', 'true');
      if (colorLabel) colorLabel.textContent = sw.dataset.color;
    });
  });

  /* ── Size buttons ── */
  const sizeBtns = document.querySelectorAll('.pd-size-btn:not(:disabled)');
  sizeBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      sizeBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    });
  });

  /* ── Add to bag ── */
  const addBtn = document.getElementById('pdAddBtn');
  if (addBtn) {
    addBtn.addEventListener('click', () => {
      addBtn.textContent = 'Đã thêm vào giỏ ✓';
      addBtn.classList.add('pd-added');
      addBtn.disabled = true;
      setTimeout(() => {
        addBtn.textContent = 'Thêm vào giỏ hàng';
        addBtn.classList.remove('pd-added');
        addBtn.disabled = false;
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


  /* ── Accordions — one open at a time ── */
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
