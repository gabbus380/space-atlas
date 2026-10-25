@extends('layouts.site')
@section('title', 'Team invitation')
@section('noindex', '1')
@section('content')
<div class="auth">
  <span class="eyebrow">Team invitation</span>
  @if ($state === 'expired')
    <h1 class="h2">This invitation has expired</h1>
    <p class="muted">Ask the team owner to send a new one. Invitation links work for 14 days and only once.</p>
  @elseif ($state === 'wrong-user')
    <h1 class="h2">This invitation is for another address</h1>
    <p class="muted">It was sent to {{ $invitation->email }}. Sign out and sign in with that address, or ask the owner to invite the address you use.</p>
    <form method="POST" action="/logout">@csrf<button class="btn" type="submit">Sign out</button></form>
  @else
    <h1 class="h2">You are already on another team</h1>
    <p class="muted">Leave your current team from the team page, then open this link again.</p>
    <a class="btn" href="/team">Open the team page</a>
  @endif
</div>
@endsection
