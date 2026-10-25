@extends('layouts.site')
@section('title', 'Users')
@section('noindex', '1')
@section('content')
<div class="stack" style="gap:8px"><span class="eyebrow">EMMA staff</span><h1 class="h2">Users</h1></div>
@include('admin.nav')
<form class="row" method="GET" action="/admin/users" style="margin:16px 0">
  <input type="search" name="q" value="{{ $q }}" placeholder="Name, email, or organization" aria-label="Search users" style="max-width:420px">
  <button class="btn sm" type="submit">Search</button>
</form>
<div class="tw"><table class="t">
  <thead><tr><th>Name</th><th>Organization</th><th>Plan</th><th>Staff</th><th>Joined</th></tr></thead>
  <tbody>
  @foreach ($users as $user)
    <tr>
      <td><a href="/admin/users/{{ $user->id }}">{{ $user->name }}</a><br><span class="faint" style="font-size:12.5px">{{ $user->email }}</span></td>
      <td>{{ $user->organization }}@if($user->job_title)<br><span class="faint" style="font-size:12.5px">{{ $user->job_title }}</span>@endif</td>
      <td><span class="pill {{ ($tiers[$user->id] ?? 'free') === 'free' ? '' : 'gold' }}">{{ $tiers[$user->id] ?? 'free' }}</span></td>
      <td>{{ $user->staffRole() ? \App\Support\Staff::ROLES[$user->staffRole()] : '' }}</td>
      <td>{{ $user->created_at->format('M j, Y') }}</td>
    </tr>
  @endforeach
  </tbody>
</table></div>
<div style="margin-top:16px">{{ $users->links() }}</div>
@endsection
