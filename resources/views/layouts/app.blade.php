<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'LINNÉ')</title>
  <meta name="description" content="@yield('meta-description', 'LINNÉ — Thời trang linen tối giản, bền vững.')">
  @include('partials.seo-head')
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
