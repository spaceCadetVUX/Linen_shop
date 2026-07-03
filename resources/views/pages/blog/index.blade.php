@extends('layouts.app')

@section('title', ($category ?? $fallbackTitle) . ' — LINNÉ')
@section('meta-description', $fallbackDescription)
@section('body-class', 'page-pd')

@php
    // Featured = newest post, only on page 1 without search/filter
    // (otherwise the first result would vanish from the grid and confuse paging)
    $featured  = $blogs->onFirstPage() && ! $searchTerm && ! $category ? $blogs->first() : null;
    $gridPosts = $featured ? $blogs->getCollection()->slice(1) : $blogs->getCollection();
@endphp

@section('content')

    <!-- ==============================  JOURNAL PAGE  ============================== -->
    <div class="jnl-page">

      <!-- Header -->
      <div class="jnl-hd">
        <p class="jnl-hd-eyebrow">LINNÉ · {{ $locale === 'vi' ? 'Nhật ký thời trang' : 'Fashion journal' }}</p>
        <h1 class="jnl-hd-title"><em>Journal</em></h1>
      </div>

      <!-- Featured article -->
      @if($featured)
        @php
          $featuredUrl = $featured->category_slug
              ? route($locale . '.blog.show', ['category_slug' => $featured->category_slug, 'slug' => $featured->slug])
              : '#';
          $featuredImg = $featured->featured_image
              ? asset($featured->featured_image)
              : asset('assets/img/placeholder-category.jpg');
        @endphp
        <article class="jnl-featured">
          <a href="{{ $featuredUrl }}" class="jnl-featured-img-link">
            <div class="jnl-featured-img-wrap">
              <img src="{{ $featuredImg }}" alt="{{ $featured->title }}" class="jnl-featured-img">
            </div>
          </a>
          <div class="jnl-featured-body">
            <div class="jnl-featured-meta">
              @if($featured->category)
                <span class="jnl-tag">{{ $featured->category }}</span>
              @endif
              @if($featured->formatted_published_date)
                <span class="jnl-date">{{ $featured->formatted_published_date }}</span>
              @endif
            </div>
            <h2 class="jnl-featured-title">{{ $featured->title }}</h2>
            <p class="jnl-featured-excerpt">{{ $featured->excerpt }}</p>
            <a href="{{ $featuredUrl }}" class="jnl-featured-cta">{{ $locale === 'vi' ? 'Đọc bài viết' : 'Read article' }}
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
              </svg>
            </a>
          </div>
        </article>
      @endif

      <!-- Divider -->
      <div class="jnl-divider">
        <span class="jnl-divider-label">
          @if($category)
            {{ $category }}
          @elseif($searchTerm)
            {{ $locale === 'vi' ? 'Kết quả cho' : 'Results for' }} “{{ $searchTerm }}”
          @else
            {{ $locale === 'vi' ? 'Tất cả bài viết' : 'All articles' }}
          @endif
        </span>
      </div>

      <!-- Articles grid -->
      @if($gridPosts->isNotEmpty())
        <div class="jnl-grid journal-grid">
          @foreach($gridPosts as $post)
            <x-blog.card :post="$post" :locale="$locale" class="jnl-card" />
          @endforeach
        </div><!-- /.jnl-grid -->
      @elseif(! $featured)
        <p style="text-align:center; padding: 8px var(--pad-x) 0; color: var(--ash);">
          {{ $locale === 'vi' ? 'Chưa có bài viết nào.' : 'No articles yet.' }}
        </p>
      @endif

      <!-- Pagination — reuses .jnl-load-btn styling (line-height inline: anchor lacks button's auto vertical centering) -->
      @if($blogs->hasPages())
        <div class="jnl-load-more">
          @if($blogs->previousPageUrl())
            <a href="{{ $blogs->previousPageUrl() }}" class="jnl-load-btn" style="line-height:44px;">← {{ $locale === 'vi' ? 'Trang trước' : 'Previous' }}</a>
          @endif
          @if($blogs->hasMorePages())
            <a href="{{ $blogs->nextPageUrl() }}" class="jnl-load-btn" style="line-height:44px;">{{ $locale === 'vi' ? 'Xem thêm bài viết' : 'More articles' }} →</a>
          @endif
        </div>
      @endif

    </div><!-- /.jnl-page -->

@endsection
