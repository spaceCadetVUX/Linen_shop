<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  @php
    // Hậu tố tab title lấy từ Business Profile (site_name) — đổi tên shop
    // một chỗ, toàn site đổi theo. Page chỉ set phần tên riêng, KHÔNG kèm suffix.
    //
    // $__pageTitle đã được Blade tự escape 1 lần: mọi trang set title qua dạng
    // @section('title', $var) (2 tham số) — Illuminate\View\Concerns\ManagesLayouts::startSection()
    // áp dụng e($content) ngay khi nhận giá trị. Vì vậy $__siteName cũng phải
    // escape thủ công ở đây rồi render bằng {!! !!} bên dưới — nếu để {{ }}
    // escape thêm lần nữa sẽ ra "&amp;amp;" khi title chứa &, <, >...
    $__siteName = e(\App\Models\Setting::get('site_name') ?: config('app.name', 'CacyLinen'));
    $__pageTitle = trim($__env->yieldContent('title'));
    $__tabTitle = ($__pageTitle === '' || $__pageTitle === $__siteName)
        ? $__siteName
        : (str_ends_with($__pageTitle, '- '.$__siteName) ? $__pageTitle : $__pageTitle.' - '.$__siteName);
  @endphp
  <title>{!! $__tabTitle !!}</title>
  <meta name="description" content="@yield('meta-description', 'CacyLinen - Thời trang linen tối giản, bền vững.')">
  @include('partials.seo-head')
  @stack('meta')
  <link rel="icon" href="{{ $faviconUrl ?? asset('favicon.ico') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:ital,wght@0,300;0,400;0,500;1,300;1,400;1,500&family=Be+Vietnam+Pro:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
  @stack('styles')
</head>
<body class="@yield('body-class')">

  @include('partials.header')

  <div id="smooth-wrap">
    @yield('content')
    @include('partials.footer')
  </div>

  <script src="{{ asset('assets/js/app.js') }}"></script>
  @stack('scripts')

</body>
</html>
