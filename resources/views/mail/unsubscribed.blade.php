@extends('layouts.site')

@section('title', 'Email preferences')
@section('noindex')@endsection

@section('content')
<div class="auth">
  <p class="eyebrow">Email</p>
  <h1 class="h2">{{ $heading }}</h1>
  <p class="lead">{{ $detail }}</p>
  <p><a class="btn" href="/account">Account</a></p>
</div>
@endsection
