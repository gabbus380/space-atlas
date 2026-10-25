@extends('layouts.site')
@section('title', 'Directory')
@section('noindex', '1')
@section('content')
<div class="stack" style="gap:8px"><span class="eyebrow">Directory</span><h1 class="h2">Organizations</h1>
  <p class="muted" style="margin:0">Edits sit on top of the atlas directory. Add the company's email domains so a reader can only claim it with a matching address.</p>
</div>
@include('admin.nav')
<form class="row" method="GET" action="/admin/directory" style="margin:16px 0">
  <input type="search" name="q" value="{{ $q }}" placeholder="Organization name" aria-label="Search organizations" style="max-width:420px">
  <button class="btn sm" type="submit">Search</button>
</form>
<div class="tw"><table class="t">
  <thead><tr><th>Name</th><th>Country</th><th>Email domains</th></tr></thead>
  <tbody>
  @forelse ($orgs as $org)
    <tr>
      <td><a href="/admin/directory/{{ rawurlencode($org['id']) }}">{{ $org['name'] }}</a></td>
      <td>{{ $org['iso3'] ?? '' }}</td>
      <td>{{ implode(', ', $org['email_domains'] ?? []) }}</td>
    </tr>
  @empty
    <tr><td colspan="3" class="faint">Nothing matched.</td></tr>
  @endforelse
  </tbody>
</table></div>
@endsection
