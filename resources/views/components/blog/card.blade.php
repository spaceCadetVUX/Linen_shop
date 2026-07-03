@props([
    'post',    // decorated BlogPost from BlogController: title, slug, excerpt,
               // category (string name), category_slug, featured_image ('storage/...'),
               // formatted_published_date
    'locale' => null,
])

@php
    $locale ??= app()->getLocale();

    $url = $post->category_slug
        ? route($locale . '.blog.show', ['category_slug' => $post->category_slug, 'slug' => $post->slug])
        : '#';
    $categoryUrl = $post->category_slug
        ? route($locale . '.blog.category', $post->category_slug)
        : null;
    $image = $post->featured_image
        ? asset($post->featured_image)
        : asset('assets/img/placeholder-category.jpg');
@endphp

<article {{ $attributes->merge(['class' => 'journal-card']) }}>

  <a href="{{ $url }}" class="journal-card-img-link">
    <div class="journal-card-img-wrap">
      <img src="{{ $image }}" alt="{{ $post->title }}" class="journal-card-img" loading="lazy">
    </div>
  </a>

  <div class="journal-card-body">

    <div class="jnl-card-meta">
      @if($post->category)
        @if($categoryUrl)
          <a href="{{ $categoryUrl }}" class="jnl-tag">{{ $post->category }}</a>
        @else
          <span class="jnl-tag">{{ $post->category }}</span>
        @endif
      @endif
      @if($post->formatted_published_date)
        <span class="jnl-date">{{ $post->formatted_published_date }}</span>
      @endif
    </div>

    <a href="{{ $url }}" class="journal-card-title">{{ $post->title }}</a>

    <p class="journal-card-excerpt">{{ $post->excerpt }}</p>

    <a href="{{ $url }}" class="journal-card-cta">{{ $locale === 'vi' ? 'Đọc thêm' : 'Read more' }}</a>

  </div>

</article>
