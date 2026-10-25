@php($p = \App\Support\Plans::prices())
@php($label = $plan === 'teams' ? 'Teams' : 'Personal')
@php($unit = $plan === 'teams' ? ($interval === 'year' ? $p['teams_year'] : $p['teams_month']) : ($interval === 'year' ? $p['personal_year'] : $p['personal_month']))
@extends('layouts.site')
@section('title', 'Checkout')
@section('noindex', '1')
@push('head')
@if ($checkout) @paddleJS @endif
@endpush
@section('content')
<div class="grid2" style="align-items:start">
  <div class="stack">
    <span class="eyebrow">Checkout</span>
    <h1 class="h2">{{ $label }}, billed {{ $interval === 'year' ? 'yearly' : 'monthly' }}</h1>
    <dl class="kv">
      @if ($team)<dt>Team</dt><dd>{{ $team->name }}</dd>@endif
      @if ($plan === 'teams')<dt>Seats</dt><dd>{{ $seats }}</dd>@endif
      <dt>Price</dt><dd>${{ number_format($unit * $seats) }} {{ $interval === 'year' ? 'a year' : 'a month' }}@if($plan === 'teams') (${{ $unit }} per user)@endif, plus any VAT or sales tax</dd>
      <dt>Renewal</dt><dd>Renews {{ $interval === 'year' ? 'every year' : 'every month' }} until you cancel. Cancel any time from your account.</dd>
      <dt>Guarantee</dt><dd>Full refund on request within {{ config('atlas.refund_days') }} days of your first payment.</dd>
    </dl>
    @if ($interval === 'month')
      <p class="muted" style="margin:0">Yearly billing costs ${{ $plan === 'teams' ? $p['teams_year'] * $seats : $p['personal_year'] }} a year and saves ${{ ($plan === 'teams' ? $p['teams_saving'] : $p['personal_saving']) * $seats }}. <a href="{{ $plan === 'teams' ? '/subscribe/teams?interval=year&seats='.$seats : '/subscribe/personal?interval=year' }}">Switch to yearly</a></p>
    @endif
    @if ($checkout)
      <div class="row">
        <x-paddle-button :checkout="$checkout" class="btn gold" data-theme="dark">Continue to secure checkout</x-paddle-button>
        <a class="btn link" href="/pricing">Back to plans</a>
      </div>
      <p class="faint" style="margin:0;font-size:12.5px;line-height:1.6">Our order process is conducted by our online reseller Paddle.com. Paddle.com is the merchant of record for all our orders. Paddle provides all customer service inquiries and handles returns. By paying you accept the <a href="/legal#terms">Terms of Service</a>. Consumers in the EU, the EEA and the UK ask for the service to start now, and keep the right to withdraw within 14 days.</p>
    @else
      <div class="errors">Online checkout is not switched on yet. Write to <a href="mailto:{{ config('atlas.contact') }}">{{ config('atlas.contact') }}</a> and we set up your plan.</div>
    @endif
  </div>
  <aside class="card stack">
    <h2 class="h3">Included</h2>
    <ul class="muted" style="margin:0;padding-left:18px;display:flex;flex-direction:column;gap:6px">
      <li>All 81 country files</li><li>The full daily briefing, narration and map playback</li><li>21-day briefing archive</li>
      <li>Programs tracker, ecosystem registry and company engagements</li>
      @if ($plan === 'teams')<li>Shared watchlist, team notes and CSV export</li><li>Onboarding session with an EMMA analyst</li>@else<li>Your watchlist on your account</li>@endif
    </ul>
    @if (\Illuminate\Support\Carbon::parse(config('atlas.founding_until'))->endOfDay()->isFuture())
      <p class="pill gold" style="align-self:flex-start;text-transform:none;letter-spacing:0;font-family:var(--font);font-size:13px">Founding rate: this price stays while you stay subscribed</p>
    @endif
  </aside>
</div>
@endsection
