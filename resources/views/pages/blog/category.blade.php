@extends('layouts.app')

@section('title', ($seoMeta?->meta_title ?? $fallbackTitle))
@section('meta-description', $seoMeta?->meta_description ?? $fallbackDescription)
@section('body-class', 'page-pd')

@section('content')

    <!-- ==============================  BLOG CATEGORY  ============================== -->
    {{-- Temporary UI: reuses the jnl-* structure from pages/blog/index.blade.php --}}
    <div class="jnl-page">

      <!-- Header -->
      <div class="jnl-hd">
        <p class="jnl-hd-eyebrow">
          <a href="{{ route($locale . '.blog.index') }}">CacyLinen · Journal</a>
        </p>
        <h1 class="jnl-hd-title"><em>{{ $translation->name }}</em></h1>

        @if($translation->description)
          <p style="margin-top:16px; max-width:560px; font-size:13px; line-height:1.7; color:var(--graphite);">
            {{ $translation->description }}
          </p>
        @endif

        {{-- Subcategory pills — children come with per-locale name/slug + blog_count from controller --}}
        @if($blogCategory->children->isNotEmpty())
          <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:24px;">
            @foreach($blogCategory->children as $child)
              <a href="{{ route($locale . '.blog.category', $child->slug) }}" class="jnl-tag">
                {{ $child->name }}@if($child->blog_count) ({{ $child->blog_count }})@endif
              </a>
            @endforeach
          </div>
        @endif
      </div>

      <!-- Divider -->
      <div class="jnl-divider">
        <span class="jnl-divider-label">
          {{ $locale === 'vi' ? 'Tất cả bài viết' : 'All articles' }} · {{ $blogs->total() }}
        </span>
      </div>

      <!-- Articles grid -->
      @if($blogs->isNotEmpty())
        <div class="jnl-grid journal-grid">
          @foreach($blogs as $post)
            <x-blog.card :post="$post" :locale="$locale" class="jnl-card" />
          @endforeach
        </div><!-- /.jnl-grid -->
      @else
        <p style="text-align:center; padding: 8px var(--pad-x) 0; color: var(--ash);">
          {{ $locale === 'vi' ? 'Chưa có bài viết nào trong chủ đề này.' : 'No articles in this topic yet.' }}
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

      {{-- TODO: $faqs (GEO profile) not rendered yet — no FAQ section design for this page --}}

    </div><!-- /.jnl-page -->

@endsection
