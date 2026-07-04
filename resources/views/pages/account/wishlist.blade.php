@extends('layouts.app')

@section('title', 'Danh sách yêu thích')
@section('meta-description', 'Xem lại các sản phẩm bạn đã yêu thích tại LINNÉ.')
@section('body-class', 'page-pd')

@section('content')

    <!-- ==============================  FAVORITES PAGE  ============================== -->
    <div class="fav-page">

      <!-- Header -->
      <div class="fav-hd">
        <p class="fav-hd-eyebrow">LINNÉ · Yêu thích</p>
        <h1 class="fav-hd-title">Danh sách yêu thích <em id="favCountLabel">(8)</em></h1>
      </div>

      <!-- Product grid -->
      <div class="fav-grid">

        <!-- Card 1 -->
        <article class="shop-card fav-card">
          <a href="{{ url('/products/ao-linen-co-chu-v') }}" class="shop-card-img-link">
            <div class="shop-card-img-wrap">
              <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Black.jpg?v=1779070489&width=1200" alt="Áo linen cổ chữ V" class="shop-card-img">
              <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Black4.jpg?v=1779070489&width=1200" alt="" class="shop-card-img-alt" aria-hidden="true">
            </div>
          </a>
          <button class="fav-card-del" aria-label="Bỏ yêu thích">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          </button>
          <div class="shop-card-info">
            <a href="{{ url('/products/ao-linen-co-chu-v') }}" class="shop-card-name">Áo linen cổ chữ V</a>
            <p class="shop-card-meta">100% Linen · Cổ chữ V</p>
            <span class="shop-card-price">660.000 ₫</span>
            <button class="fav-atc">Thêm vào giỏ</button>
          </div>
        </article>

        <!-- Card 2 -->
        <article class="shop-card fav-card">
          <a href="{{ url('/products/ao-blouse-that-no') }}" class="shop-card-img-link">
            <div class="shop-card-img-wrap">
              <img src="https://elleandriley.com/cdn/shop/files/Bandana-Scarf-Black-Sand_80abcf68-ce45-46c1-8665-8419cb22e876.jpg?v=1781216042&width=1200" alt="Áo blouse thắt nơ" class="shop-card-img">
              <img src="https://elleandriley.com/cdn/shop/files/Bandana-Scarf-Black-Sand3.jpg?v=1781216042&width=1200" alt="" class="shop-card-img-alt" aria-hidden="true">
            </div>
          </a>
          <button class="fav-card-del" aria-label="Bỏ yêu thích">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          </button>
          <div class="shop-card-info">
            <a href="{{ url('/products/ao-blouse-that-no') }}" class="shop-card-name">Áo blouse thắt nơ</a>
            <p class="shop-card-meta">100% Linen · Thắt nơ</p>
            <span class="shop-card-price">720.000 ₫</span>
            <button class="fav-atc">Thêm vào giỏ</button>
          </div>
        </article>

        <!-- Card 3 -->
        <article class="shop-card fav-card">
          <a href="{{ url('/products/ao-crop-linen') }}" class="shop-card-img-link">
            <div class="shop-card-img-wrap">
              <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Red.jpg?v=1778217693&width=1200" alt="Áo crop linen" class="shop-card-img">
              <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Red2.jpg?v=1778217693&width=1200" alt="" class="shop-card-img-alt" aria-hidden="true">
            </div>
          </a>
          <button class="fav-card-del" aria-label="Bỏ yêu thích">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          </button>
          <div class="shop-card-info">
            <a href="{{ url('/products/ao-crop-linen') }}" class="shop-card-name">Áo crop linen</a>
            <p class="shop-card-meta">100% Linen · Crop fit</p>
            <span class="shop-card-price">620.000 ₫</span>
            <button class="fav-atc">Thêm vào giỏ</button>
          </div>
        </article>

        <!-- Card 4 -->
        <article class="shop-card fav-card">
          <a href="{{ url('/products/ao-linen-oversized') }}" class="shop-card-img-link">
            <div class="shop-card-img-wrap">
              <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Birch.jpg?v=1778217589&width=1200" alt="Áo linen oversized" class="shop-card-img">
              <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Birch3.jpg?v=1778217589&width=1200" alt="" class="shop-card-img-alt" aria-hidden="true">
            </div>
          </a>
          <button class="fav-card-del" aria-label="Bỏ yêu thích">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          </button>
          <div class="shop-card-info">
            <a href="{{ url('/products/ao-linen-oversized') }}" class="shop-card-name">Áo linen oversized</a>
            <p class="shop-card-meta">100% Linen · Oversized</p>
            <span class="shop-card-price">680.000 ₫</span>
            <button class="fav-atc">Thêm vào giỏ</button>
          </div>
        </article>

        <!-- Card 5 -->
        <article class="shop-card fav-card">
          <a href="{{ url('/products/ao-cashmere-co-tron') }}" class="shop-card-img-link">
            <div class="shop-card-img-wrap">
              <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Black4.jpg?v=1779070489&width=1200" alt="Áo Cashmere cổ tròn" class="shop-card-img">
              <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Camel3.jpg?v=1779070696&width=1200" alt="" class="shop-card-img-alt" aria-hidden="true">
              <span class="shop-badge">Mới</span>
            </div>
          </a>
          <button class="fav-card-del" aria-label="Bỏ yêu thích">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          </button>
          <div class="shop-card-info">
            <a href="{{ url('/products/ao-cashmere-co-tron') }}" class="shop-card-name">Áo Cashmere cổ tròn</a>
            <p class="shop-card-meta">100% Cashmere · Cổ tròn</p>
            <span class="shop-card-price">890.000 ₫</span>
            <button class="fav-atc">Thêm vào giỏ</button>
          </div>
        </article>

        <!-- Card 6 -->
        <article class="shop-card fav-card">
          <a href="{{ url('/products/ao-linen-xanh-nhat') }}" class="shop-card-img-link">
            <div class="shop-card-img-wrap">
              <img src="https://elleandriley.com/cdn/shop/files/Slim_tee_Pale_Blue.jpg?v=1778216637&width=1200" alt="Áo linen xanh nhạt" class="shop-card-img">
              <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_BrownMelange.jpg?v=1778217470&width=1200" alt="" class="shop-card-img-alt" aria-hidden="true">
            </div>
          </a>
          <button class="fav-card-del" aria-label="Bỏ yêu thích">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          </button>
          <div class="shop-card-info">
            <a href="{{ url('/products/ao-linen-xanh-nhat') }}" class="shop-card-name">Áo linen xanh nhạt</a>
            <p class="shop-card-meta">100% Linen · Regular fit</p>
            <span class="shop-card-price">640.000 ₫</span>
            <button class="fav-atc">Thêm vào giỏ</button>
          </div>
        </article>

        <!-- Card 7 -->
        <article class="shop-card fav-card">
          <a href="{{ url('/products/vay-midi-linen') }}" class="shop-card-img-link">
            <div class="shop-card-img-wrap">
              <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_BrownMelange2.jpg?v=1778217470&width=1200" alt="Váy midi linen" class="shop-card-img">
              <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_BrownMelange.jpg?v=1778217470&width=1200" alt="" class="shop-card-img-alt" aria-hidden="true">
            </div>
          </a>
          <button class="fav-card-del" aria-label="Bỏ yêu thích">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          </button>
          <div class="shop-card-info">
            <a href="{{ url('/products/vay-midi-linen') }}" class="shop-card-name">Váy midi linen</a>
            <p class="shop-card-meta">100% Linen · Midi</p>
            <span class="shop-card-price">780.000 ₫</span>
            <button class="fav-atc">Thêm vào giỏ</button>
          </div>
        </article>

        <!-- Card 8 -->
        <article class="shop-card fav-card">
          <a href="{{ url('/products/khan-bandana-linen') }}" class="shop-card-img-link">
            <div class="shop-card-img-wrap">
              <img src="https://elleandriley.com/cdn/shop/files/Bandana-Scarf-Sand-Cream3.jpg?v=1781216151&width=1200" alt="Khăn bandana linen" class="shop-card-img">
              <img src="https://elleandriley.com/cdn/shop/files/Bandana-Scarf-Black-Sand3.jpg?v=1781216042&width=1200" alt="" class="shop-card-img-alt" aria-hidden="true">
            </div>
          </a>
          <button class="fav-card-del" aria-label="Bỏ yêu thích">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
          </button>
          <div class="shop-card-info">
            <a href="{{ url('/products/khan-bandana-linen') }}" class="shop-card-name">Khăn bandana linen</a>
            <p class="shop-card-meta">100% Linen · Bandana</p>
            <span class="shop-card-price">320.000 ₫</span>
            <button class="fav-atc">Thêm vào giỏ</button>
          </div>
        </article>

      </div><!-- /.fav-grid -->

      <!-- Continue shopping -->
      <div class="fav-continue">
        <a href="{{ url('/collections') }}" class="cart-continue-link">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
          </svg>
          Khám phá thêm
        </a>
      </div>

    </div><!-- /.fav-page -->

@endsection
