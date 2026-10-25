<nav class="nav" aria-label="Admin" style="margin:18px 0 8px">
  @foreach ($adminNav as $href => $label)
    <a href="{{ $href }}" @if(request()->is(trim($href, '/'))) aria-current="page" @endif>{{ $label }}</a>
  @endforeach
</nav>
