@extends('layouts.site')
@section('title', 'API documentation')
@section('noindex', '1')
@section('style')
.doc{max-width:760px}
.doc code, .doc pre{font-family:var(--mono);font-size:13px}
.doc pre{background:var(--space);border:1px solid var(--line);border-radius:2px;padding:14px 16px;overflow-x:auto;margin:0}
.doc h2{margin-top:8px}
.endpoint{display:flex;gap:12px;align-items:baseline;flex-wrap:wrap;padding:12px 0;border-bottom:1px solid var(--line)}
.endpoint b{font-family:var(--mono);font-weight:500;font-size:13px;color:var(--gold);letter-spacing:.04em}
@endsection
@section('content')
<div class="doc stack">
  <span class="eyebrow">API</span>
  <h1 class="h2">Documentation</h1>
  @if (session('plain_key'))
    <div class="flash" style="word-break:break-all"><strong>New key:</strong> <code>{{ session('plain_key') }}</code><br><span>Copy it now. It is not shown again, and the previous key no longer works.</span></div>
  @endif
  <p class="lead">The same records as the atlas, as JSON. Each response includes <code>data</code> and <code>meta</code>, with a timestamp and the license.</p>

  <section class="card stack">
    <h2 class="h3">Your keys</h2>
    @foreach ($keys as $key)
      <div class="row" style="justify-content:space-between">
        <span><strong>{{ $key->name }}</strong> <code>{{ $key->prefix }}…</code> · {{ number_format($key->daily_quota) }} a day{{ $key->active ? '' : ' · switched off' }}</span>
        <form method="POST" action="/account/api-keys/{{ $key->id }}/refresh">@csrf<button class="btn sm" type="submit">Refresh key</button></form>
      </div>
    @endforeach
  </section>

  <section class="stack">
    <h2 class="h3">Authentication</h2>
    <p class="muted" style="margin:0">Send the key on every request. Base URL: <code>{{ $base }}</code></p>
    <pre>curl {{ $base }}/countries \
  -H "Authorization: Bearer ssa_live_…"</pre>
    <p class="faint" style="margin:0">You can also send the key as the <code>X-API-Key</code> header. The ceiling is 120 requests a minute. The daily quota resets at 00:00 UTC. Responses include <code>X-Quota-Limit</code> and <code>X-Quota-Remaining</code>.</p>
  </section>

  <section class="stack">
    <h2 class="h3">Endpoints</h2>
    <div class="endpoint"><b>GET /countries</b><span class="muted">Optional <code>region</code>, <code>member</code>, <code>limit</code>, <code>offset</code>.</span></div>
    <div class="endpoint"><b>GET /countries/{iso3}/file</b><span class="muted">One country file. <code>iso3</code> is three letters, for example HUN.</span></div>
    <div class="endpoint"><b>GET /programs</b><span class="muted">Optional <code>iso3</code> and <code>category</code>.</span></div>
    <div class="endpoint"><b>GET /organizations</b><span class="muted">Optional <code>iso3</code>, <code>category</code>, and <code>domain</code>.</span></div>
    <div class="endpoint"><b>GET /engagements</b><span class="muted">Optional <code>iso3</code>, <code>company</code>, and <code>since</code> (YYYY-MM-DD).</span></div>
    <div class="endpoint"><b>GET /briefings</b><span class="muted">Dates and headlines, newest first.</span></div>
    <div class="endpoint"><b>GET /briefings/{date}</b><span class="muted">One edition, or <code>latest</code>. The archive keeps {{ config('atlas.archive_days') }} days.</span></div>
    <div class="endpoint"><b>GET /events</b><span class="muted">Optional <code>type</code>: congress, conference, or decision.</span></div>
    <p class="faint" style="margin:0">List endpoints accept <code>limit</code> (1 to 500, default 100) and <code>offset</code>.</p>
  </section>

  <section class="stack">
    <h2 class="h3">Errors</h2>
    <div class="endpoint"><b>401 unauthorized</b><span class="muted">Missing, unknown, or expired key.</span></div>
    <div class="endpoint"><b>429 rate_limited</b><span class="muted">More than 120 requests in a minute.</span></div>
    <div class="endpoint"><b>429 quota_exceeded</b><span class="muted">The daily quota is used up.</span></div>
    <div class="endpoint"><b>404 not_found</b><span class="muted">No country file or briefing for that address.</span></div>
  </section>
</div>
@endsection
