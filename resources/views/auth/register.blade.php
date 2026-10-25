@extends('layouts.site')
@section('title', 'Create a free account')
@section('noindex', '1')
@section('content')
<div class="auth">
  <div class="stack" style="gap:10px">
    <span class="eyebrow">Free account</span>
    <h1 class="h2">Create your account</h1>
    <p class="muted" style="margin:0">Free, no card. It opens {{ config('atlas.free_files_per_month') }} full country files a month, and it is the account you subscribe with if you upgrade.</p>
  </div>
  <form class="form" method="POST" action="/register">
    @csrf
    <label>Full name<input type="text" name="name" value="{{ old('name') }}" required autocomplete="name"></label>
    <label>Work email<input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email"></label>
    <label>Organization <span class="faint">(optional)</span><input type="text" name="organization" value="{{ old('organization') }}" autocomplete="organization"></label>
    <label>Password <span class="faint">(at least 10 characters, letters and numbers)</span><input type="password" name="password" required autocomplete="new-password"></label>
    <label>Repeat the password<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
    <label class="check"><input type="checkbox" name="terms" value="1" required @checked(old('terms'))> <span>I accept the <a href="/legal#terms" target="_blank">Terms of Service</a> and have read the <a href="/legal#privacy" target="_blank">Privacy Policy</a>.</span></label>
    <label class="check"><input type="checkbox" name="marketing" value="1" @checked(old('marketing'))> <span>Send me the monthly note about the atlas from EMMA Aerospace Consulting. You can unsubscribe at any time.</span></label>
    <div class="row"><button class="btn gold" type="submit">Create account</button></div>
  </form>
  @include('auth.providers')
  <p class="muted" style="margin:0">Already have an account? <a href="/login">Sign in</a>.</p>
</div>
@endsection
