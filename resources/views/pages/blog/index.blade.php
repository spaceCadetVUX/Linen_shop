@extends('layouts.app')

@section('title', ($category->name ?? 'Cửa hàng') . ' — LINNÉ')
@section('meta-description', 'Khám phá bộ sưu tập ' . ($category->name ?? '') . ' của LINNÉ — thời trang linen tối giản, bền vững.')
@section('body-class', 'page-pd')

@section('content')

    <!-- ==============================  JOURNAL PAGE  ============================== -->
    <div class="jnl-page">

      <!-- Header -->
      <div class="jnl-hd">
        <p class="jnl-hd-eyebrow">LINNÉ · Nhật ký thời trang</p>
        <h1 class="jnl-hd-title"><em>Journal</em></h1>
      </div>

      <!-- Featured article -->
      <article class="jnl-featured">
        <a href="#" class="jnl-featured-img-link">
          <div class="jnl-featured-img-wrap">
            <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Camel3.jpg?v=1779070696&width=2160"
                 alt="Mặc gì khi đi làm?" class="jnl-featured-img">
          </div>
        </a>
        <div class="jnl-featured-body">
          <div class="jnl-featured-meta">
            <span class="jnl-tag">Phong cách</span>
            <span class="jnl-date">12 tháng 6, 2026</span>
          </div>
          <h2 class="jnl-featured-title">Mặc gì khi đi làm?</h2>
          <p class="jnl-featured-excerpt">Chọn trang phục phù hợp khi đi làm đôi khi là một thử thách. Bạn cần vừa chuyên nghiệp, vừa thoải mái, và vẫn giữ được phong cách riêng. Dưới đây là những gợi ý từ LINNÉ giúp bạn mặc đẹp mỗi ngày đến văn phòng.</p>
          <a href="#" class="jnl-featured-cta">Đọc bài viết
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
              <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
            </svg>
          </a>
        </div>
      </article>

      <!-- Divider -->
      <div class="jnl-divider">
        <span class="jnl-divider-label">Tất cả bài viết</span>
      </div>

      <!-- Articles grid -->
      <div class="jnl-grid journal-grid">

        <article class="journal-card jnl-card">
          <a href="#" class="journal-card-img-link">
            <div class="journal-card-img-wrap">
              <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_BrownMelange2.jpg?v=1778217470&width=2160"
                   alt="Chọn độ dài váy phù hợp" class="journal-card-img">
            </div>
          </a>
          <div class="journal-card-body">
            <div class="jnl-card-meta"><span class="jnl-tag">Phong cách</span><span class="jnl-date">05/06/2026</span></div>
            <h3 class="journal-card-title">Chọn độ dài váy phù hợp</h3>
            <p class="journal-card-excerpt">Độ dài váy phù hợp có thể tôn lên vóc dáng của bạn một cách tinh tế...</p>
            <a href="#" class="journal-card-cta">Đọc thêm</a>
          </div>
        </article>

        <article class="journal-card jnl-card">
          <a href="#" class="journal-card-img-link">
            <div class="journal-card-img-wrap">
              <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Birch.jpg?v=1778217589&width=2160"
                   alt="Blazer và phong cách văn phòng" class="journal-card-img">
            </div>
          </a>
          <div class="journal-card-body">
            <div class="jnl-card-meta"><span class="jnl-tag">Xu hướng</span><span class="jnl-date">28/05/2026</span></div>
            <h3 class="journal-card-title">Blazer và phong cách văn phòng</h3>
            <p class="journal-card-excerpt">Blazer là một item không thể thiếu trong tủ đồ thời trang...</p>
            <a href="#" class="journal-card-cta">Đọc thêm</a>
          </div>
        </article>

        <article class="journal-card jnl-card">
          <a href="#" class="journal-card-img-link">
            <div class="journal-card-img-wrap">
              <img src="https://elleandriley.com/cdn/shop/files/Slim_tee_Pale_Blue.jpg?v=1778216637&width=2160"
                   alt="Chăm sóc vải linen đúng cách" class="journal-card-img">
            </div>
          </a>
          <div class="journal-card-body">
            <div class="jnl-card-meta"><span class="jnl-tag">Chất liệu</span><span class="jnl-date">20/05/2026</span></div>
            <h3 class="journal-card-title">Chăm sóc vải linen đúng cách</h3>
            <p class="journal-card-excerpt">Vải linen bền đẹp hơn nếu bạn biết cách giặt và bảo quản đúng cách...</p>
            <a href="#" class="journal-card-cta">Đọc thêm</a>
          </div>
        </article>

        <article class="journal-card jnl-card">
          <a href="#" class="journal-card-img-link">
            <div class="journal-card-img-wrap">
              <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Black.jpg?v=1779070489&width=2160"
                   alt="5 cách phối đồ với áo cổ tròn" class="journal-card-img">
            </div>
          </a>
          <div class="journal-card-body">
            <div class="jnl-card-meta"><span class="jnl-tag">Phong cách</span><span class="jnl-date">14/05/2026</span></div>
            <h3 class="journal-card-title">5 cách phối đồ với áo cổ tròn</h3>
            <p class="journal-card-excerpt">Áo cổ tròn tưởng đơn giản nhưng lại cực kỳ đa năng khi biết cách phối...</p>
            <a href="#" class="journal-card-cta">Đọc thêm</a>
          </div>
        </article>

        <article class="journal-card jnl-card">
          <a href="#" class="journal-card-img-link">
            <div class="journal-card-img-wrap">
              <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Black4.jpg?v=1779070489&width=2160"
                   alt="Màu trung tính: đơn giản mà tinh tế" class="journal-card-img">
            </div>
          </a>
          <div class="journal-card-body">
            <div class="jnl-card-meta"><span class="jnl-tag">Xu hướng</span><span class="jnl-date">07/05/2026</span></div>
            <h3 class="journal-card-title">Màu trung tính: đơn giản mà tinh tế</h3>
            <p class="journal-card-excerpt">Neutral tone không bao giờ lỗi mốt — bí quyết nằm ở cách bạn layering...</p>
            <a href="#" class="journal-card-cta">Đọc thêm</a>
          </div>
        </article>

        <article class="journal-card jnl-card">
          <a href="#" class="journal-card-img-link">
            <div class="journal-card-img-wrap">
              <img src="https://elleandriley.com/cdn/shop/files/Bandana-Scarf-Black-Sand_80abcf68-ce45-46c1-8665-8419cb22e876.jpg?v=1781216042&width=2160"
                   alt="Gợi ý tủ đồ tối giản cho nàng bận rộn" class="journal-card-img">
            </div>
          </a>
          <div class="journal-card-body">
            <div class="jnl-card-meta"><span class="jnl-tag">Phong cách</span><span class="jnl-date">24/04/2026</span></div>
            <h3 class="journal-card-title">Gợi ý tủ đồ tối giản cho nàng bận rộn</h3>
            <p class="journal-card-excerpt">Capsule wardrobe không cần nhiều — chỉ cần đúng, bạn có thể mặc mãi không hết...</p>
            <a href="#" class="journal-card-cta">Đọc thêm</a>
          </div>
        </article>

      </div><!-- /.jnl-grid -->

      <!-- Load more -->
      <div class="jnl-load-more">
        <button class="jnl-load-btn">Xem thêm bài viết</button>
      </div>

    </div><!-- /.jnl-page -->

@endsection

