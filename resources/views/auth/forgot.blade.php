@extends('layouts.site')
@section('title', 'Reset your password')
@section('noindex', '1')
@section('content')
<div class="auth">
  <div class="stack" style="gap:10px"><span class="eyebrow">Account</span><h1 class="h2">Reset your password</h1>
  <p class="muted" style="margin:0">Enter the address you signed up with. We send a link that works for 60 minutes.</p></div>
  <form class="form" method="POST" action="/forgot-password">
    @csrf
    <label>Email<input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"></label>
    <div class="row"><button class="btn gold" type="submit">Send the link</button><a class="btn link" href="/login">Back to sign in</a></div>
  </form>
</div>
@endsection
