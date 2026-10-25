@extends('layouts.site')
@section('title', 'Withdrawal received')
@section('noindex', '1')
@section('content')
<div class="auth">
  <span class="eyebrow">Received</span>
  <h1 class="h2">We have your withdrawal</h1>
  <p class="lead">Received on {{ $w->created_at->timezone('UTC')->format('F j, Y') }} at {{ $w->created_at->timezone('UTC')->format('H:i') }} UTC. A confirmation is on its way to {{ $w->email }}. The refund follows within 14 days.</p>
  <a class="btn" href="/">Back to the atlas</a>
</div>
@endsection
