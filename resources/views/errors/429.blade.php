@extends('layouts.site')
@section('title', 'Too many requests')
@section('noindex', '1')
@section('content')
<div class="auth"><span class="eyebrow">Error 429</span><h1 class="h2">Too many requests</h1><p class="lead">Wait a minute and try again.</p><div class="row"><a class="btn" href="/">Open the atlas</a><a class="btn link" href="/pricing">See plans</a></div></div>
@endsection
