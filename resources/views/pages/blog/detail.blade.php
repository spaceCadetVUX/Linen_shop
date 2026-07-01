@extends('layouts.app')

@section('title', 'Linen và nghệ thuật mặc đẹp mỗi ngày — LINNÉ Journal')
@section('meta-description', 'Một chiếc áo linen không chỉ là trang phục — đó là lựa chọn về lối sống, về những gì bạn muốn mang theo từng ngày.')
@section('body-class', 'page-pd')

@section('content')

    <!-- ==============================  JOURNAL POST  ============================== -->
    <div class="jnl-post-page">

      <!-- HERO IMAGE -->
      <div class="jnl-post-hero">
        <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Camel3.jpg?v=1779070696&width=2160"
             alt="Linen và nghệ thuật mặc đẹp mỗi ngày" class="jnl-post-hero-img">
      </div>

      <!-- ARTICLE HEADER -->
      <header class="jnl-post-hd">
        <div class="jnl-post-meta">
          <a href="{{ url('/blog') }}" class="jnl-post-tag">Phong cách sống</a>
          <span class="jnl-post-meta-sep">·</span>
          <span class="jnl-post-date">30 tháng 6, 2026</span>
          <span class="jnl-post-meta-sep">·</span>
          <span class="jnl-post-read">5 phút đọc</span>
        </div>
        <h1 class="jnl-post-title">Linen và nghệ thuật<br>mặc đẹp mỗi ngày</h1>
        <p class="jnl-post-subtitle">Một chiếc áo linen không chỉ là trang phục — đó là lựa chọn về lối sống, về những gì bạn muốn mang theo từng ngày.</p>
        <div class="jnl-post-author">
          <span class="jnl-post-author-name">Linh An</span>
          <span class="jnl-post-author-sep">—</span>
          <span class="jnl-post-author-role">Người sáng lập, LINNÉ</span>
        </div>
      </header>

      <!-- TWO-COLUMN: BODY + STICKY RAIL -->
      <div class="jnl-post-layout">

        <!-- LEFT: Article body -->
        <div class="jnl-post-body">

          <p id="intro" class="jnl-post-lead">Có một điều kỳ lạ xảy ra khi bạn mặc linen lần đầu tiên vào một buổi sáng mùa hè — cơ thể bạn thở được. Không phải theo nghĩa bóng, mà là thực sự, vật lý. Sợi vải tự nhiên tạo ra những khoảng không khí nhỏ li ti, giúp da duy trì nhiệt độ, hút ẩm và thoát nhanh.</p>

          <p>Nhưng vải linen không chỉ là về sự thoải mái sinh học. Nó là về một thứ khó định nghĩa hơn: cái cảm giác khi bạn mặc điều gì đó thật sự đáng mặc. Không phải vì thương hiệu, không phải vì xu hướng — mà vì chính chiếc áo đó, với chất liệu đó, với đường cắt đó, nói lên điều gì đó về bạn.</p>

          <!-- Pull quote -->
          <blockquote class="jnl-post-pull">
            Vải đẹp không cần phải được che giấu bởi nhiều lớp trang trí. Nó tự nói lên tất cả.
          </blockquote>

          <p id="triet-ly">Ở LINNÉ, chúng tôi tin rằng tủ quần áo tốt nhất là tủ quần áo ít nhất. Không phải vì chủ nghĩa tối giản là một xu hướng — mà vì khi bạn chỉ có những thứ bạn thực sự yêu thích, bạn mặc tốt hơn. Bạn nghĩ ít hơn về việc mặc gì, và sống nhiều hơn với điều quan trọng hơn.</p>

          <!-- Inline image -->
          <figure class="jnl-post-figure">
            <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_BrownMelange2.jpg?v=1778217470&width=1600"
                 alt="Chi tiết vải linen LINNÉ" class="jnl-post-figure-img">
            <figcaption class="jnl-post-caption">Bộ sưu tập Thu 2026 — 100% linen Bỉ, nhuộm tự nhiên</figcaption>
          </figure>

          <p id="gia-dep">Linen già đi đẹp hơn cotton. Sau mỗi lần giặt, sợi vải mềm ra một chút, nhưng cấu trúc vẫn giữ nguyên. Sau một năm, chiếc áo linen của bạn trở nên "của bạn" theo một cách mà không loại vải nào khác làm được — nó mang dấu ấn của cơ thể bạn, thói quen của bạn, những buổi sáng của bạn.</p>

          <p>Đó là lý do tại sao chúng tôi không theo mùa. Chúng tôi không ra 4 bộ sưu tập một năm, không tạo ra sự khan hiếm giả tạo, không khuyến khích bạn mua nhiều hơn những gì bạn cần. Chúng tôi làm những chiếc áo để mặc lâu — và chúng tôi tự hào về điều đó.</p>

          <!-- 2-col image -->
          <div class="jnl-post-figure-duo">
            <figure class="jnl-post-figure">
              <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Black4.jpg?v=1779070489&width=900"
                   alt="Cashmere đen" class="jnl-post-figure-img">
            </figure>
            <figure class="jnl-post-figure">
              <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Red.jpg?v=1778217693&width=900"
                   alt="Linen đỏ" class="jnl-post-figure-img">
            </figure>
          </div>
          <p class="jnl-post-caption jnl-post-caption--duo">Trái: Cashmere Crew Đen · Phải: Linen Tee Rouge</p>

          <p id="cach-mac">Cách mặc linen đẹp nhất? Đừng cố gắng quá. Để nếp nhăn tự nhiên — đó không phải khiếm khuyết, đó là tính cách của vải. Chọn size hơi rộng một chút nếu bạn muốn cảm giác thoải mái và hiện đại. Kết hợp với những thứ đơn giản: một đôi giày da, một chiếc túi canvas, một nụ cười thật.</p>

          <p>Vải linen đã tồn tại hàng nghìn năm trước khi synthetic fiber ra đời. Nó sẽ vẫn ở đây sau khi mọi xu hướng nhanh qua đi. Và chiếc áo bạn đang mặc hôm nay — nếu được chăm sóc đúng cách — có thể theo bạn mười năm nữa.</p>

          <!-- Share -->
          <div class="jnl-post-share">
            <span class="jnl-post-share-label">Chia sẻ</span>
            <a href="#" class="jnl-post-share-link" aria-label="Chia sẻ Facebook">Facebook</a>
            <a href="#" class="jnl-post-share-link" aria-label="Chia sẻ Pinterest">Pinterest</a>
            <a href="#" class="jnl-post-share-link" aria-label="Sao chép link">Sao chép link</a>
          </div>

        </div><!-- /.jnl-post-body -->

        <!-- RIGHT: Sticky sidebar -->
        <aside class="jnl-post-rail">

          <nav class="jnl-rail-toc">
            <h3 class="jnl-rail-title">Trong bài</h3>
            <ul class="jnl-rail-toc-list">
              <li><a href="#intro"    class="jnl-rail-toc-link">Linen và cơ thể</a></li>
              <li><a href="#triet-ly" class="jnl-rail-toc-link">Triết lý tối giản</a></li>
              <li><a href="#gia-dep"  class="jnl-rail-toc-link">Vải già đẹp hơn</a></li>
              <li><a href="#cach-mac" class="jnl-rail-toc-link">Cách mặc đẹp nhất</a></li>
            </ul>
          </nav>

          <div class="jnl-rail-divider"></div>

          <div class="jnl-rail-author">
            <span class="jnl-rail-author-name">Linh An</span>
            <span class="jnl-rail-author-role">Người sáng lập, LINNÉ</span>
          </div>

          <div class="jnl-rail-divider"></div>

          <div class="jnl-rail-tags">
            <h3 class="jnl-rail-title">Tags</h3>
            <div class="jnl-rail-tag-list">
              <a href="#" class="jnl-post-tag-item">Linen</a>
              <a href="#" class="jnl-post-tag-item">Phong cách sống</a>
              <a href="#" class="jnl-post-tag-item">Sustainable fashion</a>
              <a href="#" class="jnl-post-tag-item">Tủ quần áo tối giản</a>
            </div>
          </div>

        </aside>

      </div><!-- /.jnl-post-layout -->

      <hr class="jnl-post-rule">

      <!-- RELATED POSTS SLIDER -->
      <section class="jnl-post-related">
        <div class="jnl-related-head">
          <h2 class="jnl-post-related-hd">Bài viết liên quan</h2>
          <div class="jnl-related-arrows">
            <button class="jnl-related-btn jnl-related-btn--prev" aria-label="Bài trước">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            <button class="jnl-related-btn jnl-related-btn--next" aria-label="Bài tiếp">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
          </div>
        </div>
        <div class="jnl-related-track">

          <article class="journal-card">
            <a href="#" class="journal-card-img-link">
              <div class="journal-card-img-wrap">
                <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_BrownMelange2.jpg?v=1778217470&width=800" alt="Cashmere" class="journal-card-img">
              </div>
            </a>
            <div class="journal-card-body">
              <div class="jnl-card-meta"><span class="jnl-tag">Chất liệu</span><span class="jnl-date">15/06/2026</span></div>
              <h3 class="journal-card-title">Cashmere: Hiểu đúng để chọn đúng</h3>
              <p class="journal-card-excerpt">Không phải mọi cashmere đều như nhau. Đây là những gì bạn cần biết trước khi đầu tư.</p>
              <a href="#" class="journal-card-cta">Đọc thêm</a>
            </div>
          </article>

          <article class="journal-card">
            <a href="#" class="journal-card-img-link">
              <div class="journal-card-img-wrap">
                <img src="https://elleandriley.com/cdn/shop/files/Cashmere_Crew_Black4.jpg?v=1779070489&width=800" alt="Tủ đồ" class="journal-card-img">
              </div>
            </a>
            <div class="journal-card-body">
              <div class="jnl-card-meta"><span class="jnl-tag">Tủ đồ</span><span class="jnl-date">02/06/2026</span></div>
              <h3 class="journal-card-title">10 món cơ bản cho tủ đồ tối giản</h3>
              <p class="journal-card-excerpt">Ít hơn nhưng tốt hơn — nguyên tắc xây dựng tủ đồ bền vững theo phong cách LINNÉ.</p>
              <a href="#" class="journal-card-cta">Đọc thêm</a>
            </div>
          </article>

          <article class="journal-card">
            <a href="#" class="journal-card-img-link">
              <div class="journal-card-img-wrap">
                <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Red.jpg?v=1778217693&width=800" alt="Linen" class="journal-card-img">
              </div>
            </a>
            <div class="journal-card-body">
              <div class="jnl-card-meta"><span class="jnl-tag">Chăm sóc</span><span class="jnl-date">20/05/2026</span></div>
              <h3 class="journal-card-title">Giặt linen đúng cách để vải bền lâu</h3>
              <p class="journal-card-excerpt">Vài thói quen đơn giản giúp chiếc áo linen của bạn đẹp hơn sau mỗi lần giặt.</p>
              <a href="#" class="journal-card-cta">Đọc thêm</a>
            </div>
          </article>

          <article class="journal-card">
            <a href="#" class="journal-card-img-link">
              <div class="journal-card-img-wrap">
                <img src="https://elleandriley.com/cdn/shop/files/Slim_Tee_Birch.jpg?v=1778217589&width=800" alt="Phong cách" class="journal-card-img">
              </div>
            </a>
            <div class="journal-card-body">
              <div class="jnl-card-meta"><span class="jnl-tag">Phong cách</span><span class="jnl-date">07/05/2026</span></div>
              <h3 class="journal-card-title">Màu trung tính: đơn giản mà tinh tế</h3>
              <p class="journal-card-excerpt">Neutral tone không bao giờ lỗi mốt — bí quyết nằm ở cách bạn phối màu và chất liệu.</p>
              <a href="#" class="journal-card-cta">Đọc thêm</a>
            </div>
          </article>

        </div>
      </section>

    </div><!-- /.jnl-post-page -->

@endsection
