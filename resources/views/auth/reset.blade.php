@extends('layouts.site')
@section('title', 'Choose a new password')
@section('noindex', '1')
@section('content')
<div class="auth">
  <div class="stack" style="gap:10px"><span class="eyebrow">Account</span><h1 class="h2">Choose a new password</h1></div>
  <form class="form" method="POST" action="/reset-password">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <label>Email<input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email"></label>
    <label>New password <span class="faint">(at least 10 characters, letters and numbers)</span><input type="password" name="password" required autocomplete="new-password"></label>
    <label>Repeat the password<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
    <div class="row"><button class="btn gold" type="submit">Save the new password</button></div>
  </form>
</div>
@endsection
