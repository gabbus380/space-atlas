@extends('layouts.site')
@section('title', 'Withdraw from a contract')
@section('content')
<div class="grid2" style="align-items:start">
  <div class="stack">
    <span class="eyebrow">Right of withdrawal</span>
    <h1 class="h2">Withdraw from your contract</h1>
    <p class="lead">Consumers in the EU, the EEA and the UK may withdraw from a Personal plan within 14 days of the contract without giving a reason. Send this form, or write to <a href="mailto:{{ config('atlas.contact') }}">{{ config('atlas.contact') }}</a>. We confirm receipt by email straight away.</p>
    <form class="form" method="POST" action="/withdraw">
      @csrf
      <p style="margin:0">To EMMA Aerospace Consulting LLP, 1103-11871 Horseshoe Way, 2nd Floor, Richmond, BC V7A 5H5, Canada, {{ config('atlas.contact') }}: I hereby give notice that I withdraw from my contract for the provision of the following service: Sovereign Space ATLAS subscription.</p>
      <label>Your name<input type="text" name="name" value="{{ old('name', $user?->name) }}" required></label>
      <label>Email used for the order<input type="email" name="email" value="{{ old('email', $user?->email) }}" required></label>
      <label>Postal address <span class="faint">(optional)</span><input type="text" name="address" value="{{ old('address') }}"></label>
      <label>Order or invoice number <span class="faint">(optional)</span><input type="text" name="order_reference" value="{{ old('order_reference') }}"></label>
      <label>Ordered on <span class="faint">(optional)</span><input type="date" name="ordered_on" value="{{ old('ordered_on') }}"></label>
      <label>Message <span class="faint">(optional)</span><textarea name="message" rows="3">{{ old('message') }}</textarea></label>
      <div class="row"><button class="btn gold" type="submit">Withdraw from the contract</button></div>
    </form>
  </div>
  <aside class="card stack">
    <h2 class="h3">What happens next</h2>
    <p class="muted" style="margin:0">We cancel the plan and Paddle, our reseller, refunds the payment to the method you used within 14 days. If you asked for the service to start during the withdrawal period, we may keep an amount in proportion to the days used. Under our money-back guarantee we refund in full within 14 days of the first payment anyway.</p>
  </aside>
</div>
@endsection
