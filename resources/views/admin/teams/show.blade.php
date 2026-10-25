@extends('layouts.site')
@section('title', $team->name)
@section('noindex', '1')
@section('content')
<div class="stack" style="gap:8px"><span class="eyebrow">Team</span><h1 class="h2">{{ $team->name }}</h1>
  <p class="muted" style="margin:0">Owner {{ $team->owner?->email ?: 'unknown' }} · {{ $team->members->count() }} / {{ $team->seats() }} seats</p>
</div>
@include('admin.nav')
<div class="grid2" style="margin-top:20px;align-items:start">
  <section class="card stack">
    <h2 class="h3">Members</h2>
    <div class="tw"><table class="t">
      <thead><tr><th>Name</th><th>Email</th><th>Title</th></tr></thead>
      <tbody>
      @foreach ($team->members as $member)
        <tr>
          <td><a href="/admin/users/{{ $member->id }}">{{ $member->name }}</a></td>
          <td>{{ $member->email }}</td>
          <td>{{ $member->job_title }}</td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
  </section>
  <div class="stack">
    <form class="card form" method="POST" action="/admin/teams/{{ $team->id }}/grant">@csrf
      <h2 class="h3">Grant complimentary seats</h2>
      <label>Seats<input type="number" name="seats" value="{{ $team->override_seats ?: 3 }}" min="1" required></label>
      <label>Days<input type="number" name="days" value="30" min="1" required></label>
      <button class="btn sm" type="submit">Grant</button>
    </form>
    <form method="POST" action="/admin/teams/{{ $team->id }}/revoke">@csrf<button class="btn sm" type="submit">Revoke complimentary seats</button></form>
  </div>
</div>
@endsection
