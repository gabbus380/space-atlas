@extends('layouts.site')
@section('title', 'API requests')
@section('noindex', '1')
@section('content')
<div class="stack" style="gap:8px"><span class="eyebrow">EMMA staff</span><h1 class="h2">API requests</h1>
  <p class="muted" style="margin:0">Order Forms submitted from the pricing page. A new visitor is registered as a free account. A signed-in reader keeps their Personal or Teams account.</p>
</div>
@include('admin.nav')
<form class="row" method="GET" action="/admin/api-requests" style="margin:16px 0">
  <label>Status
    <select name="status" onchange="this.form.submit()">
      <option value="">All</option>
      @foreach (\App\Models\ApiRequest::STATUSES as $value => $label)
        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
      @endforeach
    </select>
  </label>
</form>
<div class="tw"><table class="t">
  <thead><tr><th>Organization</th><th>Account</th><th>Status</th><th>Received</th></tr></thead>
  <tbody>
  @forelse ($requests as $item)
    <tr>
      <td><a href="/admin/api-requests/{{ $item->id }}">{{ $item->organization }}</a><br><span class="faint" style="font-size:12.5px">{{ $item->email }}</span></td>
      <td>{{ ucfirst($item->account_tier) }}@if($item->created_account) <span class="faint">new free account</span>@endif</td>
      <td><span class="pill {{ $item->status === 'approved' ? 'green' : ($item->status === 'declined' ? 'red' : '') }}">{{ \App\Models\ApiRequest::STATUSES[$item->status] ?? $item->status }}</span></td>
      <td>{{ $item->created_at->format('M j, Y') }}</td>
    </tr>
  @empty
    <tr><td colspan="4" class="faint">No requests yet.</td></tr>
  @endforelse
  </tbody>
</table></div>
<div style="margin-top:16px">{{ $requests->links() }}</div>
@endsection
