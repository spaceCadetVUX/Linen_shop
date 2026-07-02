@props([
    'items', // array of ['label' => string, 'url' => string|null] — last item (no url) renders as current page
])

<nav class="pd-breadcrumb" aria-label="Breadcrumb">
  <div class="pd-breadcrumb-inner">
    @foreach($items as $item)
      @if(!$loop->first)
        <span class="pd-bc-sep" aria-hidden="true">/</span>
      @endif
      @if(!empty($item['url']))
        <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
      @else
        <span aria-current="page">{{ $item['label'] }}</span>
      @endif
    @endforeach
  </div>
</nav>
