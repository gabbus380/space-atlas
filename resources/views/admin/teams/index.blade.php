@extends('layouts.site')
@section('title', 'Teams')
@section('noindex', '1')
@section('content')
<div class="stack" style="gap:8px"><span class="eyebrow">EMMA staff</span><h1 class="h2">Teams</h1></div>
@include('admin.nav')
<div class="tw" style="margin-top:16px"><table class="t">
  <thead><tr><th>Team</th><th>Owner</th><th>Members</th><th>Status</th></tr></thead>
  <tbody>
  @forelse ($teams as $team)
    <tr>
      <td><a href="/admin/teams/{{ $team->id }}">{{ $team->name }}</a></td>
      <td>{{ $team->owner?->email }}</td>
      <td>{{ $team->members_count }} / {{ $team->seats() }}</td>
      <td>@if($team->isActive())<span class="pill green">active</span>@else<span class="pill">inactive</span>@endif</td>
    </tr>
  @empty
    <tr><td colspan="4" class="faint">None yet.</td></tr>
  @endforelse
  </tbody>
</table></div>
<div style="margin-top:16px">{{ $teams->links() }}</div>
@endsection
