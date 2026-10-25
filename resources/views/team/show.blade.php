@php($p = \App\Support\Plans::prices())
@extends('layouts.site')
@section('title', $team->name)
@section('noindex', '1')
@section('content')
<div class="stack" style="gap:10px">
  <span class="eyebrow">Team</span>
  <h1 class="h2">{{ $team->name }}</h1>
  <p class="muted" style="margin:0">
    @if ($active) <span class="pill green">Active</span> @else <span class="pill red">Not active</span> @endif
    &nbsp;{{ $seats }} seats, {{ $used }} in use
    @if ($subscription) &middot; billed {{ $interval === 'month' ? 'monthly' : 'yearly' }} @elseif ($team->hasOverride()) &middot; arranged with EMMA until {{ $team->override_until->format('F j, Y') }} @endif
  </p>
</div>

<div class="grid2" style="margin-top:28px;align-items:start">
  <section class="card stack" aria-labelledby="mH">
    <h2 class="h3" id="mH">Members</h2>
    <div class="tw"><table class="t">
      <thead><tr><th>Name</th><th>Email</th><th>Since</th>@if($owner)<th></th>@endif</tr></thead>
      <tbody>
      @foreach ($members as $m)
        <tr><td>{{ $m->name }} @if($m->id === $team->owner_id)<span class="pill gold">Owner</span>@endif @if(! $team->hasSeatFor($m))<span class="pill red">No seat</span>@endif</td>
          <td>{{ $m->email }}</td><td>{{ $m->team_joined_at?->format('M j, Y') }}</td>
          @if ($owner)<td>@if ($m->id !== $team->owner_id)<form method="POST" action="/team/members/{{ $m->id }}">@csrf @method('DELETE')<button class="btn sm" type="submit">Remove</button></form>@endif</td>@endif</tr>
      @endforeach
      @foreach ($invitations as $i)
        <tr><td class="faint">Invited</td><td>{{ $i->email }}</td><td class="faint">until {{ $i->expires_at->format('M j') }}</td>
          <td><form method="POST" action="/team/invitations/{{ $i->id }}">@csrf @method('DELETE')<button class="btn sm" type="submit">Withdraw</button></form></td></tr>
      @endforeach
      </tbody>
    </table></div>
    @if ($owner)
      @if ($active && $used < $seats)
        <form class="row" method="POST" action="/team/invitations">@csrf
          <input type="email" name="email" placeholder="colleague@organization.org" required style="flex:1;min-width:220px" aria-label="Email to invite">
          <button class="btn gold sm" type="submit">Invite</button></form>
      @elseif ($active)
        <p class="muted" style="margin:0">Every seat is taken. Add a seat to invite someone else.</p>
      @else
        <div class="row"><a class="btn gold sm" href="/subscribe/teams">Activate the team plan</a></div>
      @endif
    @else
      <form method="POST" action="/team/leave">@csrf<button class="btn sm" type="submit">Leave the team</button></form>
    @endif
  </section>

  @if ($owner)
  <div class="stack">
    <section class="card stack" aria-labelledby="sH">
      <h2 class="h3" id="sH">Seats and billing</h2>
      @if ($subscription)
        <form class="row" method="POST" action="/team/seats">@csrf
          <label style="flex-direction:row;align-items:center;gap:10px">Seats <input type="number" name="seats" value="{{ $seats }}" min="{{ max($p['teams_min'], $used) }}" max="{{ config('atlas.teams_max_seats') }}" style="width:96px"></label>
          <button class="btn sm" type="submit">Update</button></form>
        <p class="faint" style="margin:0;font-size:13px">${{ $interval === 'month' ? $p['teams_month'].' per user a month' : $p['teams_year'].' per user a year' }}. Paddle prorates seat changes.</p>
        @if ($interval === 'month' && ! $subscription->onGracePeriod())
          <form method="POST" action="/billing/yearly">@csrf<input type="hidden" name="plan" value="teams"><button class="btn gold sm" type="submit">Switch to yearly and save ${{ $p['teams_saving'] * $seats }} a year</button></form>
        @endif
        <div class="row">
          <a class="btn sm" href="/billing/payment-method?plan=teams">Update payment method</a>
          @if ($subscription->onGracePeriod())
            <form method="POST" action="/billing/resume">@csrf<input type="hidden" name="plan" value="teams"><button class="btn sm" type="submit">Keep the team plan</button></form>
          @else
            <details><summary class="btn sm danger" style="list-style:none">Cancel the team plan</summary>
              <form method="POST" action="/billing/cancel" style="margin-top:10px">@csrf<input type="hidden" name="plan" value="teams">
                <p class="muted" style="margin:0 0 10px;font-size:13.5px">Every member keeps access until the end of the paid period.</p>
                <button class="btn sm danger" type="submit">Cancel at the end of the period</button></form></details>
          @endif
        </div>
      @elseif ($team->hasOverride())
        <p class="muted" style="margin:0">Seats on this team are arranged with EMMA. To change them, write to <a href="mailto:{{ config('atlas.contact') }}">{{ config('atlas.contact') }}</a>.</p>
      @endif
    </section>
    <section class="card stack" aria-labelledby="nH">
      <h2 class="h3" id="nH">Team name</h2>
      <form class="row" method="POST" action="/team/name">@csrf<input type="text" name="name" value="{{ $team->name }}" required maxlength="120" style="flex:1;min-width:200px" aria-label="Team name"><button class="btn sm" type="submit">Save</button></form>
    </section>
    <section class="card stack">
      <h2 class="h3">Onboarding</h2>
      <p class="muted" style="margin:0">Book the team's onboarding session with an EMMA analyst: <a href="mailto:{{ config('atlas.contact') }}?subject=Onboarding%20for%20{{ rawurlencode($team->name) }}">{{ config('atlas.contact') }}</a>.</p>
    </section>
  </div>
  @endif
</div>
@endsection
