@extends('layouts.site')
@section('title', 'Connectors')
@section('noindex', '1')
@section('content')
<div class="stack" style="gap:8px"><span class="eyebrow">Admin only</span><h1 class="h2">Connectors</h1>
  <p class="muted" style="margin:0">Paddle runs billing. AI credentials are stored for the tools that will call a model. Leave a secret blank to keep the one already saved.</p>
</div>
@include('admin.nav')
<form class="form" method="POST" action="/admin/connectors" style="margin-top:16px;max-width:640px">@csrf
  <section class="card stack">
    <h2 class="h3">Paddle</h2>
    <label class="check"><input type="checkbox" name="paddle_sandbox" value="1" @checked($sandbox)> <span>Use the Paddle sandbox</span></label>
    @foreach (\App\Support\Connectors::PADDLE as $key => $label)
      @php($field = str_replace('paddle.', '', $key))
      <label>{{ $label }} @if($hints[$key])<span class="faint">saved {{ $hints[$key] }}</span>@endif
        <input type="{{ str_contains($key, 'price_') ? 'text' : 'password' }}" name="paddle[{{ $field }}]" autocomplete="off">
      </label>
    @endforeach
  </section>
  <section class="card stack" style="margin-top:14px">
    <h2 class="h3">AI provider</h2>
    <label>Provider
      <select name="ai[provider]">
        <option value="">None</option>
        @foreach (['openai' => 'OpenAI', 'anthropic' => 'Anthropic', 'google' => 'Google'] as $value => $label)
          <option value="{{ $value }}" @selected($ai['provider'] === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </label>
    <label>Model @if($hints['ai.model'] ?? false)<span class="faint">saved</span>@endif<input type="text" name="ai[model]" value="{{ $ai['model'] }}"></label>
    <label>Base URL<input type="url" name="ai[base_url]" value="{{ $ai['base_url'] }}" placeholder="https://"></label>
    <label>API key @if($hints['ai.api_key'])<span class="faint">saved {{ $hints['ai.api_key'] }}</span>@endif<input type="password" name="ai[api_key]" autocomplete="off"></label>
  </section>
  <div class="row" style="margin-top:14px"><button class="btn" type="submit">Save connectors</button></div>
</form>
@endsection
