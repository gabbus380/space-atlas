@extends('layouts.site')
@section('title', 'API keys')
@section('noindex', '1')
@section('content')
<div class="stack" style="gap:8px"><span class="eyebrow">EMMA staff</span><h1 class="h2">API keys</h1></div>
@include('admin.nav')
@if (session('plain_key'))
  <div class="flash" style="word-break:break-all"><strong>API key:</strong> <code>{{ session('plain_key') }}</code></div>
@endif
<div class="grid2" style="margin-top:16px;align-items:start">
  <form class="card form" method="POST" action="/admin/api-keys">@csrf
    <h2 class="h3">Issue a key</h2>
    <div class="picker">
      <label for="org-search">Organization
        <input type="text" id="org-search" name="name" required autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="org-results" placeholder="Search the directory">
      </label>
      <ul class="picker-list" id="org-results" role="listbox" hidden></ul>
    </div>
    <label>Contact email<input type="email" id="org-email" name="contact_email"><span class="faint" id="org-email-note"></span></label>
    <div class="row">
      <label style="flex:1">Requests a day<input type="number" name="daily_quota" value="{{ config('atlas.api.starter_quota') }}" required></label>
      <label style="flex:1">People<input type="number" name="max_users" value="{{ config('atlas.api.starter_users') }}" required></label>
    </div>
    <label>Expires on<input type="date" name="expires_on" value="{{ today()->addYear()->toDateString() }}"></label>
    <label>Notes<textarea name="notes" rows="2"></textarea></label>
    <button class="btn sm" type="submit">Issue</button>
  </form>
  <div class="tw"><table class="t">
    <thead><tr><th>Organization</th><th>Key</th><th>Today</th><th></th></tr></thead>
    <tbody>
    @forelse ($keys as $key)
      <tr>
        <td>{{ $key->name }}<br><span class="faint" style="font-size:12.5px">{{ $key->max_users }} people, until {{ $key->expires_on?->format('M j, Y') ?? 'open' }}</span></td>
        <td><code>{{ $key->prefix }}…</code></td>
        <td>{{ $key->usedToday() }} / {{ number_format($key->daily_quota) }}</td>
        <td>
          <div class="row">
            <form method="POST" action="/admin/api-keys/{{ $key->id }}/refresh">@csrf<button class="btn sm" type="submit">Refresh</button></form>
            <form method="POST" action="/admin/api-keys/{{ $key->id }}/toggle">@csrf<button class="btn sm" type="submit">{{ $key->active ? 'Switch off' : 'Switch on' }}</button></form>
          </div>
        </td>
      </tr>
    @empty
      <tr><td colspan="4" class="faint">None yet.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div>
<script>
(() => {
  const input = document.getElementById('org-search');
  const list = document.getElementById('org-results');
  const email = document.getElementById('org-email');
  const note = document.getElementById('org-email-note');
  if (!input || !list) return;
  let items = [];
  let active = -1;
  let timer;

  const close = () => {
    list.hidden = true;
    list.innerHTML = '';
    input.setAttribute('aria-expanded', 'false');
    active = -1;
  };

  const paint = () => {
    list.innerHTML = '';
    items.forEach((org, index) => {
      const li = document.createElement('li');
      const button = document.createElement('button');
      button.type = 'button';
      button.setAttribute('role', 'option');
      button.setAttribute('aria-selected', index === active ? 'true' : 'false');
      button.id = 'org-option-' + index;
      button.append(document.createTextNode(org.name));
      const meta = document.createElement('small');
      meta.textContent = [org.iso3, org.account, org.email].filter(Boolean).join(' · ');
      button.append(meta);
      button.addEventListener('mousedown', (event) => {
        event.preventDefault();
        choose(index);
      });
      li.append(button);
      list.append(li);
    });
    const open = items.length > 0;
    list.hidden = !open;
    input.setAttribute('aria-expanded', open ? 'true' : 'false');
    input.setAttribute('aria-activedescendant', active >= 0 ? 'org-option-' + active : '');
  };

  const choose = (index) => {
    const org = items[index];
    if (!org) return;
    input.value = org.name;
    if (org.email) {
      email.value = org.email;
      note.textContent = org.account ? 'Filled from ' + org.account + '. You can change it.' : 'You can change this address.';
    } else {
      note.textContent = 'No account is linked to this organization yet.';
    }
    close();
  };

  input.addEventListener('input', () => {
    note.textContent = '';
    clearTimeout(timer);
    const query = input.value.trim();
    if (query.length < 2) {
      close();
      return;
    }
    timer = setTimeout(async () => {
      const response = await fetch('/admin/api-keys/organizations?q=' + encodeURIComponent(query), {
        headers: {Accept: 'application/json'},
      });
      if (!response.ok) {
        close();
        return;
      }
      items = await response.json();
      active = items.length ? 0 : -1;
      paint();
    }, 180);
  });

  input.addEventListener('keydown', (event) => {
    if (list.hidden) return;
    if (event.key === 'ArrowDown') {
      event.preventDefault();
      active = Math.min(items.length - 1, active + 1);
      paint();
    } else if (event.key === 'ArrowUp') {
      event.preventDefault();
      active = Math.max(0, active - 1);
      paint();
    } else if (event.key === 'Enter' && active >= 0) {
      event.preventDefault();
      choose(active);
    } else if (event.key === 'Escape') {
      close();
    }
  });

  input.addEventListener('blur', () => setTimeout(close, 120));
})();
</script>
@endsection
