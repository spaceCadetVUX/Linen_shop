@extends('layouts.app')

@section('title', $fallbackTitle)
@section('meta-description', $fallbackDescription)
@section('body-class', 'page-pd')

@section('content')

<x-ui.breadcrumb :items="[
    ['label' => $locale === 'vi' ? 'Trang chủ' : 'Home', 'url' => route($locale . '.index')],
    ['label' => $fallbackTitle, 'url' => null],
]" />

<section class="sg-page">
  <h1 class="sg-title">{{ $fallbackTitle }}</h1>

  @if($guides->isEmpty())
    <p class="sg-empty">
      {{ $locale === 'vi' ? 'Nội dung đang được cập nhật.' : 'Content coming soon.' }}
    </p>
  @else
    @if($guides->count() > 1)
      <nav class="sg-nav" aria-label="{{ $fallbackTitle }}">
        @foreach($guides as $guide)
          <a href="#sg-{{ $guide['key'] }}">{{ $guide['name'] }}</a>
        @endforeach
      </nav>
    @endif

    @foreach($guides as $guide)
      <section class="sg-section" id="sg-{{ $guide['key'] }}">
        <h2 class="sg-name">{{ $guide['name'] }}</h2>
        <div class="sg-body">{!! $guide['body'] !!}</div>
      </section>
    @endforeach
  @endif
</section>

@endsection
