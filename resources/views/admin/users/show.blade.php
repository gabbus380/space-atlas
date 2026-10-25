@extends('layouts.site')
@section('title', $user->name)
@section('noindex', '1')
@section('content')
<div class="stack" style="gap:8px"><span class="eyebrow">User</span><h1 class="h2">{{ $user->name }}</h1>
  <p class="muted" style="margin:0">{{ $user->email }} @if($user->email_verified_at)<span class="pill green">confirmed</span>@else<span class="pill">unconfirmed</span>@endif</p>
</div>
@include('admin.nav')
<div class="grid2" style="margin-top:20px;align-items:start">
  <section class="card stack">
    <h2 class="h3">Profile</h2>
    <dl class="kv">
      <dt>Plan</dt><dd>{{ $tier }}</dd>
      <dt>Organization</dt><dd>{{ $user->organization ?: '—' }}@if($user->organization_id) <span class="faint">(linked)</span>@endif</dd>
      <dt>Title</dt><dd>{{ $user->job_title ?: '—' }}</dd>
      <dt>Shown on organization</dt><dd>{{ $user->show_on_organization ? 'Yes' : 'No' }}</dd>
      <dt>Team</dt><dd>@if($user->team)<a href="/admin/teams/{{ $user->team_id }}">{{ $user->team->name }}</a>@else — @endif</dd>
      <dt>Complimentary until</dt><dd>{{ $user->override_until?->format('F j, Y') ?: '—' }}</dd>
    </dl>
  </section>
  <div class="stack">
    <form class="card form" method="POST" action="/admin/users/{{ $user->id }}/grant">@csrf
      <h2 class="h3">Grant Personal</h2>
      <label>Days<input type="number" name="days" value="30" min="1" max="730" required></label>
      <button class="btn sm" type="submit">Grant</button>
    </form>
    <form method="POST" action="/admin/users/{{ $user->id }}/revoke">@csrf<button class="btn sm" type="submit">Revoke complimentary access</button></form>
    @unless($user->email_verified_at)
      <form method="POST" action="/admin/users/{{ $user->id }}/verify">@csrf<button class="btn sm" type="submit">Confirm email</button></form>
    @endunless
    @if($canRoles)
      <form class="card form" method="POST" action="/admin/users/{{ $user->id }}/role">@csrf
        <h2 class="h3">Staff role</h2>
        <label>Role
          <select name="staff_role">
            <option value="">Regular account</option>
            @foreach (\App\Support\Staff::ROLES as $value => $label)
              <option value="{{ $value }}" @selected($user->staffRole() === $value)>{{ $label }}</option>
            @endforeach
          </select>
        </label>
        <button class="btn sm" type="submit">Save role</button>
      </form>
    @endif
    <form method="POST" action="/admin/users/{{ $user->id }}" onsubmit="return confirm('Delete this account?')">@csrf @method('DELETE')
      <button class="btn sm danger" type="submit">Delete account</button>
    </form>
  </div>
</div>
@endsection
