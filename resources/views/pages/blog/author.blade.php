@extends('layouts.app')

@section('title', $fallbackTitle)
@section('meta-description', $fallbackDescription)
@section('body-class', 'page-pd')

@section('content')

<x-ui.breadcrumb :items="$breadcrumbItems" />

    <!-- ==============================  AUTHOR PAGE  ============================== -->
    <div class="jnl-page author-page">

      <!-- Author hero -->
      <div class="author-hero">

        <div class="author-hero-avatar-wrap">
          @if($author->avatar_url)
            <img src="{{ $author->avatar_url }}" alt="{{ $author->name }}" class="author-hero-avatar">
          @else
            <span class="author-hero-avatar author-hero-avatar--placeholder" aria-hidden="true">
              {{ mb_substr($author->name, 0, 1) }}
            </span>
          @endif
        </div>

        <div class="author-hero-body">
          <p class="jnl-hd-eyebrow">CacyLinen &middot; {{ $locale === 'vi' ? 'Tác giả' : 'Author' }}</p>
          <h1 class="author-hero-name">{{ $author->name }}</h1>

          @if($author->title)
            <p class="author-hero-title">{{ $author->title }}</p>
          @endif

          @if($author->bio)
            <p class="author-hero-bio">{{ $author->bio }}</p>
          @endif

          @if(!empty($author->expertise))
            <div class="author-hero-expertise">
              @foreach($author->expertise as $topic)
                <span class="jnl-tag">{{ $topic }}</span>
              @endforeach
            </div>
          @endif

          @if($author->website || $author->linkedin || $author->twitter || $author->facebook)
            <div class="author-hero-social">
              @if($author->website)
                <a href="{{ $author->website }}" class="author-social-link" target="_blank" rel="noopener" aria-label="Website">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M3 12h18M12 3c2.4 2.6 3.7 5.7 3.7 9s-1.3 6.4-3.7 9c-2.4-2.6-3.7-5.7-3.7-9s1.3-6.4 3.7-9z"/>
                  </svg>
                </a>
              @endif
              @if($author->linkedin)
                <a href="{{ $author->linkedin }}" class="author-social-link" target="_blank" rel="noopener" aria-label="LinkedIn">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M4.98 3.5C3.88 3.5 3 4.38 3 5.48c0 1.1.88 2 1.98 2h.02c1.11 0 1.98-.9 1.98-2 0-1.1-.87-1.98-1.98-1.98zM3.2 8.9h3.6V21H3.2V8.9zM9.5 8.9h3.45v1.65h.05c.48-.9 1.66-1.85 3.4-1.85 3.63 0 4.3 2.39 4.3 5.5V21h-3.6v-5.4c0-1.29-.02-2.95-1.8-2.95-1.8 0-2.08 1.4-2.08 2.86V21H9.5V8.9z"/>
                  </svg>
                </a>
              @endif
              @if($author->twitter)
                <a href="{{ $author->twitter }}" class="author-social-link" target="_blank" rel="noopener" aria-label="X (Twitter)">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                  </svg>
                </a>
              @endif
              @if($author->facebook)
                <a href="{{ $author->facebook }}" class="author-social-link" target="_blank" rel="noopener" aria-label="Facebook">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                  </svg>
                </a>
              @endif
            </div>
          @endif
        </div>

      </div><!-- /.author-hero -->

      <!-- Divider -->
      <div class="jnl-divider">
        <span class="jnl-divider-label">
          {{ $locale === 'vi' ? 'Bài viết của' : 'Articles by' }} {{ $author->name }} &middot; {{ $posts->total() }}
        </span>
      </div>

      <!-- Articles grid -->
      @if($posts->isNotEmpty())
        <div class="jnl-grid journal-grid">
          @foreach($posts as $post)
            <x-blog.card :post="$post" :locale="$locale" class="jnl-card" />
          @endforeach
        </div><!-- /.jnl-grid -->
      @else
        <p style="text-align:center; padding: 8px var(--pad-x) 0; color: var(--ash);">
          {{ $locale === 'vi' ? 'Tác giả chưa có bài viết nào.' : 'This author has no articles yet.' }}
        </p>
      @endif

      <!-- Pagination -->
      @if($posts->hasPages())
        <div class="jnl-load-more">
          @if($posts->previousPageUrl())
            <a href="{{ $posts->previousPageUrl() }}" class="jnl-load-btn" style="line-height:44px;">← {{ $locale === 'vi' ? 'Trang trước' : 'Previous' }}</a>
          @endif
          @if($posts->hasMorePages())
            <a href="{{ $posts->nextPageUrl() }}" class="jnl-load-btn" style="line-height:44px;">{{ $locale === 'vi' ? 'Xem thêm bài viết' : 'More articles' }} →</a>
          @endif
        </div>
      @endif

    </div><!-- /.jnl-page -->

@endsection
