@php($p = \App\Support\Plans::prices())
@extends('layouts.site')
@section('title', 'Start Teams')
@section('noindex', '1')
@section('style')
.trust,.trust *{border-radius:0}
.sheets{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(260px,.9fr);gap:18px;align-items:start}
.sheet{background:var(--panel);border:1px solid var(--line);padding:28px}
.logo-well{background:#f6f3ec;padding:16px;display:flex;margin-bottom:8px}
.logo-well img{width:140px;height:auto}
.seller{display:flex;align-items:center;gap:14px;margin-bottom:8px}
.seller img{width:44px;height:44px}
.seller strong{display:block;font-family:var(--serif);font-size:20px;font-weight:400}
.seller span,.fine{color:var(--ink-2);font-size:13.5px;line-height:1.5}
@endsection
@section('content')
<div class="trust grid2" style="align-items:start">
  <div class="stack">
    <span class="eyebrow">Teams</span>
    <h1 class="h2">Set up your team</h1>
    <p class="lead">You own the team and pay for its seats. Your own seat is one of them. After checkout you invite colleagues by email from the team page.</p>
    @if ($memberOf)
      <div class="errors">You are a member of {{ $memberOf->name }}. Leave that team in your account before you start your own.</div>
    @endif
    <form class="form" method="POST" action="/subscribe/teams" id="tf">
      @csrf
      <label>Team or organization name<input type="text" name="name" value="{{ old('name', $name) }}" required maxlength="120"></label>
      <label>Seats <span class="faint">(at least {{ $p['teams_min'] }}, including you)</span><input type="number" name="seats" id="seats" value="{{ old('seats', $seats) }}" min="{{ $p['teams_min'] }}" max="{{ config('atlas.teams_max_seats') }}" required></label>
      <fieldset style="border:0;padding:0;margin:0;display:flex;flex-direction:column;gap:8px">
        <legend class="muted" style="font-size:13px;margin-bottom:6px">Billing</legend>
        <label class="check"><input type="radio" name="interval" value="year" @checked(old('interval', $interval) === 'year')> <span><strong style="color:var(--ink)">Yearly</strong>, ${{ $p['teams_year'] }} per user a year. Saves ${{ $p['teams_saving'] }} per user.</span></label>
        <label class="check"><input type="radio" name="interval" value="month" @checked(old('interval', $interval) === 'month')> <span><strong style="color:var(--ink)">Monthly</strong>, ${{ $p['teams_month'] }} per user a month.</span></label>
      </fieldset>
      <p class="muted" id="sum" style="margin:0"></p>
      <div class="row"><button class="btn gold" type="submit" @disabled($memberOf)>Continue to checkout</button><a class="btn link" href="/pricing">Back to plans</a></div>
    </form>
  </div>
  <aside class="sheet stack">
    <div class="seller">
      <img src="/apple-touch-icon.png" width="44" height="44" alt="EMMA Aerospace Consulting">
      <div>
        <strong>EMMA Aerospace Consulting LLP</strong>
        <span>LL03251 · Richmond, BC</span>
      </div>
    </div>
    <div class="logo-well"><img src="/brand/paddle.svg" width="140" height="41" alt="Paddle"></div>
    <p class="eyebrow" style="margin:0">Merchant of record</p>
    <h2 class="h3">What the team gets</h2>
    <ul class="muted" style="margin:0;padding-left:18px;display:flex;flex-direction:column;gap:6px">
      <li>Everything in Personal for every seat</li>
      <li>A shared team watchlist next to each member's own</li>
      <li>Country notes the team writes once and everyone sees</li>
      <li>CSV export from Compare</li>
      <li>One invoice, seat changes prorated</li>
      <li>An onboarding session with an EMMA analyst</li>
    </ul>
    <p class="faint" style="margin:0;font-size:13px">Need an invoice instead of a card, or a 30-day pilot first? Write to <a href="mailto:{{ config('atlas.contact') }}">{{ config('atlas.contact') }}</a>.</p>
  </aside>
</div>
@endsection
@push('scripts')
<script>
(function () {
  var f = document.getElementById("tf"), s = document.getElementById("sum"), Y = {{ $p['teams_year'] }}, M = {{ $p['teams_month'] }}, MIN = {{ $p['teams_min'] }};
  function upd() {
    var n = Math.max(MIN, parseInt(f.seats.value, 10) || MIN), y = f.querySelector("input[name=interval]:checked").value === "year";
    s.textContent = n + " seats, " + (y ? "$" + (n * Y).toLocaleString("en-US") + " a year" : "$" + (n * M).toLocaleString("en-US") + " a month") + ", plus any VAT or sales tax.";
  }
  f.addEventListener("input", upd); upd();
})();
</script>
@endpush
