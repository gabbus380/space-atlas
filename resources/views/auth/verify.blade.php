@extends('layouts.site')
@section('title', 'Confirm your email')
@section('noindex', '1')
@section('content')
@php($next = session('url.intended'))
<div class="auth">
  <div class="stack" style="gap:10px"><span class="eyebrow">One more step</span><h1 class="h2">Confirm your email</h1>
  <p class="muted" style="margin:0">We sent a link to <strong style="color:var(--ink)">{{ auth()->user()->email }}</strong>. Open it to confirm the address.
  @if ($next && str_contains($next, '/subscribe/')) Then we take you straight to checkout. @elseif ($next && str_contains($next, '/invite/')) Then you join your team. @else Then your {{ config('atlas.free_files_per_month') }} free country files are open. @endif</p></div>
  <div class="row">
    <form method="POST" action="/email/verification-notification">@csrf<button class="btn" type="submit">Send the link again</button></form>
    <a class="btn link" href="/">Back to the atlas</a>
  </div>
  <p class="faint" style="margin:0;font-size:13px">Nothing after a few minutes? Check the spam folder, or write to {{ config('atlas.contact') }}.</p>
</div>
@endsection
