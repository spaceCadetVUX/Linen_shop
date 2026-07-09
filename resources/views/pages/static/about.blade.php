@extends('layouts.app')

@section('title', $fallbackTitle)
@section('meta-description', $fallbackDescription)
@section('body-class', 'page-pd')

@section('content')

    <!-- ==============================  ABOUT PAGE  ============================== -->
    <div class="about-page">

      <!-- ① HERO -->
      <section class="about-hero">
        <div class="about-hero-bg">
          <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Camel3.jpg?v=1779070696&width=2160"
               alt="" class="about-hero-img" aria-hidden="true">
          <div class="about-hero-overlay"></div>
        </div>
        <div class="about-hero-content">
          <p class="about-hero-eyebrow">{{ __('about.hero.eyebrow') }}</p>
          <h1 class="about-hero-title">CacyLinen</h1>
          <p class="about-hero-tagline">{{ __('about.hero.tagline') }}</p>
        </div>
        <div class="about-hero-scroll" aria-hidden="true">
          <span class="about-hero-scroll-label">{{ __('about.hero.scroll') }}</span>
          <div class="about-hero-scroll-line"></div>
        </div>
      </section>

      <!-- ② MANIFESTO -->
      <section class="about-manifesto">
        <div class="about-manifesto-inner">
          <p class="about-manifesto-pre">{{ __('about.manifesto.pre') }}</p>
          <div class="about-manifesto-rule" aria-hidden="true"></div>
          <blockquote class="about-manifesto-quote">
            <span class="about-manifesto-line">{{ __('about.manifesto.quote_line1') }}</span>
            <span class="about-manifesto-line">{{ __('about.manifesto.quote_line2') }}</span>
            <span class="about-manifesto-line about-manifesto-line--accent">{{ __('about.manifesto.quote_line3') }}</span>
          </blockquote>
          <p class="about-manifesto-body">
            {{ __('about.manifesto.body_line1') }}<br>
            {{ __('about.manifesto.body_line2') }}
          </p>
        </div>
      </section>

      <!-- ③ BRAND STORY -->
      <section class="about-story">
        <div class="about-story-inner">

          <!-- Left: year -->
          <div class="about-story-pull">
            <span class="about-story-pull-mark">&ldquo;</span>
            <p class="about-story-pull-quote">{{ __('about.story.pull_quote') }}</p>
            <span class="about-story-pull-year">2018</span>
          </div>

          <!-- Right: content -->
          <div class="about-story-content">
            <p class="about-story-eyebrow">{{ __('about.story.eyebrow') }}</p>
            <h2 class="about-story-title">{{ __('about.story.title_line1') }}<br>{{ __('about.story.title_line2') }}</h2>
            <div class="about-story-body">
              <p>{{ __('about.story.body_p1_before') }} <em>{{ __('about.story.body_p1_em') }}</em> {{ __('about.story.body_p1_after') }}</p>
              <p>{{ __('about.story.body_p2') }}</p>
              <p>{{ __('about.story.body_p3') }}</p>
            </div>
            <div class="about-story-stats">
              <div class="about-story-stat">
                <span class="about-story-stat-num">1.200<sup>+</sup></span>
                <span class="about-story-stat-label">{{ __('about.story.stat1_label') }}</span>
              </div>
              <div class="about-story-stat">
                <span class="about-story-stat-num">48</span>
                <span class="about-story-stat-label">{{ __('about.story.stat2_label') }}</span>
              </div>
              <div class="about-story-stat">
                <span class="about-story-stat-num">100%</span>
                <span class="about-story-stat-label">{{ __('about.story.stat3_label') }}</span>
              </div>
            </div>
          </div>

        </div>
      </section>

      <!-- ④ MATERIALS EDITORIAL -->
      <section class="about-mat">

        <div class="about-mat-intro">
          <p class="about-mat-eyebrow">{{ __('about.materials.eyebrow') }}</p>
          <h2 class="about-mat-title">{{ __('about.materials.title_line1') }}<br>{{ __('about.materials.title_line2') }}</h2>
        </div>

        <div class="about-mat-grid">

          <!-- A: tall left — LINEN -->
          <div class="about-mat-cell about-mat-cell--a">
            <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Birch.jpg?v=1778217589&width=2160"
                 alt="Linen" class="about-mat-img">
            <div class="about-mat-overlay">
              <span class="about-mat-label">{{ __('about.materials.linen_label') }}</span>
              <p class="about-mat-desc">{{ __('about.materials.linen_desc') }}</p>
            </div>
          </div>

          <!-- B: top right square — CASHMERE -->
          <div class="about-mat-cell about-mat-cell--b">
            <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Black.jpg?v=1779070489&width=2160"
                 alt="Cashmere" class="about-mat-img">
            <div class="about-mat-overlay">
              <span class="about-mat-label">{{ __('about.materials.cashmere_label') }}</span>
              <p class="about-mat-desc">{{ __('about.materials.cashmere_desc') }}</p>
            </div>
          </div>

          <!-- C: top far-right square — COTTON -->
          <div class="about-mat-cell about-mat-cell--c">
            <img src="https://elleandriley.com/cdn/shop/files/Slim_tee_Pale_Blue.jpg?v=1778216637&width=2160"
                 alt="Cotton" class="about-mat-img">
            <div class="about-mat-overlay">
              <span class="about-mat-label">{{ __('about.materials.cotton_label') }}</span>
              <p class="about-mat-desc">{{ __('about.materials.cotton_desc') }}</p>
            </div>
          </div>

          <!-- D: bottom right wide — BANDANA / SILK -->
          <div class="about-mat-cell about-mat-cell--d">
            <img src="https://elleandriley.com/cdn/shop/files/Bandana-Scarf-Black-Sand_80abcf68-ce45-46c1-8665-8419cb22e876.jpg?v=1781216042&width=2160"
                 alt="Silk" class="about-mat-img">
            <div class="about-mat-overlay">
              <span class="about-mat-label">{{ __('about.materials.silk_label') }}</span>
              <p class="about-mat-desc">{{ __('about.materials.silk_desc') }}</p>
            </div>
          </div>

        </div><!-- /.about-mat-grid -->
      </section>

      <!-- ⑤ CORE VALUES -->
      <section class="about-values">
        <div class="about-values-inner">

          <div class="about-values-col">
            <span class="about-values-num">01</span>
            <div class="about-values-divider"></div>
            <h3 class="about-values-name">{{ __('about.values.col1_name') }}</h3>
            <p class="about-values-desc">{{ __('about.values.col1_desc') }}</p>
          </div>

          <div class="about-values-col">
            <span class="about-values-num">02</span>
            <div class="about-values-divider"></div>
            <h3 class="about-values-name">{{ __('about.values.col2_name') }}</h3>
            <p class="about-values-desc">{{ __('about.values.col2_desc') }}</p>
          </div>

          <div class="about-values-col">
            <span class="about-values-num">03</span>
            <div class="about-values-divider"></div>
            <h3 class="about-values-name">{{ __('about.values.col3_name') }}</h3>
            <p class="about-values-desc">{{ __('about.values.col3_desc') }}</p>
          </div>

        </div>
      </section>

      <!-- ⑥ STICKY SCROLL NARRATIVE -->
      <section class="about-sticky">

        <!-- LEFT: sticky text panel -->
        <div class="about-sticky-left">
          <div class="about-sticky-text">
            <p class="about-sticky-eyebrow">{{ __('about.sticky.eyebrow') }}</p>

            <div class="about-sticky-texts">
              <div class="about-sticky-slide-text active" data-slide="0">
                <span class="about-sticky-step">01 / 03</span>
                <h2 class="about-sticky-title">{{ __('about.sticky.step1_title') }}</h2>
                <p class="about-sticky-desc">{{ __('about.sticky.step1_desc') }}</p>
              </div>
              <div class="about-sticky-slide-text" data-slide="1">
                <span class="about-sticky-step">02 / 03</span>
                <h2 class="about-sticky-title">{{ __('about.sticky.step2_title') }}</h2>
                <p class="about-sticky-desc">{{ __('about.sticky.step2_desc') }}</p>
              </div>
              <div class="about-sticky-slide-text" data-slide="2">
                <span class="about-sticky-step">03 / 03</span>
                <h2 class="about-sticky-title">{{ __('about.sticky.step3_title') }}</h2>
                <p class="about-sticky-desc">{{ __('about.sticky.step3_desc') }}</p>
              </div>
            </div>

            <div class="about-sticky-progress-wrap">
              <div class="about-sticky-progress-bar"></div>
            </div>
          </div>
        </div>

        <!-- RIGHT: scrolling images -->
        <div class="about-sticky-right">
          <div class="about-sticky-slide" data-slide="0">
            <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_BrownMelange2.jpg?v=1778217470&width=2160"
                 alt="Thiết kế" class="about-sticky-img">
          </div>
          <div class="about-sticky-slide" data-slide="1">
            <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Black4.jpg?v=1779070489&width=2160"
                 alt="Chất liệu" class="about-sticky-img">
          </div>
          <div class="about-sticky-slide" data-slide="2">
            <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Red.jpg?v=1778217693&width=2160"
                 alt="Hoàn thiện" class="about-sticky-img">
          </div>
        </div>

        <!-- Mobile dot indicators (hidden on desktop via CSS) -->
        <div class="about-sticky-dots" aria-hidden="true">
          <span class="about-sticky-dot active"></span>
          <span class="about-sticky-dot"></span>
          <span class="about-sticky-dot"></span>
        </div>

      </section>

      <!-- ⑦ STATS BAR -->
      <section class="about-stats" aria-label="Thống kê">
        <div class="about-stats-overflow">
          <div class="about-stats-track">

            @php
              $aboutStatsSet = [
                  ['1.200+', __('about.stats.customers')],
                  ['6', __('about.stats.years')],
                  ['100%', __('about.stats.natural_fabric')],
                  ['0', __('about.stats.synthetic')],
              ];
            @endphp

            <!-- Set A -->
            <div class="about-stats-inner">
              @foreach ($aboutStatsSet as [$num, $label])
                <span class="about-stats-num">{{ $num }}</span>
                <span class="about-stats-label">{{ $label }}</span>
                <span class="about-stats-sep">·</span>
              @endforeach
            </div>

            <!-- Set B — duplicate for seamless loop -->
            <div class="about-stats-inner" aria-hidden="true">
              @foreach ($aboutStatsSet as [$num, $label])
                <span class="about-stats-num">{{ $num }}</span>
                <span class="about-stats-label">{{ $label }}</span>
                <span class="about-stats-sep">·</span>
              @endforeach
            </div>

          </div>
        </div>
      </section>

      <!-- ⑧ FOUNDER QUOTE -->
      <section class="about-fq">

        <!-- Portrait -->
        <div class="about-fq-portrait">
          <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Camel3.jpg?v=1779070696&width=2160"
               alt="Người sáng lập CacyLinen" class="about-fq-img">
        </div>

        <!-- Quote -->
        <div class="about-fq-content">
          <div class="about-fq-mark">&ldquo;</div>
          <blockquote class="about-fq-quote">
            {{ __('about.founder.quote') }}
          </blockquote>
          <footer class="about-fq-footer">
            <span class="about-fq-name">MINH VŨ</span>
            <span class="about-fq-role">{{ __('about.founder.role') }}</span>
          </footer>
        </div>

      </section>

      <!-- ⑨ CTA — PRODUCT REEL -->
      <section class="about-cta">

        <div class="about-cta-head">
          <p class="about-cta-eyebrow">{{ __('about.cta.eyebrow') }}</p>
          <h2 class="about-cta-title">{{ __('about.cta.title_line1') }}<br><em>{{ __('about.cta.title_line2') }}</em></h2>
          <a href="{{ url('/collections') }}" class="about-cta-btn">{{ __('about.cta.btn') }} &rarr;</a>
        </div>

        <!-- Scrolling thumbnail reel -->
        <div class="about-cta-reel-wrap">
          <div class="about-cta-reel">

            <!-- Set A -->
            <div class="about-cta-reel-inner">
              <a href="{{ url('/products/ao-linen-nau') }}" class="about-cta-thumb">
                <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_BrownMelange2.jpg?v=1778217470&width=800" alt="Áo Linen Nâu">
                <span class="about-cta-thumb-label">Áo Linen Nâu</span>
              </a>
              <a href="{{ url('/products/cashmere-crew-den') }}" class="about-cta-thumb">
                <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Black4.jpg?v=1779070489&width=800" alt="Cashmere Crew Đen">
                <span class="about-cta-thumb-label">Cashmere Crew Đen</span>
              </a>
              <a href="{{ url('/products/cashmere-crew-camel') }}" class="about-cta-thumb">
                <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Camel3.jpg?v=1779070696&width=800" alt="Cashmere Crew Camel">
                <span class="about-cta-thumb-label">Cashmere Crew Camel</span>
              </a>
              <a href="{{ url('/products/ao-linen-do') }}" class="about-cta-thumb">
                <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Red.jpg?v=1778217693&width=800" alt="Áo Linen Đỏ">
                <span class="about-cta-thumb-label">Áo Linen Đỏ</span>
              </a>
              <a href="{{ url('/products/ao-linen-trang') }}" class="about-cta-thumb">
                <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Birch.jpg?v=1778217693&width=800" alt="Áo Linen Trắng">
                <span class="about-cta-thumb-label">Áo Linen Trắng</span>
              </a>
            </div>

            <!-- Set B — duplicate for seamless loop -->
            <div class="about-cta-reel-inner" aria-hidden="true">
              <a href="{{ url('/products/ao-linen-nau') }}" class="about-cta-thumb">
                <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_BrownMelange2.jpg?v=1778217470&width=800" alt="">
                <span class="about-cta-thumb-label">Áo Linen Nâu</span>
              </a>
              <a href="{{ url('/products/cashmere-crew-den') }}" class="about-cta-thumb">
                <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Black4.jpg?v=1779070489&width=800" alt="">
                <span class="about-cta-thumb-label">Cashmere Crew Đen</span>
              </a>
              <a href="{{ url('/products/cashmere-crew-camel') }}" class="about-cta-thumb">
                <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Camel3.jpg?v=1779070696&width=800" alt="">
                <span class="about-cta-thumb-label">Cashmere Crew Camel</span>
              </a>
              <a href="{{ url('/products/ao-linen-do') }}" class="about-cta-thumb">
                <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Red.jpg?v=1778217693&width=800" alt="">
                <span class="about-cta-thumb-label">Áo Linen Đỏ</span>
              </a>
              <a href="{{ url('/products/ao-linen-trang') }}" class="about-cta-thumb">
                <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Birch.jpg?v=1778217693&width=800" alt="">
                <span class="about-cta-thumb-label">Áo Linen Trắng</span>
              </a>
            </div>

          </div>
        </div>

      </section>

    </div><!-- /.about-page -->

@endsection

@push('scripts')
<script>
(function () {
  // About page — reveal sections on scroll
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in-view'); revealObserver.unobserve(e.target); } });
  }, { threshold: 0.2 });
  document.querySelectorAll('.about-manifesto, .about-story, .about-mat, .about-values, .about-fq').forEach(el => revealObserver.observe(el));

  // Section 2 — Manifesto parallax
  (function () {
    const section = document.querySelector('.about-manifesto');
    const inner   = document.querySelector('.about-manifesto-inner');
    if (!section || !inner) return;
    window.addEventListener('scroll', function () {
      const rect = section.getBoundingClientRect();
      if (rect.bottom < 0 || rect.top > window.innerHeight) return;
      inner.style.transform = 'translateY(' + (-rect.top * 0.13) + 'px)';
    }, { passive: true });
  })();

  // Section 6 — Sticky scroll narrative
  (function () {
    const imgSlides  = document.querySelectorAll('.about-sticky-slide');
    const textSlides = document.querySelectorAll('.about-sticky-slide-text');
    const bar        = document.querySelector('.about-sticky-progress-bar');
    const dots       = document.querySelectorAll('.about-sticky-dot');
    const right      = document.querySelector('.about-sticky-right');
    if (!imgSlides.length) return;

    const activate = (idx) => {
      textSlides.forEach((t, i) => t.classList.toggle('active', i === idx));
      dots.forEach((d, i) => d.classList.toggle('active', i === idx));
      if (bar) bar.style.width = ((idx + 1) / imgSlides.length * 100) + '%';
    };

    // Desktop: IntersectionObserver on vertical scroll
    const stickyObs = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          activate(+entry.target.dataset.slide);
          entry.target.classList.add('in-view');
        }
      });
    }, { threshold: 0.5 });

    imgSlides.forEach(s => stickyObs.observe(s));

    // Mobile: carousel scroll → sync active text + dots
    if (right) {
      right.addEventListener('scroll', function () {
        const idx = Math.round(right.scrollLeft / right.offsetWidth);
        activate(idx);
      }, { passive: true });
    }
  })();
})();
</script>
@endpush
