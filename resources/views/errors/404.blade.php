@extends('layouts.app')

@section('title', 'Không tìm thấy trang')
@section('meta-description', 'Trang bạn tìm không còn ở đây. Khám phá bộ sưu tập CacyLinen hoặc quay về trang chủ.')
@section('body-class', 'page-pd')

@section('content')

  <!-- ==============================  404  ============================== -->
  <section class="err-section" aria-label="Trang không tồn tại">

    <div class="err-bg-num" aria-hidden="true">404</div>

    <div class="err-inner">
      <p class="err-eyebrow">Trang không tồn tại</p>

      <h1 class="err-title">
        Trang bạn tìm<br>
        <em>không còn ở đây.</em>
      </h1>

      <p class="err-sub">
        Có thể đường dẫn đã thay đổi, hoặc trang này<br>
        chưa bao giờ tồn tại trong bộ sưu tập của chúng tôi.
      </p>

      <a href="{{ url('/') }}" class="err-btn">Về trang chủ</a>

      <div class="err-explore">
        <span class="err-explore-label">Hoặc khám phá</span>
        <div class="err-explore-links">
          <a href="{{ url('/collections') }}">Bộ sưu tập</a>
          <a href="{{ url('/blog') }}">Journal</a>
          <a href="{{ url('/contact') }}">Liên hệ</a>
        </div>
      </div>
    </div>

  </section>

@endsection
