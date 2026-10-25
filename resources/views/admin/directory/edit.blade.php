@extends('layouts.site')
@section('title', $org['name'])
@section('noindex', '1')
@section('content')
<div class="stack" style="gap:8px"><span class="eyebrow">{{ $org['iso3'] ?? 'Organization' }}</span><h1 class="h2">{{ $org['name'] }}</h1></div>
@include('admin.nav')
<div class="grid2" style="margin-top:16px;align-items:start">
  <form class="card form" method="POST" action="/admin/directory/{{ rawurlencode($org['id']) }}">@csrf
    <label>Name<input type="text" name="name" value="{{ $org['name'] }}" required></label>
    <label>Description<textarea name="desc" rows="6">{{ $org['desc'] ?? '' }}</textarea></label>
    <label>Company email domains <span class="faint">comma separated, for example esa.int, nasa.gov</span>
      <input type="text" name="email_domains" value="{{ implode(', ', $org['email_domains'] ?? []) }}">
    </label>
    <button class="btn sm" type="submit">Save</button>
  </form>
  <section class="card stack">
    <h2 class="h3">People linked to this organization</h2>
    @forelse ($people as $person)
      <div class="row" style="justify-content:space-between">
        <span><a href="/admin/users/{{ $person->id }}">{{ $person->name }}</a>@if($person->job_title) <span class="faint">{{ $person->job_title }}</span>@endif</span>
        @if($person->show_on_organization)<span class="pill green">visible</span>@else<span class="pill">hidden</span>@endif
      </div>
    @empty
      <p class="faint" style="margin:0">Nobody has claimed this organization yet.</p>
    @endforelse
  </section>
</div>
@endsection
