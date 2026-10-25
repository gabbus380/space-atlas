@extends('layouts.site')
@section('title', 'Sign in')
@section('noindex', '1')
@section('content')
<div class="auth">
  <div class="stack" style="gap:10px">
    <span class="eyebrow">Sovereign Space ATLAS</span>
    <h1 class="h2">Sign in</h1>
  </div>
  <form class="form" method="POST" action="/login">
    @csrf
    <label>Email<input type="email" name="email" value="{{ old('email', request('email')) }}" required autofocus autocomplete="email"></label>
    <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
    <label class="check"><input type="checkbox" name="remember" value="1"> Keep me signed in on this device</label>
    <div class="row"><button class="btn gold" type="submit">Sign in</button><a class="btn link" href="/forgot-password">Forgot your password?</a></div>
  </form>
  <p class="muted" style="margin:0">New here? <a href="/register{{ request('email') ? '?email='.urlencode(request('email')) : '' }}">Create a free account</a>. It opens {{ config('atlas.free_files_per_month') }} full country files a month.</p>
</div>
@endsection
