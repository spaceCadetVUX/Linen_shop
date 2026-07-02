@extends('layouts.app')

@section('title', $fallbackTitle . ' — LINNÉ')
@section('meta-description', $fallbackDescription)
@section('body-class', 'page-pd')

@section('content')

<x-ui.breadcrumb :items="[
    ['label' => $locale === 'vi' ? 'Trang chủ' : 'Home', 'url' => route($locale . '.index')],
    ['label' => $fallbackTitle, 'url' => null],
]" />

<section class="plp-banner">
  <div class="plp-banner-info">
    <h1 class="plp-banner-title">{{ $fallbackTitle }}</h1>
    <p class="plp-banner-sub">{{ $categories->count() }} {{ $locale === 'vi' ? 'danh mục' : 'categories' }}</p>
  </div>
</section>

<section class="shop-section cat-index-section">
  @if($categories->isEmpty())
    <p class="plp-empty">{{ $locale === 'vi' ? 'Chưa có danh mục nào.' : 'No categories yet.' }}</p>
  @else
    <ul class="cat-index-list">
      @foreach($categories as $translation)
        <li class="cat-index-item">
          <a href="{{ route($locale . '.category.show', $translation->slug) }}" class="cat-index-link">
            <span class="cat-index-name">{{ $translation->name }}</span>
            <span class="cat-index-count">{{ $translation->category->product_count }} {{ $locale === 'vi' ? 'sản phẩm' : 'products' }}</span>
          </a>
        </li>
      @endforeach
    </ul>
  @endif
</section>

@endsection
