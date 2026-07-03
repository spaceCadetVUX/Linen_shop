@extends('layouts.app')

@section('title', $fallbackTitle . ' — LINNÉ')
@section('meta-description', $fallbackDescription)
@section('body-class', 'page-pd')

@section('content')

<x-ui.breadcrumb :items="[
    ['label' => $locale === 'vi' ? 'Trang chủ' : 'Home', 'url' => route($locale . '.index')],
    ['label' => $translation->title, 'url' => null],
]" />

<section class="static-page">
  <h1 class="static-page-title">{{ $translation->title }}</h1>

  <div class="static-page-body">
    {!! $translation->body !!}
  </div>
</section>

@endsection
