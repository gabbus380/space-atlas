@extends('layouts.site')
@section('title', 'Withdrawals')
@section('noindex', '1')
@section('content')
<div class="stack" style="gap:8px"><span class="eyebrow">EMMA staff</span><h1 class="h2">Withdrawal requests</h1></div>
@include('admin.nav')
<div class="stack" style="margin-top:16px">
@forelse ($withdrawals as $withdrawal)
  <section class="card stack">
    <div class="row" style="justify-content:space-between">
      <strong>{{ $withdrawal->name }}</strong>
      <span class="pill {{ $withdrawal->status === 'done' ? 'green' : '' }}">{{ $withdrawal->status }}</span>
    </div>
    <p class="muted" style="margin:0">{{ $withdrawal->email }} · {{ $withdrawal->created_at->format('M j, Y') }}
      @if($withdrawal->order_reference) · {{ $withdrawal->order_reference }}@endif
      @if($withdrawal->ordered_on) · ordered {{ $withdrawal->ordered_on->format('M j, Y') }}@endif
    </p>
    @if($withdrawal->address)<p style="margin:0">{{ $withdrawal->address }}</p>@endif
    @if($withdrawal->message)<p style="margin:0">{{ $withdrawal->message }}</p>@endif
    <form class="form" method="POST" action="/admin/withdrawals/{{ $withdrawal->id }}">@csrf
      <label>Status
        <select name="status">
          @foreach (['open' => 'Open', 'done' => 'Done', 'declined' => 'Declined'] as $value => $label)
            <option value="{{ $value }}" @selected($withdrawal->status === $value)>{{ $label }}</option>
          @endforeach
        </select>
      </label>
      <label>Note<textarea name="staff_note" rows="2">{{ $withdrawal->staff_note }}</textarea></label>
      <button class="btn sm" type="submit">Save</button>
    </form>
  </section>
@empty
  <p class="faint">No withdrawal requests yet.</p>
@endforelse
</div>
<div style="margin-top:16px">{{ $withdrawals->links() }}</div>
@endsection
