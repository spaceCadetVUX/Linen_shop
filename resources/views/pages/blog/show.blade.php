@extends('layouts.app')

@section('title', ($seoMeta?->meta_title ?? $fallbackTitle))
@section('meta-description', $seoMeta?->meta_description ?? $fallbackDescription)
@section('body-class', 'page-pd')

@php
    $categoryUrl = $blog->category_slug
        ? route($locale . '.blog.category', $blog->category_slug)
        : route($locale . '.blog.index');

    $shareUrl       = $blog->canonical_url;
    $facebookShare  = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($shareUrl);
    $pinterestShare = 'https://pinterest.com/pin/create/button/?url=' . urlencode($shareUrl)
        . ($blog->featured_image ? '&media=' . urlencode(asset($blog->featured_image)) : '')
        . '&description=' . urlencode($blog->title);
@endphp

@section('content')

<x-ui.breadcrumb :items="$breadcrumbItems" />

    <!-- ==============================  JOURNAL POST  ============================== -->
    <div class="jnl-post-page">

      <!-- HERO IMAGE -->
      @if($blog->featured_image)
        <div class="jnl-post-hero">
          <img src="{{ asset($blog->featured_image) }}" alt="{{ $blog->title }}" class="jnl-post-hero-img">
        </div>
      @endif

      <!-- ARTICLE HEADER -->
      <header class="jnl-post-hd">
        <div class="jnl-post-meta">
          @if($blog->category)
            <a href="{{ $categoryUrl }}" class="jnl-post-tag">{{ $blog->category }}</a>
            <span class="jnl-post-meta-sep">·</span>
          @endif
          <span class="jnl-post-date">{{ $blog->formatted_published_date }}</span>
          <span class="jnl-post-meta-sep">·</span>
          <span class="jnl-post-read">{{ $blog->reading_time }}</span>
        </div>
        <h1 class="jnl-post-title">{{ $blog->title }}</h1>
        @if($blog->excerpt)
          <p class="jnl-post-subtitle">{{ $blog->excerpt }}</p>
        @endif
        @if($blog->author)
          <div class="jnl-post-author">
            @if($blog->author->avatar_url)
              <img src="{{ $blog->author->avatar_url }}" alt="{{ $blog->author->name }}" class="jnl-post-author-avatar">
            @endif
            <div class="jnl-post-author-info">
              @if($blog->author->slug)
                <a href="{{ route($locale . '.author.show', $blog->author->slug) }}" class="jnl-post-author-name">{{ $blog->author->name }}</a>
              @else
                <span class="jnl-post-author-name">{{ $blog->author->name }}</span>
              @endif
              @if($blog->author->title)
                <span class="jnl-post-author-sep">—</span>
                <span class="jnl-post-author-role">{{ $blog->author->title }}</span>
              @endif
            </div>
          </div>
        @endif
      </header>

      <!-- TWO-COLUMN: BODY + STICKY RAIL -->
      <div class="jnl-post-layout">

        <!-- LEFT: Article body — HTML from Tiptap (converted in controller) -->
        <div class="jnl-post-body">

          {!! $blog->content !!}

          <!-- Share -->
          <div class="jnl-post-share">
            <span class="jnl-post-share-label">{{ $locale === 'vi' ? 'Chia sẻ' : 'Share' }}</span>
            <a href="{{ $facebookShare }}" class="jnl-post-share-link" target="_blank" rel="noopener" aria-label="Chia sẻ Facebook">Facebook</a>
            <a href="{{ $pinterestShare }}" class="jnl-post-share-link" target="_blank" rel="noopener" aria-label="Chia sẻ Pinterest">Pinterest</a>
            {{-- TODO: copy-link needs a JS handler in app.js --}}
            <a href="#" class="jnl-post-share-link" data-copy-url="{{ $shareUrl }}" aria-label="Sao chép link">{{ $locale === 'vi' ? 'Sao chép link' : 'Copy link' }}</a>
          </div>

        </div><!-- /.jnl-post-body -->

        <!-- RIGHT: Sticky sidebar -->
        {{-- TOC from mockup dropped: real Tiptap content has no anchor ids to link to.
             $categories / $latestPosts / $morePostsList / $blog->faqs available but no rail design yet. --}}
        <aside class="jnl-post-rail">

          @if($blog->author)
            <div class="jnl-rail-author">
              <span class="jnl-rail-author-name">{{ $blog->author->name }}</span>
              <span class="jnl-rail-author-role">CacyLinen</span>
            </div>

            <div class="jnl-rail-divider"></div>
          @endif

          @if(count($blog->tags))
            <div class="jnl-rail-tags">
              <h3 class="jnl-rail-title">Tags</h3>
              <div class="jnl-rail-tag-list">
                @foreach($blog->tags as $tag)
                  <a href="{{ route($locale . '.blog.index', ['q' => $tag]) }}" class="jnl-post-tag-item">{{ $tag }}</a>
                @endforeach
              </div>
            </div>
          @endif

        </aside>

      </div><!-- /.jnl-post-layout -->

      {{-- ============================================================
           FAQ — admin-managed (GeoEntityProfile.faq, falls back to legacy
           faq_items_{locale}). Reuses the PDP/category accordion pattern
           (.pd-accordions / .pd-acc-trigger) — toggle wired globally in app.js.
           ============================================================ --}}
      @if(count($blog->faqs))
        <section class="jnl-post-faq">
          <div class="jnl-post-body">
            <h2 class="jnl-post-related-hd">{{ $locale === 'vi' ? 'Câu hỏi thường gặp' : 'Frequently asked questions' }}</h2>
            <div class="pd-accordions">
              @foreach($blog->faqs as $faq)
                <div class="pd-accordion">
                  <button class="pd-acc-trigger" aria-expanded="false" type="button">
                    <span>{{ $faq['question'] }}</span>
                    <span class="pd-acc-icon" aria-hidden="true">+</span>
                  </button>
                  <div class="pd-acc-body">
                    <p>{{ $faq['answer'] }}</p>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </section>
      @endif

      @if($relatedPosts->isNotEmpty())
        <hr class="jnl-post-rule">

        <!-- RELATED POSTS SLIDER — arrows handled by app.js (.jnl-related-btn) -->
        <section class="jnl-post-related">
          <div class="jnl-related-head">
            <h2 class="jnl-post-related-hd">{{ $locale === 'vi' ? 'Bài viết liên quan' : 'Related articles' }}</h2>
            <div class="jnl-related-arrows">
              <button class="jnl-related-btn jnl-related-btn--prev" aria-label="{{ $locale === 'vi' ? 'Bài trước' : 'Previous' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><polyline points="15 18 9 12 15 6"/></svg>
              </button>
              <button class="jnl-related-btn jnl-related-btn--next" aria-label="{{ $locale === 'vi' ? 'Bài tiếp' : 'Next' }}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><polyline points="9 18 15 12 9 6"/></svg>
              </button>
            </div>
          </div>
          <div class="jnl-related-track">
            @foreach($relatedPosts as $post)
              <x-blog.card :post="$post" :locale="$locale" />
            @endforeach
          </div>
        </section>
      @endif

    </div><!-- /.jnl-post-page -->

@endsection
