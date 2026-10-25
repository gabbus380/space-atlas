@extends('layouts.site')
@section('title', 'Request received')
@section('noindex', '1')
@section('content')
<div class="stack" style="gap:12px;max-width:640px">
  <span class="eyebrow">API</span>
  <h1 class="h2">We have your request</h1>
  <p class="lead">Sales will review it and send the Order Form to {{ auth()->user()?->email ?: 'the address you gave' }}. Nothing is charged until the form is signed.</p>
  <div class="row"><a class="btn" href="/account">Open your account</a><a class="btn link" href="/">Back to the atlas</a></div>
</div>
@endsection
