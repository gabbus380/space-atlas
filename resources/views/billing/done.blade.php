@extends('layouts.site')
@section('title', 'Payment received')
@section('noindex', '1')
@section('content')
<div class="auth" style="max-width:560px">
  <span class="eyebrow">Thank you</span>
  <h1 class="h2" id="h">Setting up your plan</h1>
  <p class="lead" id="t">Paddle confirms the payment to us within a few seconds. This page opens the atlas when your plan is active.</p>
  <div class="row"><a class="btn gold" href="/" id="go">Open the atlas</a><a class="btn link" href="/account">Your account</a></div>
</div>
@endsection
@push('scripts')
<script>
(function () {
  var n = 0, h = document.getElementById("h"), t = document.getElementById("t");
  function poll() {
    fetch("/app/me", { headers: { Accept: "application/json" }, credentials: "same-origin" }).then(function (r) { return r.json(); }).then(function (j) {
      if (j.tier === "personal" || j.tier === "teams") { h.textContent = "Your plan is active"; t.textContent = "Every country file, the full briefing and every tracker are open."; setTimeout(function () { location.href = j.tier === "teams" ? "/team" : "/"; }, 1200); return; }
      if (++n < 40) setTimeout(poll, 2000); else t.textContent = "This is taking longer than usual. Your receipt from Paddle is on its way. If the plan does not show within a few minutes, write to {{ config('atlas.contact') }}.";
    }).catch(function () { if (++n < 40) setTimeout(poll, 3000); });
  }
  poll();
})();
</script>
@endpush
