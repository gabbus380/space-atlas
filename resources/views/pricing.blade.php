@php($p = \App\Support\Plans::prices())
@php($tier = \App\Support\Plans::tier(auth()->user()))
@php($founding = \Illuminate\Support\Carbon::parse(config('atlas.founding_until')))
@extends('layouts.site')
@section('title', 'Plans and pricing')
@section('description', 'Sovereign Space ATLAS plans: Free, Personal from $'.$p['personal_year_month'].' a month billed yearly, Teams from $'.$p['teams_year_month'].' per user, and API access.')
@section('style')
.hero{display:flex;flex-direction:column;gap:16px;max-width:760px}
.bill{display:inline-flex;border:1px solid var(--line-2);padding:3px;margin-top:8px;align-self:flex-start}
.bill button{border:0;background:none;color:var(--ink-2);font:500 13px/1 var(--font);padding:10px 16px;cursor:pointer;display:inline-flex;gap:8px;align-items:center}
.bill button[aria-pressed="true"]{background:var(--ink);color:var(--void)}
.bill .save{font-family:var(--mono);font-size:10.5px;letter-spacing:.06em;color:#070b14;background:var(--gold);padding:3px 6px}
.bill button[aria-pressed="false"] .save{background:var(--gold-soft);color:var(--gold)}
.plans{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-top:36px;align-items:stretch}
.plan{background:var(--panel);border:1px solid var(--line);padding:22px 20px;display:flex;flex-direction:column;gap:14px;position:relative}
.plan.best{border:1px solid var(--gold);box-shadow:0 0 0 1px rgba(229,191,109,.25) inset}
.plan.api{background:#0f1729}
.plan .tag{position:absolute;top:-11px;left:18px;font-family:var(--mono);font-size:10.5px;letter-spacing:.1em;text-transform:uppercase;background:var(--gold);color:#070b14;padding:4px 8px}
.plan .nm{font-family:var(--mono);font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:var(--gold)}
.price{display:flex;align-items:baseline;gap:6px;flex-wrap:wrap}
.price b{font-family:var(--serif);font-weight:400;font-size:52px;line-height:1}
.price small{color:var(--ink-2);font-size:14px}
.under{font-size:13.5px;color:var(--ink-2);min-height:42px;line-height:1.5}
.under strong{color:var(--ink);font-weight:500}
.plan ul{list-style:none;margin:0;padding:14px 0 0;border-top:1px solid var(--line);display:flex;flex-direction:column;gap:9px;font-size:14px;line-height:1.45;flex:1}
.plan li{display:grid;grid-template-columns:16px minmax(0,1fr);gap:8px}
.plan li::before{content:"";width:12px;height:7px;border-left:1.6px solid var(--gold);border-bottom:1.6px solid var(--gold);transform:rotate(-45deg);margin-top:5px}
.plan .cta{display:flex;flex-direction:column;gap:8px}
.plan .cta .btn{width:100%}
.assure{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-top:22px}
.assure div{border-top:1px solid var(--line-2);padding-top:12px;font-size:13.5px;line-height:1.5;color:var(--ink-2)}
.assure b{display:block;color:var(--ink);font-weight:500;margin-bottom:2px}
.cmp{margin-top:64px}
.cmp table{width:100%;border-collapse:collapse;font-size:14px;min-width:640px}
.cmp th,.cmp td{padding:10px 8px;border-bottom:1px solid var(--line);text-align:center}
.cmp th:first-child,.cmp td:first-child{text-align:left}
.cmp thead th{font-family:var(--mono);font-weight:400;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:var(--ink-3)}
.yes{color:var(--gold)}.no{color:var(--ink-3)}
.faq{margin-top:64px;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.4fr);gap:32px}
.faq details{border-bottom:1px solid var(--line);padding:14px 0}
.faq summary{cursor:pointer;font-weight:500;list-style:none;display:flex;justify-content:space-between;gap:16px}
.faq summary::after{content:"+";color:var(--gold);font-family:var(--mono)}
.faq details[open] summary::after{content:"\2212"}
.faq details p{margin:10px 0 0;color:var(--ink-2);font-size:14.5px;line-height:1.6}
.legalnote{margin-top:40px;font-size:12.5px;color:var(--ink-3);line-height:1.6;max-width:90ch}
[data-i="month"] .y,[data-i="year"] .m{display:none!important}
.sellers{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:14px;margin-top:28px}
.sellers article{border:1px solid var(--line);padding:18px;display:flex;gap:14px;align-items:center;background:var(--panel)}
.sellers img.mark{width:44px;height:44px;display:block}
.sellers .well{background:#f6f3ec;padding:10px 12px;display:flex}
.sellers .well img{width:120px;height:auto;display:block}
.sellers strong{display:block;font-weight:500}
.sellers span{color:var(--ink-2);font-size:13.5px;line-height:1.45}
#pr .btn,#pr .pill{border-radius:0}
@media (max-width:1040px){.plans,.assure{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:800px){.sellers{grid-template-columns:minmax(0,1fr)}}
@media (max-width:640px){.plans,.assure{grid-template-columns:minmax(0,1fr)}.faq{grid-template-columns:minmax(0,1fr)}.price b{font-size:44px}.bill{width:100%}.bill button{flex:1;justify-content:center;min-height:44px}.plan{padding:18px 16px}.faq summary{min-height:44px;align-items:center}}
@endsection
@section('content')
<div id="pr" data-i="year">
  <section class="hero">
    <span class="eyebrow">Plans and pricing</span>
    <h1 class="display">Start free. Go deeper when the work does.</h1>
    <p class="lead">The atlas is built from public sources and structured for people who brief ministers, boards and partners. The open atlas costs nothing. Paid plans open every country file, the full daily briefing and every tracker.</p>
    <div class="bill" role="group" aria-label="Billing period">
      <button type="button" data-set="year" aria-pressed="true">Yearly <span class="save">save up to ${{ $p['personal_saving'] }}</span></button>
      <button type="button" data-set="month" aria-pressed="false">Monthly</button>
    </div>
  </section>

  <section class="plans" aria-label="Plans">
    <article class="plan">
      <span class="nm">Free</span>
      <div class="price"><b>$0</b></div>
      <p class="under">No card, no account needed.<br>A free account adds <strong>{{ config('atlas.free_files_per_month') }} full country files a month</strong>.</p>
      <ul>
        <li>World map, 13 regions, 35 alliances</li>
        <li>The 18 leading national programs: space budget, capability index and ESA status</li>
        <li>Astronauts flown, and today's briefing headline</li>
        <li>Country file overviews, plus {{ config('atlas.free_files_per_month') }} full files a month with an account</li>
        <li>Community: IAF, IAC hosts, 17 conferences</li>
      </ul>
      <div class="cta">
        <a class="btn" href="/">Open the atlas</a>
        @guest<a class="btn link" href="/register">Create a free account</a>@endguest
      </div>
    </article>

    <article class="plan best">
      <span class="tag y">Best value</span>
      <span class="nm">Personal</span>
      <div class="price y"><b>${{ $p['personal_year_month'] }}</b><small>a month</small></div>
      <div class="price m"><b>${{ $p['personal_month'] }}</b><small>a month</small></div>
      <p class="under y">Billed <strong>${{ $p['personal_year'] }} a year</strong>. You save ${{ $p['personal_saving'] }}, which is 4 months free.</p>
      <p class="under m">Billed monthly. Yearly billing is ${{ $p['personal_year'] }}, which saves ${{ $p['personal_saving'] }}.</p>
      <ul>
        <li>Every country, not only the leading 18</li>
        <li>Compare, including GDP, defense, R&amp;D, space budget per capita and ESA CM25</li>
        <li>Satellites, flight-ready astronauts, term elapsed, programs, ecosystem and tracker entries</li>
        <li>Every country file: strategy, government structure, stakeholders, sources</li>
        <li>The full daily briefing with narration and map playback</li>
        <li>A 21-day briefing archive</li>
        <li>Programs tracker, ecosystem registry and company engagements</li>
        <li>Your watchlist on your account, on every device</li>
      </ul>
      <div class="cta">
        @if ($tier === 'personal' || $tier === 'teams')
          <a class="btn" href="/account">Your plan is active</a>
        @else
          <a class="btn gold y" href="/subscribe/personal?interval=year">Start Personal, yearly</a>
          <a class="btn gold m" href="/subscribe/personal?interval=month">Start Personal, monthly</a>
        @endif
      </div>
    </article>

    <article class="plan">
      <span class="nm">Teams</span>
      <div class="price y"><b>${{ $p['teams_year_month'] }}</b><small>per user a month</small></div>
      <div class="price m"><b>${{ $p['teams_month'] }}</b><small>per user a month</small></div>
      <p class="under y">Billed <strong>${{ $p['teams_year'] }} per user a year</strong>, from {{ $p['teams_min'] }} users. You save ${{ $p['teams_saving'] }} per user.</p>
      <p class="under m">Billed monthly, from {{ $p['teams_min'] }} users. Yearly billing saves ${{ $p['teams_saving'] }} per user.</p>
      <ul>
        <li>Everything in Personal, for every seat</li>
        <li>A shared team watchlist next to each member's own</li>
        <li>Country notes the team writes once and everyone sees</li>
        <li>CSV export from Compare</li>
        <li>Seat management, one invoice, priority email</li>
        <li>An onboarding session with an EMMA analyst</li>
      </ul>
      <div class="cta">
        <a class="btn gold y" href="/subscribe/teams?interval=year">Start Teams, yearly</a>
        <a class="btn gold m" href="/subscribe/teams?interval=month">Start Teams, monthly</a>
        <a class="btn link" href="mailto:{{ config('atlas.contact') }}?subject=Teams%20pilot%20for%20Sovereign%20Space%20ATLAS">Ask for a 30-day team pilot</a>
      </div>
    </article>

    <article class="plan api">
      <span class="nm">API</span>
      <div class="price"><b>${{ number_format($p['api_year']) }}</b><small>a year</small></div>
      <p class="under">Per organization, billed yearly under an Order Form.</p>
      <ul>
        <li>Countries, country files, programs, organizations, engagements, briefings, events</li>
        <li>JSON over HTTPS, refreshed daily</li>
        <li>Sources and a timestamp on every record</li>
        <li>Up to {{ config('atlas.api.starter_users') }} people and {{ number_format(config('atlas.api.starter_quota')) }} requests a day</li>
        <li>Larger use by quote</li>
      </ul>
      <div class="cta">
        <a class="btn" href="/api-order">Request an Order Form</a>
      </div>
    </article>
  </section>

  <section class="sellers" aria-label="Who you are buying from">
    <article>
      <img class="mark" src="/apple-touch-icon.png" width="44" height="44" alt="EMMA Aerospace Consulting">
      <div>
        <p class="eyebrow" style="margin:0">Operator</p>
        <strong>EMMA Aerospace Consulting LLP</strong>
        <span>Richmond, BC, Canada. EMMA provides Sovereign Space ATLAS.</span>
      </div>
    </article>
    <article>
      <div class="well"><img src="/brand/paddle.svg" width="120" height="35" alt="Paddle"></div>
      <div>
        <p class="eyebrow" style="margin:0">Merchant of record</p>
        <strong>Paddle.com</strong>
        <span>Paddle takes the payment, adds tax, sends the invoice and handles refunds.</span>
      </div>
    </article>
  </section>

  <section class="assure" aria-label="Commitments">
    <div><b>14-day money-back guarantee</b>Not useful for your work? Write to us within 14 days of your first payment for a full refund.</div>
    <div><b>Founding rate</b>Subscribe by {{ $founding->format('F j, Y') }} and keep your price for as long as you stay subscribed.</div>
    <div><b>Cancel any time</b>Access runs to the end of the period you paid for. No renewal after that.</div>
    <div><b>Secure checkout by Paddle</b>Cards, PayPal and local methods. Paddle handles VAT and sales tax and sends the receipt.</div>
  </section>

  <section class="cmp" aria-labelledby="cmpH">
    <h2 class="h2" id="cmpH">What each plan includes</h2>
    <div class="tw" style="margin-top:18px">
      <table>
        <thead><tr><th scope="col">Feature</th><th scope="col">Free</th><th scope="col">Personal</th><th scope="col">Teams</th></tr></thead>
        <tbody>
          @foreach ([
            ['World map, 13 regions, 35 alliances and memberships', 1, 1, 1],
            ['The 18 leading programs: space budget, capability index, ESA status, astronauts flown', 1, 1, 1],
            ['Every country, Compare, GDP, defense, R&D, per capita, ESA CM25, satellites, flight-ready astronauts and term elapsed', 0, 1, 1],
            ['Space hubs, US states and German Länder', 1, 1, 1],
            ['Community: IAF members, IAC hosts, 17 conferences', 1, 1, 1],
            ["Today's briefing headline and summary", 1, 1, 1],
            ['Country files with strategy, structure and stakeholders', 'overview, plus '.config('atlas.free_files_per_month').' a month with a free account', 'all 81', 'all 81'],
            ['Full daily briefing, narration and map playback', 0, 1, 1],
            ['Briefing archive', 0, '21 days', '21 days'],
            ['Programs and consortia tracker', 0, 1, 1],
            ['Ecosystem registry and company engagements', 0, 1, 1],
            ['Watchlist', 'this browser', 'your account', 'yours and shared'],
            ['Compare', 0, 'view', 'view and CSV'],
            ['Shared country notes for the team', 0, 0, 1],
            ['Seat management and one invoice', 0, 0, 1],
            ['Onboarding session with an EMMA analyst', 0, 0, 1],
            ['Support', 'email', 'email', 'priority email'],
          ] as $r)
          <tr>
            <td>{{ $r[0] }}</td>
            @foreach (array_slice($r, 1) as $v)
              <td>@if ($v === 1)<span class="yes" aria-label="Included">&#10003;</span>@elseif ($v === 0)<span class="no" aria-label="Not included">&ndash;</span>@else<span class="muted" style="font-size:13px">{{ $v }}</span>@endif</td>
            @endforeach
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </section>

  <section class="faq" aria-labelledby="faqH">
    <div class="stack">
      <span class="eyebrow">Questions</span>
      <h2 class="h2" id="faqH">Before you subscribe</h2>
      <p class="muted">Anything else: <a href="mailto:{{ config('atlas.contact') }}">{{ config('atlas.contact') }}</a></p>
    </div>
    <div>
      <details open><summary>Why does yearly cost less?</summary><p>Twelve months of Personal billed monthly come to ${{ $p['personal_month'] * 12 }}. Billed yearly they cost ${{ $p['personal_year'] }}, so you save ${{ $p['personal_saving'] }}. Monthly readers can switch to yearly from their account at any time, and Paddle credits the unused part of the current month.</p></details>
      <details><summary>What is the founding rate?</summary><p>If you subscribe by {{ $founding->format('F j, Y') }}, the price you sign up at stays the same for as long as your subscription continues without a break, even when list prices rise.</p></details>
      <details><summary>Who sells the subscription?</summary><p>Paddle.com is our online reseller and the merchant of record. Paddle takes the payment, adds the right VAT or sales tax, issues the invoice and handles refunds. EMMA Aerospace Consulting provides the service.</p></details>
      <details><summary>Can my organization pay by invoice?</summary><p>Yes, for Teams and API billed yearly. Write to us with the number of seats and your billing details and we send an invoice.</p></details>
      <details><summary>Are prices shown with tax?</summary><p>Prices are in US dollars and exclude VAT, GST and sales tax. Paddle adds the tax that applies where you are and shows the full amount before you pay. Businesses in the EU with a valid VAT number are charged without VAT under the reverse charge.</p></details>
      <details><summary>Where does the data come from?</summary><p>From public sources: government and agency documents, official statistics and reputable press, each linked from the record. Research, summaries and the daily briefing are produced with generative AI, and every item carries its sources so you can check it.</p></details>
      <details><summary>Do universities get a discount?</summary><p>Yes. Students and staff of universities and public research institutes get 50% off Personal and Teams. Write to us from your institutional address.</p></details>
    </div>
  </section>

  <p class="legalnote">Prices as of {{ now()->format('F Y') }}, in US dollars, excluding taxes. Paid plans renew automatically until cancelled. The Terms of Service, the Privacy Policy and the refund policy are at <a href="/legal">atlas.emmaconsulting.space/legal</a>. Consumers in the EU, the EEA and the UK may also withdraw within 14 days using the <a href="/withdraw">withdrawal form</a>.</p>
</div>
@endsection
@push('scripts')
<script>
(function () {
  var root = document.getElementById("pr");
  document.querySelectorAll("[data-set]").forEach(function (b) {
    b.addEventListener("click", function () {
      root.setAttribute("data-i", b.dataset.set);
      document.querySelectorAll("[data-set]").forEach(function (x) { x.setAttribute("aria-pressed", String(x === b)); });
    });
  });
})();
</script>
@endpush
