@php($p = \App\Support\Plans::prices())
@extends('layouts.site')
@section('title', 'Start Teams')
@section('noindex', '1')
@section('content')
<div class="grid2" style="align-items:start">
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
  <aside class="card stack">
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
