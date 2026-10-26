@extends('layouts.site')
@section('title', 'Sign-in')
@section('noindex', '1')
@section('style')
.si{max-width:720px;display:flex;flex-direction:column;gap:16px;margin-top:16px}
.si .card h2{display:flex;justify-content:space-between;gap:12px;align-items:baseline;flex-wrap:wrap}
.si .where{margin:0;color:var(--ink-2);font-size:14px;line-height:1.55}
.si code{font-family:var(--mono);font-size:12.5px;color:var(--gold);word-break:break-all}
.si textarea{min-height:140px;font-family:var(--mono);font-size:12.5px}
.legend{margin-top:18px}
.legend table{min-width:640px}
.legend td:first-child{width:28%;font-family:var(--mono);font-size:12.5px;color:var(--gold)}
@endsection
@section('content')
<div class="stack" style="gap:8px">
  <span class="eyebrow">Admin only</span>
  <h1 class="h2">Sign-in</h1>
  <p class="muted" style="margin:0;max-width:68ch">Paste the identifiers from Apple, Google, Microsoft and LinkedIn. Leave a secret blank to keep the one already saved. Each provider must list the redirect URL exactly.</p>
</div>
@include('admin.nav')
<section class="card legend" aria-labelledby="legendH">
  <h2 class="h3" id="legendH">What each value is</h2>
  <p class="muted" style="margin:0">These are the only values ATLAS asks for. A secret left blank keeps the one already saved. The redirect URL is not a secret. Copy it into the provider exactly.</p>
  <div class="tw">
    <table class="t">
      <thead><tr><th>Variable</th><th>What to paste</th></tr></thead>
      <tbody>
        @foreach (\App\Support\Connectors::SIGNIN as $slug => $provider)
          <tr><td>{{ $provider['name'] }} redirect</td><td>Register <code>{{ $base }}/login/{{ $slug }}/callback</code> in that provider. Sign-in fails if a single character differs.</td></tr>
          @foreach ($provider['fields'] as $field => $meta)
            <tr><td>{{ $provider['name'] }} · {{ $meta[0] }}</td><td>{{ $meta[2] }}</td></tr>
          @endforeach
        @endforeach
      </tbody>
    </table>
  </div>
</section>
<form class="si" method="POST" action="/admin/sign-in">@csrf
  @foreach (\App\Support\Connectors::SIGNIN as $slug => $provider)
    <section class="card stack" aria-labelledby="si-{{ $slug }}">
      <h2 class="h3" id="si-{{ $slug }}">{{ $provider['name'] }} <span class="pill {{ $ready[$slug] ? 'green' : '' }}">{{ $ready[$slug] ? 'Connected' : 'Not connected' }}</span></h2>
      <p class="where">{{ $provider['where'] }}</p>
      <label>Redirect URL<input type="text" readonly value="{{ $base }}/login/{{ $slug }}/callback" onclick="this.select()"></label>
      @foreach ($provider['fields'] as $field => $meta)
        @php($row = $saved[$slug][$field])
        <label>{{ $meta[0] }} @if($row['hint'])<span class="faint">saved {{ $row['hint'] }}</span>@endif
          @if ($field === 'private_key')
            <textarea name="providers[{{ $slug }}][{{ $field }}]" autocomplete="off" spellcheck="false" placeholder="-----BEGIN PRIVATE KEY-----"></textarea>
          @elseif ($field === 'tenant')
            <input type="text" name="providers[{{ $slug }}][{{ $field }}]" value="{{ $row['value'] !== '' ? $row['value'] : 'common' }}" autocomplete="off">
          @else
            <input type="{{ $meta[1] ? 'password' : 'text' }}" name="providers[{{ $slug }}][{{ $field }}]" value="{{ $row['value'] }}" autocomplete="off">
          @endif
        </label>
      @endforeach
      <label class="check"><input type="checkbox" name="forget[{{ $slug }}]" value="1"> <span>Remove the saved {{ $provider['name'] }} details</span></label>
    </section>
  @endforeach
  <div class="row"><button class="btn" type="submit">Save sign-in</button></div>
</form>
@endsection
