@extends('layouts.app')

@section('title', __('errors.404.title'))
@section('meta-description', __('errors.404.meta_description'))
@section('body-class', 'page-pd')

{{-- 404 is never real content — keep it out of the index regardless of the
     default 'index,follow' in seo-head.blade.php, but still let crawlers
     follow the recovery links below. --}}
@php $metaRobots = 'noindex, follow'; @endphp

@section('content')

  <!-- ==============================  404  ============================== -->
  <section class="err-section" aria-label="{{ __('errors.404.eyebrow') }}">

    <div class="err-bg-num" aria-hidden="true">404</div>

    <div class="err-inner">
      <p class="err-eyebrow">{{ __('errors.404.eyebrow') }}</p>

      <h1 class="err-title">
        {{ __('errors.404.title_line1') }}<br>
        <em>{{ __('errors.404.title_line2') }}</em>
      </h1>

      <p class="err-sub">
        {{ __('errors.404.sub_line1') }}<br>
        {{ __('errors.404.sub_line2') }}
      </p>

      <a href="{{ route(current_locale() . '.index') }}" class="err-btn">{{ __('errors.404.home_button') }}</a>

      <div class="err-explore">
        <span class="err-explore-label">{{ __('errors.404.explore_label') }}</span>
        <div class="err-explore-links">
          <a href="{{ route(current_locale() . '.product.shop') }}">{{ __('errors.404.explore_collections') }}</a>
          <a href="{{ route(current_locale() . '.blog.index') }}">{{ __('errors.404.explore_blog') }}</a>
          <a href="{{ route(current_locale() . '.page.show', ['slug' => 'contact']) }}">{{ __('errors.404.explore_contact') }}</a>
        </div>
      </div>
    </div>

  </section>

@endsection
