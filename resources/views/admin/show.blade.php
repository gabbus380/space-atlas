@extends('layouts.site')
@section('title', 'Admin')
@section('noindex', '1')
@section('style')
.kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-top:24px}
.kpi{background:var(--panel);border:1px solid var(--line);border-radius:4px;padding:18px;display:flex;flex-direction:column;gap:8px}
.kpi b{font-family:var(--serif);font-weight:400;font-size:44px;line-height:1}
.bar{height:6px;background:var(--raise);border-radius:3px;overflow:hidden}.bar i{display:block;height:100%;background:var(--gold)}
.forms{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-top:20px}
.forms .form{max-width:none}
@media (max-width:980px){.kpis,.forms{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}}
@media (max-width:600px){.kpis,.forms{grid-template-columns:minmax(0,1fr)}}
@endsection
@section('content')
@php($pct = fn ($n, $t) => $t ? min(100, round(100 * $n / $t)) : ($n ? 100 : 0))
<div class="stack" style="gap:8px"><span class="eyebrow">EMMA only</span><h1 class="h2">Year-one scoreboard</h1></div>
@if (session('plain_key'))
  <div class="flash" style="word-break:break-all"><strong>API key:</strong> <code>{{ session('plain_key') }}</code></div>
@endif
<div class="kpis">
  <div class="kpi"><span class="eyebrow">Companies on Teams</span><b>{{ $kpi['teams']['paid'] }}</b><div class="bar"><i style="width:{{ $pct($kpi['teams']['paid'], $kpi['teams']['target'][0]) }}%"></i></div><span class="faint" style="font-size:13px">Target {{ implode(' to ', $kpi['teams']['target']) }} &middot; {{ $kpi['teams']['comp'] }} {{ \Illuminate\Support\Str::plural('pilot', $kpi['teams']['comp']) }} &middot; {{ $kpi['teams']['seats'] }} paid {{ \Illuminate\Support\Str::plural('seat', $kpi['teams']['seats']) }}</span></div>
  <div class="kpi"><span class="eyebrow">Personal readers</span><b>{{ $kpi['personal']['paid'] }}</b><div class="bar"><i style="width:{{ $pct($kpi['personal']['paid'], $kpi['personal']['target'][0]) }}%"></i></div><span class="faint" style="font-size:13px">Target {{ implode(' to ', $kpi['personal']['target']) }} &middot; {{ $kpi['personal']['year'] }} yearly, {{ $kpi['personal']['month'] }} monthly &middot; {{ $kpi['personal']['comp'] }} complimentary</span></div>
  <div class="kpi"><span class="eyebrow">Billed yearly</span><b>{{ $kpi['annual_share'] === null ? 'n/a' : $kpi['annual_share'].'%' }}</b><span class="faint" style="font-size:13px">Share of paid plans on yearly billing</span></div>
  <div class="kpi"><span class="eyebrow">Annual run rate</span><b>${{ number_format($kpi['arr']) }}</b><span class="faint" style="font-size:13px">{{ $kpi['api']['keys'] }} API keys &middot; {{ $kpi['users'] }} accounts, {{ $kpi['verified'] }} verified, {{ $kpi['signups_30d'] }} new in 30 days &middot; {{ $kpi['marketing_opt_in'] }} opted in to news</span></div>
</div>

<div class="forms">
  <form class="card form" method="POST" action="/admin/grant">@csrf
    <h2 class="h3">Complimentary Personal</h2>
    <label>Account email<input type="email" name="email" required></label>
    <label>Days<input type="number" name="days" value="30" min="1" max="730" required></label>
    <button class="btn sm" type="submit">Grant</button></form>
  <form class="card form" method="POST" action="/admin/team-grant">@csrf
    <h2 class="h3">Team pilot or invoiced team</h2>
    <label>Owner email<input type="email" name="email" required></label>
    <label>Team name<input type="text" name="name" required></label>
    <div class="row"><label style="flex:1">Seats<input type="number" name="seats" value="5" min="1" required></label><label style="flex:1">Days<input type="number" name="days" value="30" min="1" required></label></div>
    <button class="btn sm" type="submit">Grant</button></form>
  <form class="card form" method="POST" action="/admin/api-keys">@csrf
    <h2 class="h3">Issue an API key</h2>
    <label>Organization<input type="text" name="name" required></label>
    <label>Contact email<input type="email" name="contact_email"></label>
    <div class="row"><label style="flex:1">Requests a day<input type="number" name="daily_quota" value="{{ config('atlas.api.starter_quota') }}" required></label><label style="flex:1">People<input type="number" name="max_users" value="{{ config('atlas.api.starter_users') }}" required></label></div>
    <label>Expires on<input type="date" name="expires_on" value="{{ today()->addYear()->toDateString() }}"></label>
    <button class="btn sm" type="submit">Issue</button></form>
</div>
<form class="row" method="POST" action="/admin/revoke" style="margin-top:14px">@csrf<input type="email" name="email" placeholder="Remove complimentary access for this email" required style="max-width:420px"><button class="btn sm" type="submit">Remove</button></form>

<div class="grid2" style="margin-top:28px;align-items:start">
  <section class="card stack"><h2 class="h3">Recent accounts</h2>
    <div class="tw"><table class="t"><thead><tr><th>Name</th><th>Organization</th><th>Plan</th><th>Joined</th></tr></thead><tbody>
    @foreach ($users as $r)<tr><td>{{ $r['u']->name }}<br><span class="faint" style="font-size:12.5px">{{ $r['u']->email }}</span></td><td>{{ $r['u']->organization }}</td><td><span class="pill {{ $r['tier'] === 'free' ? '' : 'gold' }}">{{ $r['tier'] }}</span></td><td>{{ $r['u']->created_at->format('M j') }}</td></tr>@endforeach
    </tbody></table></div></section>
  <div class="stack">
    <section class="card stack"><h2 class="h3">Country files opened by free accounts this month</h2>
      @forelse ($kpi['top_unlocks'] as $u)<div class="row" style="justify-content:space-between"><span>{{ $u->iso3 }}</span><span class="faint">{{ $u->n }}</span></div>@empty<p class="faint" style="margin:0">None yet.</p>@endforelse
      <p class="faint" style="margin:0;font-size:12.5px">Where free readers spend their allowance shows which country files sell the plan.</p></section>
    <section class="card stack"><h2 class="h3">Teams</h2>
      <div class="tw"><table class="t"><thead><tr><th>Team</th><th>Owner</th><th>Members</th><th>Status</th></tr></thead><tbody>
      @forelse ($teams as $t)<tr><td>{{ $t->name }}</td><td>{{ $t->owner?->email }}</td><td>{{ $t->members_count }} / {{ $t->seats() }}</td><td>{!! $t->isActive() ? '<span class="pill green">active</span>' : '<span class="pill">inactive</span>' !!}</td></tr>@empty<tr><td colspan="4" class="faint">None yet.</td></tr>@endforelse
      </tbody></table></div></section>
    <section class="card stack"><h2 class="h3">API keys</h2>
      <div class="tw"><table class="t"><thead><tr><th>Organization</th><th>Key</th><th>Today</th><th></th></tr></thead><tbody>
      @forelse ($keys as $k)<tr><td>{{ $k->name }}<br><span class="faint" style="font-size:12.5px">{{ $k->max_users }} people, until {{ $k->expires_on?->format('M j, Y') ?? 'open' }}</span></td><td><code>{{ $k->prefix }}…</code></td><td>{{ $k->usedToday() }} / {{ number_format($k->daily_quota) }}</td>
        <td><form method="POST" action="/admin/api-keys/{{ $k->id }}/toggle">@csrf<button class="btn sm" type="submit">{{ $k->active ? 'Switch off' : 'Switch on' }}</button></form></td></tr>@empty<tr><td colspan="4" class="faint">None yet.</td></tr>@endforelse
      </tbody></table></div></section>
  </div>
</div>
@endsection
