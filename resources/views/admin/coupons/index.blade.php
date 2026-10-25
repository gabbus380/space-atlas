@extends('layouts.site')
@section('title', 'Coupons')
@section('noindex', '1')
@section('content')
<div class="stack" style="gap:8px"><span class="eyebrow">EMMA staff</span><h1 class="h2">Coupons</h1>
  <p class="muted" style="margin:0">A code grants free time: one month, three months, or the first year. The reader enters it on their account page.</p>
</div>
@include('admin.nav')
<div class="grid2" style="margin-top:16px;align-items:start">
  <form class="card form" method="POST" action="/admin/coupons">@csrf
    <h2 class="h3">New coupon</h2>
    <label>Code<input type="text" name="code" required maxlength="40" pattern="[A-Za-z0-9-]+" autocomplete="off"></label>
    <label>Name<input type="text" name="name" required placeholder="Launch partner, first year"></label>
    <label>Free period
      <select name="months">
        <option value="1">1 month free</option>
        <option value="3">3 months free</option>
        <option value="12" selected>First year free</option>
      </select>
    </label>
    <label>Plan
      <select name="plan">
        <option value="personal">Personal</option>
        <option value="teams">Teams</option>
      </select>
    </label>
    <label>Maximum uses <span class="faint">(blank for no limit)</span><input type="number" name="max_redemptions" min="1"></label>
    <label>Expires on<input type="date" name="expires_on"></label>
    <button class="btn sm" type="submit">Create</button>
  </form>
  <div class="tw"><table class="t">
    <thead><tr><th>Code</th><th>Offer</th><th>Used</th><th></th></tr></thead>
    <tbody>
    @forelse ($coupons as $coupon)
      <tr>
        <td><code>{{ $coupon->code }}</code><br><span class="faint" style="font-size:12.5px">{{ $coupon->name }}</span></td>
        <td>{{ $coupon->label() }} · {{ ucfirst($coupon->plan) }}@if($coupon->expires_on)<br><span class="faint" style="font-size:12.5px">until {{ $coupon->expires_on->format('M j, Y') }}</span>@endif</td>
        <td>{{ $coupon->times_redeemed }}{{ $coupon->max_redemptions ? ' / '.$coupon->max_redemptions : '' }}</td>
        <td><form method="POST" action="/admin/coupons/{{ $coupon->id }}/toggle">@csrf<button class="btn sm" type="submit">{{ $coupon->active ? 'Switch off' : 'Switch on' }}</button></form></td>
      </tr>
    @empty
      <tr><td colspan="4" class="faint">None yet.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div>
@endsection
