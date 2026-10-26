@extends('layouts.site')
@section('title', 'Admin')
@section('noindex', '1')
@section('style')
.kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-top:24px}
.kpi{background:var(--panel);border:1px solid var(--line);padding:18px;display:flex;flex-direction:column;gap:8px}
.kpi b{font-family:var(--serif);font-weight:400;font-size:44px;line-height:1}
.bar{height:6px;background:var(--raise);overflow:hidden}.bar i{display:block;height:100%;background:var(--gold)}
.forms{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-top:20px}
.forms .form{max-width:none}
@media (max-width:980px){.kpis,.forms{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}}
@media (max-width:600px){.kpis,.forms{grid-template-columns:minmax(0,1fr)}}
@endsection
@section('content')
@php($pct = fn ($n, $t) => $t ? min(100, round(100 * $n / $t)) : ($n ? 100 : 0))
<div class="stack" style="gap:8px"><span class="eyebrow">EMMA staff</span><h1 class="h2">Year-one scoreboard</h1></div>
@include('admin.nav')
<div class="kpis">
  <div class="kpi"><span class="eyebrow">Companies on Teams</span><b>{{ $kpi['teams']['paid'] }}</b><div class="bar"><i style="width:{{ $pct($kpi['teams']['paid'], $kpi['teams']['target'][0]) }}%"></i></div><span class="faint" style="font-size:13px">Target {{ implode(' to ', $kpi['teams']['target']) }} &middot; {{ $kpi['teams']['comp'] }} {{ \Illuminate\Support\Str::plural('pilot', $kpi['teams']['comp']) }} &middot; {{ $kpi['teams']['seats'] }} paid {{ \Illuminate\Support\Str::plural('seat', $kpi['teams']['seats']) }}</span></div>
  <div class="kpi"><span class="eyebrow">Personal readers</span><b>{{ $kpi['personal']['paid'] }}</b><div class="bar"><i style="width:{{ $pct($kpi['personal']['paid'], $kpi['personal']['target'][0]) }}%"></i></div><span class="faint" style="font-size:13px">Target {{ implode(' to ', $kpi['personal']['target']) }} &middot; {{ $kpi['personal']['year'] }} yearly, {{ $kpi['personal']['month'] }} monthly &middot; {{ $kpi['personal']['comp'] }} complimentary</span></div>
  <div class="kpi"><span class="eyebrow">Billed yearly</span><b>{{ $kpi['annual_share'] === null ? 'n/a' : $kpi['annual_share'].'%' }}</b><span class="faint" style="font-size:13px">Share of paid plans on yearly billing</span></div>
  <div class="kpi"><span class="eyebrow">Annual run rate</span><b>${{ number_format($kpi['arr']) }}</b><span class="faint" style="font-size:13px">{{ $kpi['api']['keys'] }} API keys &middot; {{ $kpi['users'] }} accounts, {{ $kpi['verified'] }} verified, {{ $kpi['signups_30d'] }} new in 30 days &middot; {{ $kpi['marketing_opt_in'] }} opted in to news</span></div>
</div>

<div class="grid2" style="margin-top:28px;align-items:start">
  <section class="card stack"><h2 class="h3">Where to work</h2>
    <p class="muted" style="margin:0">Users, teams, API requests, withdrawals and coupons are for admin, manager and sales. API keys are for admin and manager. The directory is for admin, manager and data analysts. Paddle and AI connectors are admin only.</p>
  </section>
  <div class="stack">
    <section class="card stack"><h2 class="h3">Country files opened by free accounts this month</h2>
      @forelse ($kpi['top_unlocks'] as $u)<div class="row" style="justify-content:space-between"><span>{{ $u->iso3 }}</span><span class="faint">{{ $u->n }}</span></div>@empty<p class="faint" style="margin:0">None yet.</p>@endforelse
      <p class="faint" style="margin:0;font-size:12.5px">Where free readers spend their allowance shows which country files sell the plan.</p></section>
  </div>
</div>
@endsection
