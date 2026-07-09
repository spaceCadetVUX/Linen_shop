@extends('layouts.app')

@section('title', $fallbackTitle)
@section('meta-description', $fallbackDescription)
@section('body-class', 'page-pd')

@section('content')

<x-ui.breadcrumb :items="$breadcrumbItems" />

<section class="plp-banner">
  <div class="plp-banner-info">
    <h1 class="plp-banner-title">{{ $fallbackTitle }}</h1>
    <p class="plp-banner-sub">{{ $categoryCards->count() }} {{ $locale === 'vi' ? 'danh mục' : 'categories' }}</p>
  </div>
</section>

<section class="shop-section">
  @if($categoryCards->isEmpty())
    <p class="plp-empty">{{ $locale === 'vi' ? 'Chưa có danh mục nào.' : 'No categories yet.' }}</p>
  @else
    {{-- Same image-tile + italic-serif-name treatment as the homepage editorial
         grid (see .edit-grid in home/index.blade.php), sized for a browse-all
         grid instead of a 3-6 item hero teaser. --}}
    <div class="cat-index-grid">
      @foreach($categoryCards as $card)
        <a href="{{ $card['url'] }}" class="cat-index-card">
          @if($card['image_url'])
            <div class="cat-index-card-img" style="background-image:url('{{ $card['image_url'] }}')"></div>
          @else
            <div class="cat-index-card-img {{ $card['fallback_class'] }}"></div>
          @endif
          <div class="cat-index-card-label">
            <span class="cat-index-card-name">{{ $card['name'] }}</span>
            <span class="cat-index-card-count">{{ $card['count'] }} {{ $locale === 'vi' ? 'sản phẩm' : 'products' }}</span>
          </div>
        </a>
      @endforeach
    </div>
  @endif
</section>

@endsection
