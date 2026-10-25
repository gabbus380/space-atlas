<div class="stack" style="gap:10px">
  <p class="muted" style="margin:0;font-size:13px">Or continue with the account you already use. Apple is the Apple ID on your iCloud account. Continuing accepts the <a href="/legal#terms">Terms of Service</a>.</p>
  <div class="row">
    @foreach (['apple' => 'Apple', 'google' => 'Google', 'microsoft' => 'Microsoft', 'linkedin' => 'LinkedIn'] as $key => $label)
      <a class="btn sm" href="/login/{{ $key }}">{{ $label }}</a>
    @endforeach
  </div>
</div>
