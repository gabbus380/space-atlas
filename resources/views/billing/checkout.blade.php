@php($p = \App\Support\Plans::prices())
@php($label = $plan === 'teams' ? 'Teams' : 'Personal')
@php($unit = $plan === 'teams' ? ($interval === 'year' ? $p['teams_year'] : $p['teams_month']) : ($interval === 'year' ? $p['personal_year'] : $p['personal_month']))
@php($total = $unit * $seats)
@extends('layouts.site')
@section('title', 'Checkout')
@section('noindex', '1')
@push('head')
@if ($checkout) @paddleJS @endif
@endpush
@section('style')
.trust,.trust *{border-radius:0}
.trust{display:flex;flex-direction:column;gap:28px}
.seller{display:flex;align-items:center;gap:16px}
.seller img{width:48px;height:48px;display:block}
.seller strong{display:block;font-family:var(--serif);font-size:22px;font-weight:400;color:var(--ink)}
.seller span{color:var(--ink-2);font-size:13.5px}
.sheets{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(280px,.85fr);gap:18px;align-items:start}
.sheet,.merchant{background:var(--panel);border:1px solid var(--line);padding:28px}
.sheet h1{font-family:var(--serif);font-weight:400;font-size:40px;line-height:1.05;margin:8px 0 0}
.price-line{display:flex;align-items:baseline;gap:10px;margin:18px 0 8px}
.price-line b{font-family:var(--serif);font-weight:400;font-size:56px;line-height:1}
.facts{display:grid;gap:12px;margin:18px 0 0;padding:0}
.facts div{display:grid;grid-template-columns:140px minmax(0,1fr);gap:12px;padding-top:12px;border-top:1px solid var(--line);font-size:14.5px;line-height:1.5}
.facts dt{color:var(--ink-3);font-family:var(--mono);font-size:11px;letter-spacing:.12em;text-transform:uppercase}
.facts dd{margin:0;color:var(--ink)}
.includes{list-style:none;margin:18px 0 0;padding:16px 0 0;border-top:1px solid var(--line);display:flex;flex-direction:column;gap:8px;color:var(--ink-2);font-size:14.5px}
.includes li{display:grid;grid-template-columns:14px minmax(0,1fr);gap:8px}
.includes li::before{content:"";width:10px;height:6px;border-left:1.6px solid var(--gold);border-bottom:1.6px solid var(--gold);transform:rotate(-45deg);margin-top:6px}
.merchant{display:flex;flex-direction:column;gap:14px}
.logo-well{background:#f6f3ec;padding:22px 18px;display:flex;align-items:center;justify-content:center}
.logo-well img{width:168px;height:auto;display:block}
.merchant ul{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;color:var(--ink-2);font-size:14.5px;line-height:1.45}
.merchant li{padding-left:14px;border-left:2px solid var(--gold)}
.fine{margin:0;color:var(--ink-3);font-size:12.5px;line-height:1.6}
.trust .btn{border-radius:0}
@media (max-width:800px){.sheets{grid-template-columns:minmax(0,1fr)}.price-line b{font-size:44px}.facts div{grid-template-columns:minmax(0,1fr)}}
@endsection
@section('content')
<div class="trust">
  <div class="seller">
    <img src="/apple-touch-icon.png" width="48" height="48" alt="EMMA Aerospace Consulting">
    <div>
      <p class="eyebrow" style="margin:0">Sovereign Space ATLAS</p>
      <strong>EMMA Aerospace Consulting LLP</strong>
      <span>LL03251 · 1103-11871 Horseshoe Way, Richmond, BC V7A 5H5, Canada</span>
    </div>
  </div>
  <div class="sheets">
    <article class="sheet">
      <p class="eyebrow" style="margin:0">Order</p>
      <h1>{{ $label }}</h1>
      <div class="price-line"><b>${{ number_format($total) }}</b><span class="muted">{{ $interval === 'year' ? 'a year' : 'a month' }}@if($plan === 'teams'), ${{ $unit }} per user @endif</span></div>
      <p class="muted" style="margin:0">Plus any VAT or sales tax Paddle calculates for your country. @if($interval === 'month')<a href="{{ $plan === 'teams' ? '/subscribe/teams?interval=year&seats='.$seats : '/subscribe/personal?interval=year' }}">Switch to yearly</a> and pay ${{ number_format($plan === 'teams' ? $p['teams_year'] * $seats : $p['personal_year']) }} a year. That saves ${{ number_format(($plan === 'teams' ? $p['teams_saving'] : $p['personal_saving']) * $seats) }}.@endif</p>
      <dl class="facts">
        @if ($team)<div><dt>Team</dt><dd>{{ $team->name }}</dd></div>@endif
        @if ($plan === 'teams')<div><dt>Seats</dt><dd>{{ $seats }}, including yours</dd></div>@endif
        <div><dt>Renewal</dt><dd>Renews {{ $interval === 'year' ? 'every year' : 'every month' }} until you cancel. Cancel any time from your account. Access runs to the end of the period you paid for.</dd></div>
        <div><dt>Guarantee</dt><dd>Full refund on request within {{ config('atlas.refund_days') }} days of the first payment.</dd></div>
      </dl>
      <ul class="includes">
        <li>All country files, not only the 18 leading programs</li>
        <li>Compare, with GDP, defense, R&amp;D, ESA CM25, satellites and flight-ready astronauts</li>
        <li>The full daily briefing, narration and a 21-day archive</li>
        <li>Programs, the ecosystem registry and tracker entries</li>
        @if ($plan === 'teams')<li>Shared watchlist, team notes and CSV export</li>@else<li>Your watchlist saved to this account</li>@endif
      </ul>
    </article>
    <aside class="merchant">
      <div class="logo-well">
        <img src="/brand/paddle.svg" width="168" height="49" alt="Paddle">
      </div>
      <p class="eyebrow" style="margin:0">Merchant of record</p>
      <h2 class="h3">Paddle.com takes the payment</h2>
      <ul>
        <li>Card, PayPal and local methods. Tax is added for your country.</li>
        <li>Paddle.com is the seller on your statement and sends the invoice.</li>
        <li>Refunds and payment questions go to Paddle.</li>
      </ul>
      @if ($checkout)
        <x-paddle-button :checkout="$checkout" class="btn gold" data-theme="dark">Continue to secure checkout</x-paddle-button>
      @else
        <div class="errors">Online checkout is not switched on yet. Write to <a href="mailto:{{ config('atlas.contact') }}">{{ config('atlas.contact') }}</a>.</div>
      @endif
      <p class="fine">Our order process is conducted by our online reseller Paddle.com. Paddle.com is the merchant of record for all our orders. Paddle provides all customer service inquiries and handles returns. By paying you accept the <a href="/legal#terms">Terms of Service</a>. Consumers in the EU, the EEA and the UK ask for the service to start now, and keep the right to withdraw within {{ config('atlas.refund_days') }} days. <a href="/pricing">Back to plans</a></p>
    </aside>
  </div>
</div>
@endsection
