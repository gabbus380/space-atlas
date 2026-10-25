@extends('layouts.site')

@section('title', 'Email preferences')
@section('noindex')@endsection

@section('content')
<div class="auth">
  <p class="eyebrow">Email</p>
  <h1 class="h2">{{ $heading }}</h1>
  <p class="lead">{{ $detail }}</p>
  @if (! empty($action))
    <form method="POST" action="{{ $action }}">
      <button class="btn gold" type="submit">Unsubscribe</button>
    </form>
  @endif
  <p><a class="btn" href="/account">Account</a></p>
</div>
@endsection
