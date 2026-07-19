@extends('layouts.app')

@section('title', $fallbackTitle)
@section('meta-description', $fallbackDescription)
@section('body-class', 'page-pd')

@section('content')

<x-ui.breadcrumb :items="[
    ['label' => $locale === 'vi' ? 'Trang chủ' : 'Home', 'url' => route($locale . '.index')],
    ['label' => $translation->title, 'url' => null],
]" />

<div class="jnl-post-page">

  <header class="jnl-post-hd">
    <h1 class="jnl-post-title">{{ $translation->title }}</h1>
  </header>

  <div class="jnl-post-body">
    {!! $translation->body !!}
  </div>

</div>

@endsection
