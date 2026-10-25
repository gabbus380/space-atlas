@extends('layouts.site')
@section('title', 'API Order Form')
@section('content')
<div class="grid2" style="align-items:start">
  <div class="stack">
    <span class="eyebrow">API</span>
    <h1 class="h2">Request an Order Form</h1>
    <p class="lead">Tell us how your organization will use the atlas data. We reply with an Order Form for ${{ number_format(\App\Support\Plans::prices()['api_year']) }} a year. No card is taken on this page.</p>
    @if ($user)
      <p class="muted" style="margin:0">Sending this as <strong>{{ $user->email }}</strong>, a {{ $tier === 'teams' ? 'Teams' : ($tier === 'personal' ? 'Personal' : 'free') }} account.</p>
    @else
      <p class="muted" style="margin:0">You do not need an account yet. Sending this form creates a free one, which you can use to sign in and follow the request.</p>
    @endif
    <form class="form" method="POST" action="/api-order">@csrf
      <label>Full name<input type="text" name="name" value="{{ old('name', $user?->name) }}" required autocomplete="name"></label>
      @if ($user)
        <label>Email<input type="email" value="{{ $user->email }}" disabled></label>
      @else
        <label>Work email<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email"></label>
      @endif
      <label>Organization<input type="text" name="organization" value="{{ old('organization', $user?->organization) }}" required autocomplete="organization"></label>
      <label>Your title <span class="faint">(optional)</span><input type="text" name="job_title" value="{{ old('job_title', $user?->job_title) }}"></label>
      <label>People who will use the data <span class="faint">(optional)</span><input type="number" name="people" value="{{ old('people', config('atlas.api.starter_users')) }}" min="1"></label>
      <label>How you will use the API<textarea name="message" rows="5" required>{{ old('message') }}</textarea></label>
      @unless ($user)
        <label>Password <span class="faint">(at least 10 characters, letters and numbers)</span><input type="password" name="password" required autocomplete="new-password"></label>
        <label>Repeat the password<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
        <label class="check"><input type="checkbox" name="terms" value="1" required @checked(old('terms'))> <span>I accept the <a href="/legal#terms" target="_blank">Terms of Service</a> and have read the <a href="/legal#privacy" target="_blank">Privacy Policy</a>.</span></label>
      @endunless
      <div class="row"><button class="btn gold" type="submit">Send the request</button></div>
    </form>
    @unless ($user)
      <p class="muted" style="margin:0">Already registered? <a href="/login?next=/api-order">Sign in</a> and send it from your Personal or Teams account.</p>
    @endunless
  </div>
  <aside class="card stack">
    <h2 class="h3">What the Order Form covers</h2>
    <ul class="muted">
      <li>Countries, country files, programs, organizations, engagements, briefings and events</li>
      <li>JSON over HTTPS, refreshed daily, with sources and a timestamp</li>
      <li>Up to {{ config('atlas.api.starter_users') }} people and {{ number_format(config('atlas.api.starter_quota')) }} requests a day</li>
      <li>Billed yearly. Larger use is quoted</li>
    </ul>
  </aside>
</div>
@endsection
