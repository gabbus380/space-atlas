@php($p = \App\Support\Plans::prices())
@extends('layouts.site')
@section('title', 'Your account')
@section('noindex', '1')
@section('style')
.flash,.errors{border-radius:0}
.acct,.acct *{border-radius:0}
.acct{max-width:920px;display:flex;flex-direction:column}
.acct-head{padding-bottom:18px;border-bottom:1px solid var(--line-2)}
.acct h2.now{font-family:var(--serif);font-size:clamp(32px,4vw,44px);line-height:1.05;font-weight:400;margin:8px 0 0}
.kinds{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));border:1px solid var(--line-2);margin-top:8px}
.kind{padding:16px 16px 18px;border-right:1px solid var(--line);display:flex;flex-direction:column;gap:8px;min-width:0}
.kind:last-child{border-right:0}
.kind.on{background:var(--gold-soft);box-shadow:inset 0 3px 0 var(--gold)}
.kind .nm{font-family:var(--serif);font-size:26px;line-height:1.1;font-weight:400;color:var(--ink)}
.kind .pr{font-family:var(--mono);font-size:12px;letter-spacing:.06em;color:var(--gold)}
.kind p{margin:0;color:var(--ink-2);font-size:13.5px;line-height:1.45}
.kind .tag{font-family:var(--mono);font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--gold)}
.sheet{padding:26px 0;border-bottom:1px solid var(--line);display:flex;flex-direction:column;gap:14px}
.sheet h2{font-family:var(--serif);font-size:26px;font-weight:400}
.acct .kv{grid-template-columns:140px minmax(0,1fr);gap:0;border-top:1px solid var(--line)}
.acct .kv dt,.acct .kv dd{margin:0;padding:10px 0;border-bottom:1px solid var(--line)}
.acct .kv dt{font-family:var(--mono);font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:var(--ink-3)}
.acct .pill{border-radius:0;letter-spacing:.12em}
.up{border:1px solid rgba(229,191,109,.45);background:var(--gold-soft);padding:14px 16px;display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap}
.chips{display:flex;gap:6px;flex-wrap:wrap}
.acct .form{max-width:460px}
.acct .check input,.acct .check input[type=radio],.acct .check input[type=radio]:checked::after{border-radius:0}
@media (max-width:800px){.kinds{grid-template-columns:minmax(0,1fr)}.kind{border-right:0;border-bottom:1px solid var(--line)}.kind:last-child{border-bottom:0}}
@endsection
@section('content')
@php($tierLabel = ['free' => 'Free account', 'personal' => 'Personal', 'teams' => 'Teams'][$tier] ?? 'Free account')
@php($headline = $tier === 'personal' ? 'You are on Personal.' : ($tier === 'teams' ? 'You are on Teams.' : 'You are on the Free account.'))
@php($blurb = match ($tier) {
  'free' => 'The open atlas, '.$credits['limit'].' full country files a month, and one demo briefing you can play. A new demo replaces it each month.',
  'personal' => 'Every country file, Compare, and a new briefing each morning.',
  'teams' => 'Personal for every seat, plus a shared watchlist, team notes and one invoice.',
  default => '',
})
<div class="acct">
  <header class="acct-head stack" style="gap:8px">
    <span class="eyebrow">Account</span>
    <h1 class="h2">{{ $user->name }}</h1>
    <p class="muted" style="margin:0">{{ $user->email }}@if($user->organization) &middot; {{ $user->organization }}@endif</p>
  </header>

  @unless ($user->hasVerifiedEmail())
    <div class="up" style="margin-top:18px"><span>Confirm your email address to open full country files and to subscribe.</span><form method="POST" action="/email/verification-notification">@csrf<button class="btn sm" type="submit">Send the link again</button></form></div>
  @endunless

  <section class="sheet" aria-labelledby="accessH">
    <div>
      <h2 id="accessH" class="now">{{ $headline }}</h2>
      <p class="muted" style="margin:10px 0 0;max-width:62ch">{{ $blurb }}</p>
    </div>

    <div class="kinds" aria-label="Kinds of account">
      <article class="kind {{ $tier === 'free' ? 'on' : '' }}">
        <span class="tag">{{ $tier === 'free' ? 'Your plan' : 'Free account' }}</span>
        <span class="nm">Free</span>
        <span class="pr">$0</span>
        <p>The open atlas, {{ config('atlas.free_files_per_month') }} country files a month, and the monthly demo briefing.</p>
      </article>
      <article class="kind {{ $tier === 'personal' ? 'on' : '' }}">
        <span class="tag">{{ $tier === 'personal' ? 'Your plan' : 'Personal' }}</span>
        <span class="nm">Personal</span>
        <span class="pr">${{ $p['personal_year_month'] }} a month, billed ${{ $p['personal_year'] }} yearly</span>
        <p>All country files, Compare, the daily briefing and the archive.</p>
        @if ($tier === 'free')<a class="btn gold sm" href="/subscribe/personal?interval=year">Choose Personal</a>@endif
      </article>
      <article class="kind {{ $tier === 'teams' ? 'on' : '' }}">
        <span class="tag">{{ $tier === 'teams' ? 'Your plan' : 'Teams' }}</span>
        <span class="nm">Teams</span>
        <span class="pr">${{ $p['teams_year'] }} a user a year, at least {{ $p['teams_min'] }}</span>
        <p>Personal for every seat, a shared watchlist, team notes and one invoice.</p>
        @if ($tier !== 'teams')<a class="btn sm" href="/subscribe/teams?interval=year">Choose Teams</a>@endif
      </article>
    </div>

    @if ($personal)
      @php($iv = \App\Support\Billing::interval($personal))
      <dl class="kv">
        <dt>Plan</dt><dd>Personal, {{ $iv === 'month' ? 'billed monthly at $'.$p['personal_month'] : 'billed yearly at $'.$p['personal_year'] }}</dd>
        <dt>Status</dt><dd>
          @if ($personal->onGracePeriod()) <span class="pill red">Ends {{ $personal->ends_at->format('F j, Y') }}</span>
          @elseif ($personal->pastDue()) <span class="pill red">Payment due</span>
          @else <span class="pill green">Active</span> @endif
          @if (\App\Support\Billing::founding($personal)) <span class="pill gold">Founding rate</span> @endif
        </dd>
        @if ($personal->next_billed_at && ! $personal->onGracePeriod())<dt>Renews</dt><dd>{{ \Illuminate\Support\Carbon::parse($personal->next_billed_at)->format('F j, Y') }}</dd>@endif
        <dt>Since</dt><dd>{{ $personal->created_at->format('F j, Y') }}</dd>
      </dl>
      @if ($iv === 'month' && ! $personal->onGracePeriod())
        <div class="up"><span>Switch to yearly billing and save ${{ $p['personal_saving'] }} a year.</span>
          <form method="POST" action="/billing/yearly">@csrf<input type="hidden" name="plan" value="personal"><button class="btn gold sm" type="submit">Switch to yearly</button></form></div>
      @endif
      <div class="row">
        <a class="btn sm" href="/billing/payment-method?plan=personal">Update payment method</a>
        @if ($personal->onGracePeriod())
          <form method="POST" action="/billing/resume">@csrf<input type="hidden" name="plan" value="personal"><button class="btn sm" type="submit">Keep my plan</button></form>
        @else
          <details><summary class="btn sm danger" style="list-style:none">Cancel</summary>
            <form method="POST" action="/billing/cancel" class="stack" style="margin-top:10px;gap:10px">@csrf<input type="hidden" name="plan" value="personal">
              <p class="muted" style="margin:0;font-size:13.5px">Your plan stays open until the end of the period you paid for and does not renew. Within 14 days of your first payment you can also ask for a full refund.</p>
              <button class="btn sm danger" type="submit">Cancel at the end of the period</button></form></details>
        @endif
      </div>
    @elseif ($tier === 'personal' && $user->hasPersonalOverride())
      <p class="muted" style="margin:0">Complimentary Personal access from EMMA Aerospace Consulting, until {{ $user->override_until->format('F j, Y') }}.</p>
    @endif

    @if ($team)
      <p style="margin:0">You are {{ $team->owner_id === $user->id ? 'the owner' : 'a member' }} of <strong>{{ $team->name }}</strong>@if(! $team->isActive()) <span class="pill red">Not active</span>@endif.</p>
      @if ($personal && $tier === 'teams')<p class="muted" style="margin:0;font-size:13.5px">Your team seat already includes Personal. You can cancel your Personal plan above.</p>@endif
      <div class="row"><a class="btn sm" href="/team">Open the team page</a></div>
    @endif

    @if ($tier === 'free')
      <p class="muted" style="margin:0">This month you opened {{ $credits['used'] }} of {{ $credits['limit'] }} country files.@if($credits['used']) @foreach ($credits['unlocked'] as $iso)<span class="pill">{{ $iso }}</span>@endforeach @endif The allowance renews on {{ $credits['renews'] }}.</p>
    @endif
  </section>

  @if ($apiKeys->isNotEmpty())
  <section class="sheet" aria-labelledby="apiH">
    <h2 id="apiH">API</h2>
    <p class="muted" style="margin:0">A key has been issued for this account. Refreshing it retires the previous secret immediately.</p>
    @foreach ($apiKeys as $key)
      <div class="row" style="justify-content:space-between">
        <span><strong>{{ $key->name }}</strong> <code>{{ $key->prefix }}…</code><br><span class="faint" style="font-size:12.5px">{{ number_format($key->daily_quota) }} requests a day{{ $key->active ? '' : ' · switched off' }}</span></span>
        <form method="POST" action="/account/api-keys/{{ $key->id }}/refresh">@csrf<button class="btn sm" type="submit">Refresh key</button></form>
      </div>
    @endforeach
    <div class="row"><a class="btn sm" href="/account/api">API documentation</a></div>
  </section>
  @endif

  @if ($transactions->count())
  <section class="sheet" aria-labelledby="invH">
    <h2 id="invH">Payments and invoices</h2>
    <p class="faint" style="margin:0;font-size:13px">Paddle.com is the reseller and issues the invoices.</p>
    <div class="tw"><table class="t">
      <thead><tr><th>Date</th><th>Invoice</th><th>Total</th><th>Status</th><th></th></tr></thead>
      <tbody>@foreach ($transactions as $t)
        <tr><td>{{ $t->billed_at?->format('M j, Y') }}</td><td>{{ $t->invoice_number ?: 'n/a' }}</td><td>{{ $t->total() }}</td><td>{{ ucfirst($t->status) }}</td>
        <td>@if ($t->invoice_number)<a href="/billing/invoice/{{ $t->id }}" target="_blank" rel="noopener">PDF</a>@endif</td></tr>
      @endforeach</tbody>
    </table></div>
  </section>
  @endif

  <section class="sheet" aria-labelledby="profH">
    <h2 id="profH">Profile</h2>
    <form class="form" method="POST" action="/account/profile">@csrf
      <label>Full name<input type="text" name="name" value="{{ old('name', $user->name) }}" required></label>
      <label>Organization<input type="text" name="organization" value="{{ old('organization', $user->organization) }}"></label>
      @if (session('org_matches'))
        <fieldset class="stack" style="gap:8px">
          <legend>This matches the directory. Choose the organization, or keep the name unmatched.</legend>
          @foreach (session('org_matches') as $org)
            <label class="check"><input type="radio" name="organization_id" value="{{ $org['id'] }}" @checked(old('organization_id') === $org['id'])> <span>{{ $org['name'] }} <span class="faint">{{ $org['iso3'] ?? '' }}</span></span></label>
          @endforeach
          <label class="check"><input type="checkbox" name="keep_unmatched" value="1"> <span>Keep it as an unmatched name</span></label>
        </fieldset>
        <label>Your title<input type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}" autocomplete="organization-title"></label>
        <label class="check"><input type="checkbox" name="show_on_organization" value="1" @checked(old('show_on_organization', $user->show_on_organization))> <span>Show my name and title with this organization</span></label>
      @elseif($user->organization_id)
        <p class="muted" style="margin:0">Linked to the directory. Title: {{ $user->job_title ?: 'not set' }}. {{ $user->show_on_organization ? 'Visible on the organization.' : 'Hidden on the organization.' }}</p>
        <input type="hidden" name="organization_id" value="{{ $user->organization_id }}">
        <label>Your title<input type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}" required></label>
        <label class="check"><input type="checkbox" name="show_on_organization" value="1" @checked(old('show_on_organization', $user->show_on_organization))> <span>Show my name and title with this organization</span></label>
        <label class="check"><input type="checkbox" name="keep_unmatched" value="1"> <span>Remove the directory link and keep the name only</span></label>
      @endif
      <label class="check"><input type="checkbox" name="briefing" value="1" @checked(! $user->briefing_opt_out)> <span>Email me the morning briefing while a paid plan is active</span></label>
      <label class="check"><input type="checkbox" name="marketing" value="1" @checked($user->marketing_opt_in)> <span>Email me the monthly note about the atlas</span></label>
      <div class="row"><button class="btn sm" type="submit">Save</button></div>
    </form>
  </section>

  <section class="sheet" aria-labelledby="couponH">
    <h2 id="couponH">Coupon</h2>
    <form class="form" method="POST" action="/account/coupon">@csrf
      <label>Code<input type="text" name="code" required autocomplete="off"></label>
      <div class="row"><button class="btn sm" type="submit">Apply</button></div>
    </form>
  </section>

  <section class="sheet" aria-labelledby="pwH">
    <h2 id="pwH">Password</h2>
    <form class="form" method="POST" action="/account/password">@csrf
      @if ($user->password)
        <label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label>
      @else
        <p class="muted" style="margin:0">This account signs in with {{ ucfirst($user->auth_provider ?: 'a provider') }}. You can also set a password.</p>
      @endif
      <label>New password<input type="password" name="password" required autocomplete="new-password"></label>
      <label>Repeat the new password<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
      <div class="row"><button class="btn sm" type="submit">{{ $user->password ? 'Change password' : 'Set a password' }}</button></div>
    </form>
  </section>

  <section class="sheet" aria-labelledby="dataH">
    <h2 id="dataH">Your data</h2>
    <div class="row"><a class="btn sm" href="/account/export">Download my data</a><a class="btn sm" href="/withdraw">Withdrawal form</a>
      <form method="POST" action="/logout">@csrf<button class="btn sm" type="submit">Sign out</button></form></div>
    <details><summary class="btn sm danger" style="list-style:none">Delete my account</summary>
      <form class="form" method="POST" action="/account/delete" style="margin-top:12px">@csrf
        <p class="muted" style="margin:0;font-size:13.5px">This removes your account, your watchlist and your file history. Paddle keeps payment records as tax law requires. Cancel any paid plan first.</p>
        @if ($user->password)<label>Your password<input type="password" name="password" required autocomplete="current-password"></label>@endif
        <div class="row"><button class="btn sm danger" type="submit">Delete permanently</button></div>
      </form></details>
  </section>
</div>
@endsection
