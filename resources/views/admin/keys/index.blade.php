@extends('layouts.site')
@section('title', 'API keys')
@section('noindex', '1')
@section('content')
<div class="stack" style="gap:8px"><span class="eyebrow">EMMA staff</span><h1 class="h2">API keys</h1></div>
@include('admin.nav')
@if (session('plain_key'))
  <div class="flash" style="word-break:break-all"><strong>API key:</strong> <code>{{ session('plain_key') }}</code></div>
@endif
<div class="grid2" style="margin-top:16px;align-items:start">
  <form class="card form" method="POST" action="/admin/api-keys">@csrf
    <h2 class="h3">Issue a key</h2>
    <label>Organization<input type="text" name="name" required></label>
    <label>Contact email<input type="email" name="contact_email"></label>
    <div class="row">
      <label style="flex:1">Requests a day<input type="number" name="daily_quota" value="{{ config('atlas.api.starter_quota') }}" required></label>
      <label style="flex:1">People<input type="number" name="max_users" value="{{ config('atlas.api.starter_users') }}" required></label>
    </div>
    <label>Expires on<input type="date" name="expires_on" value="{{ today()->addYear()->toDateString() }}"></label>
    <label>Notes<textarea name="notes" rows="2"></textarea></label>
    <button class="btn sm" type="submit">Issue</button>
  </form>
  <div class="tw"><table class="t">
    <thead><tr><th>Organization</th><th>Key</th><th>Today</th><th></th></tr></thead>
    <tbody>
    @forelse ($keys as $key)
      <tr>
        <td>{{ $key->name }}<br><span class="faint" style="font-size:12.5px">{{ $key->max_users }} people, until {{ $key->expires_on?->format('M j, Y') ?? 'open' }}</span></td>
        <td><code>{{ $key->prefix }}…</code></td>
        <td>{{ $key->usedToday() }} / {{ number_format($key->daily_quota) }}</td>
        <td><form method="POST" action="/admin/api-keys/{{ $key->id }}/toggle">@csrf<button class="btn sm" type="submit">{{ $key->active ? 'Switch off' : 'Switch on' }}</button></form></td>
      </tr>
    @empty
      <tr><td colspan="4" class="faint">None yet.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div>
@endsection
