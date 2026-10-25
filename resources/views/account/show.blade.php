@php($p = \App\Support\Plans::prices())
@extends('layouts.site')
@section('title', 'Your account')
@section('noindex', '1')
@section('style')
.acc{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:20px;align-items:start;margin-top:28px}
.plan-h{display:flex;justify-content:space-between;gap:12px;align-items:baseline;flex-wrap:wrap}
.up{border:1px solid rgba(229,191,109,.45);background:var(--gold-soft);border-radius:3px;padding:14px 16px;display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap}
.chips{display:flex;gap:6px;flex-wrap:wrap}
@media (max-width:900px){.acc{grid-template-columns:minmax(0,1fr)}}
@endsection
@section('content')
@php($tierLabel = ['free' => 'Free account', 'personal' => 'Personal', 'teams' => 'Teams'][$tier] ?? 'Free account')
<div class="stack" style="gap:10px">
  <span class="eyebrow">Account</span>
  <h1 class="h2">{{ $user->name }}</h1>
  <p class="muted" style="margin:0">{{ $user->email }}@if($user->organization) &middot; {{ $user->organization }}@endif</p>
  @unless ($user->hasVerifiedEmail())
    <div class="up"><span>Confirm your email address to open full country files and to subscribe.</span><form method="POST" action="/email/verification-notification">@csrf<button class="btn sm" type="submit">Send the link again</button></form></div>
  @endunless
</div>

<div class="acc">
  <div class="stack">
    <section class="card stack" aria-labelledby="planH">
      <div class="plan-h"><h2 class="h3" id="planH">Your plan</h2><span class="pill {{ $tier === 'free' ? '' : 'gold' }}">{{ $tierLabel }}</span></div>

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
        <div class="stack" style="gap:8px">
          <p style="margin:0">You are {{ $team->owner_id === $user->id ? 'the owner' : 'a member' }} of <strong>{{ $team->name }}</strong>@if(! $team->isActive()) <span class="pill red">Not active</span>@endif.</p>
          @if ($personal && $tier === 'teams')<p class="muted" style="margin:0;font-size:13.5px">Your team seat already includes Personal. You can cancel your Personal plan above.</p>@endif
          <div class="row"><a class="btn sm" href="/team">Open the team page</a></div>
        </div>
      @endif

      @if ($tier === 'free')
        <p class="muted" style="margin:0">Free account: the open atlas, plus {{ $credits['limit'] }} full country files a month. This month you opened {{ $credits['used'] }} of {{ $credits['limit'] }}@if($credits['used']):@endif</p>
        @if ($credits['used'])<div class="chips">@foreach ($credits['unlocked'] as $iso)<span class="pill">{{ $iso }}</span>@endforeach</div>@endif
        <p class="faint" style="margin:0;font-size:13px">The allowance renews on {{ $credits['renews'] }}.</p>
        <div class="up"><span>Personal opens all 81 country files, the full briefing and every tracker. ${{ $p['personal_year_month'] }} a month, billed ${{ $p['personal_year'] }} yearly.</span><a class="btn gold sm" href="/pricing">See plans</a></div>
      @endif
    </section>

    @if ($transactions->count())
    <section class="card stack" aria-labelledby="invH">
      <h2 class="h3" id="invH">Payments and invoices</h2>
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
  </div>

  <div class="stack">
    <section class="card stack" aria-labelledby="profH">
      <h2 class="h3" id="profH">Profile</h2>
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
        <label class="check"><input type="checkbox" name="marketing" value="1" @checked($user->marketing_opt_in)> <span>Occasional news about the atlas by email</span></label>
        <div class="row"><button class="btn sm" type="submit">Save</button></div>
      </form>
    </section>
    <section class="card stack" aria-labelledby="couponH">
      <h2 class="h3" id="couponH">Coupon</h2>
      <form class="form" method="POST" action="/account/coupon">@csrf
        <label>Code<input type="text" name="code" required autocomplete="off"></label>
        <div class="row"><button class="btn sm" type="submit">Apply</button></div>
      </form>
    </section>
    <section class="card stack" aria-labelledby="pwH">
      <h2 class="h3" id="pwH">Password</h2>
      <form class="form" method="POST" action="/account/password">@csrf
        <label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label>
        <label>New password<input type="password" name="password" required autocomplete="new-password"></label>
        <label>Repeat the new password<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
        <div class="row"><button class="btn sm" type="submit">Change password</button></div>
      </form>
    </section>
    <section class="card stack" aria-labelledby="dataH">
      <h2 class="h3" id="dataH">Your data</h2>
      <div class="row"><a class="btn sm" href="/account/export">Download my data</a><a class="btn sm" href="/withdraw">Withdrawal form</a>
        <form method="POST" action="/logout">@csrf<button class="btn sm" type="submit">Sign out</button></form></div>
      <details><summary class="btn sm danger" style="list-style:none">Delete my account</summary>
        <form class="form" method="POST" action="/account/delete" style="margin-top:12px">@csrf
          <p class="muted" style="margin:0;font-size:13.5px">This removes your account, your watchlist and your file history. Paddle keeps payment records as tax law requires. Cancel any paid plan first.</p>
          <label>Your password<input type="password" name="password" required autocomplete="current-password"></label>
          <div class="row"><button class="btn sm danger" type="submit">Delete permanently</button></div>
        </form></details>
    </section>
  </div>
</div>
@endsection
