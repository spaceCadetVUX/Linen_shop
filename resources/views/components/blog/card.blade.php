@props([
    'post',  // Eloquent model: title, slug, thumbnail, excerpt, published_at, category (->name, ->slug)
])

@php
    $url        = url('/blog/' . $post->slug);
    $categoryUrl = $post->category ? url('/blog/category/' . $post->category->slug) : null;
    $categoryName = $post->category->name ?? null;
    $date       = $post->published_at->format('d/m/Y');
@endphp

<article class="journal-card">

  <a href="{{ $url }}" class="journal-card-img-link">
    <div class="journal-card-img-wrap">
      <img src="{{ $post->thumbnail }}" alt="{{ $post->title }}" class="journal-card-img">
    </div>
  </a>

  <div class="journal-card-body">

    <div class="jnl-card-meta">
      @if($categoryName)
        {{-- Link to category if available, plain span otherwise --}}
        @if($categoryUrl)
          <a href="{{ $categoryUrl }}" class="jnl-tag">{{ $categoryName }}</a>
        @else
          <span class="jnl-tag">{{ $categoryName }}</span>
        @endif
      @endif
      <span class="jnl-date">{{ $date }}</span>
    </div>

    <a href="{{ $url }}" class="journal-card-title">{{ $post->title }}</a>

    <p class="journal-card-excerpt">{{ $post->excerpt }}</p>

    <a href="{{ $url }}" class="journal-card-cta">Đọc thêm</a>

  </div>

</article>
