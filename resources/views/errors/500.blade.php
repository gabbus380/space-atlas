@extends('layouts.site')
@section('title', 'Something went wrong')
@section('noindex', '1')
@section('content')
<div class="auth"><span class="eyebrow">Error 500</span><h1 class="h2">Something went wrong</h1><p class="lead">We have been told. Try again in a few minutes.</p><div class="row"><a class="btn" href="/">Open the atlas</a><a class="btn link" href="/pricing">See plans</a></div></div>
@endsection
