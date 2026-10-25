@extends('layouts.site')
@section('title', $apiRequest->organization)
@section('noindex', '1')
@section('content')
<div class="stack" style="gap:8px"><span class="eyebrow">API request</span><h1 class="h2">{{ $apiRequest->organization }}</h1>
  <p class="muted" style="margin:0">{{ $apiRequest->name }} · {{ $apiRequest->email }} · {{ ucfirst($apiRequest->account_tier) }} account@if($apiRequest->created_account), created with this request@endif</p>
</div>
@include('admin.nav')
@if (session('plain_key'))
  <div class="flash" style="word-break:break-all"><strong>API key:</strong> <code>{{ session('plain_key') }}</code></div>
@endif
<div class="grid2" style="margin-top:20px;align-items:start">
  <section class="card stack">
    <dl class="kv">
      <dt>Title</dt><dd>{{ $apiRequest->job_title ?: '—' }}</dd>
      <dt>People</dt><dd>{{ $apiRequest->people ?: '—' }}</dd>
      <dt>Received</dt><dd>{{ $apiRequest->created_at->format('F j, Y') }}</dd>
      <dt>Account</dt><dd>@if($apiRequest->user)<a href="/admin/users/{{ $apiRequest->user_id }}">{{ $apiRequest->user->email }}</a>@else — @endif</dd>
      <dt>Key</dt><dd>@if($apiRequest->apiKey)<code>{{ $apiRequest->apiKey->prefix }}…</code>@else Not issued @endif</dd>
    </dl>
    <h2 class="h3">How they will use it</h2>
    <p style="margin:0;white-space:pre-wrap">{{ $apiRequest->message }}</p>
  </section>
  <div class="stack">
    <form class="card form" method="POST" action="/admin/api-requests/{{ $apiRequest->id }}">@csrf
      <h2 class="h3">Manage</h2>
      <label>Status
        <select name="status">
          @foreach (\App\Models\ApiRequest::STATUSES as $value => $label)
            <option value="{{ $value }}" @selected($apiRequest->status === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </label>
      <label>Note<textarea name="staff_note" rows="3">{{ $apiRequest->staff_note }}</textarea></label>
      <button class="btn sm" type="submit">Save</button>
    </form>
    @if ($canIssue)
      <form class="card form" method="POST" action="/admin/api-requests/{{ $apiRequest->id }}/key">@csrf
        <h2 class="h3">Issue the key</h2>
        <p class="faint" style="margin:0">For {{ $apiRequest->organization }}, contact {{ $apiRequest->email }}.</p>
        <label>Requests a day<input type="number" name="daily_quota" value="{{ config('atlas.api.starter_quota') }}" required></label>
        <label>People<input type="number" name="max_users" value="{{ $apiRequest->people ?: config('atlas.api.starter_users') }}" required></label>
        <label>Expires on<input type="date" name="expires_on" value="{{ today()->addYear()->toDateString() }}"></label>
        <button class="btn sm" type="submit">Issue key</button>
      </form>
    @elseif ($apiRequest->apiKey && \App\Support\Staff::allows(auth()->user(), 'keys'))
      <form method="POST" action="/admin/api-keys/{{ $apiRequest->api_key_id }}/refresh">@csrf
        <button class="btn sm" type="submit">Refresh key</button>
      </form>
    @endif
  </div>
</div>
@endsection
