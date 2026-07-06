{{-- Cards-only fragment — reused by ProductController@index and CategoryController@show
     for the AJAX "Xem thêm" append (see resources/views/components/product/grid.blade.php). --}}
@foreach($products as $product)
  <x-product.card :product="$product" :category="$product->product->categories->first()?->slug" />
@endforeach
