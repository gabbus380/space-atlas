<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>@yield('title', 'Sovereign Space ATLAS') | EMMA Aerospace Consulting</title>
<meta name="description" content="@yield('description', 'Sovereign Space ATLAS by EMMA Aerospace Consulting: national space programs, country files and a daily space briefing.')">
<meta name="theme-color" content="#070b14">
<meta name="color-scheme" content="dark">
@hasSection('noindex')<meta name="robots" content="noindex">@endif
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="stylesheet" href="/fonts/fonts.css">
<style>
:root{--void:#070b14;--space:#0a101c;--panel:#0c1220;--raise:#151d2e;--line:rgba(255,255,255,.09);--line-2:rgba(255,255,255,.16);--ink:#e6eaf1;--ink-2:#a9b3c3;--ink-3:#8892a4;--gold:#e5bf6d;--gold-soft:rgba(229,191,109,.14);--red:#e0675a;--green:#4fbf8f;--font:"Archivo",system-ui,-apple-system,"Segoe UI",Arial,sans-serif;--serif:"Instrument Serif",Georgia,"Times New Roman",serif;--mono:"JetBrains Mono",ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;color-scheme:dark}
*{box-sizing:border-box}
html{-webkit-text-size-adjust:100%}
body{margin:0;background:var(--void);color:var(--ink);font:15px/1.55 var(--font);min-height:100vh;display:flex;flex-direction:column}
a{color:var(--gold)}a:hover{color:#f2d79a}
img,svg{max-width:100%}
[hidden]{display:none!important}
.wrap{width:100%;max-width:1160px;margin:0 auto;padding-left:max(20px,env(safe-area-inset-left,0px));padding-right:max(20px,env(safe-area-inset-right,0px))}
.top{border-bottom:1px solid var(--line);background:rgba(7,11,20,.92);position:sticky;top:0;z-index:20;backdrop-filter:blur(8px)}
.top .wrap{display:flex;align-items:center;gap:20px;min-height:64px;padding-top:env(safe-area-inset-top,0px)}
.brand{display:flex;align-items:center;gap:14px;text-decoration:none;color:var(--ink);min-width:0}
.brand svg{height:24px;width:auto;flex:none}
.brand span{font-family:var(--serif);font-size:20px;white-space:nowrap;border-left:1px solid var(--line-2);padding-left:14px}
.brand em{color:var(--gold)}
.nav{margin-left:auto;display:flex;align-items:center;gap:6px;flex-wrap:wrap;justify-content:flex-end}
.nav a{font-family:var(--mono);font-size:11.5px;letter-spacing:.12em;text-transform:uppercase;color:var(--ink-2);text-decoration:none;padding:8px 10px;border-radius:2px}
.nav a:hover,.nav a[aria-current="page"]{color:var(--ink)}
.nav form{margin:0}
.eyebrow{font-family:var(--mono);font-size:11.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--gold)}
.muted{color:var(--ink-2)}.faint{color:var(--ink-3)}
h1,h2,h3{font-weight:400;margin:0}
.display{font-family:var(--serif);font-size:clamp(38px,6vw,64px);line-height:1.02;text-wrap:balance}
.h2{font-family:var(--serif);font-size:clamp(28px,3.4vw,38px);line-height:1.08;text-wrap:balance}
.h3{font-family:var(--serif);font-size:24px;line-height:1.15}
.lead{font-size:17px;line-height:1.6;color:var(--ink-2);max-width:62ch;margin:0}
main{flex:1 0 auto;padding:48px 0 72px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid var(--line-2);background:transparent;color:var(--ink);font:500 14px/1 var(--font);padding:12px 18px;border-radius:2px;cursor:pointer;text-decoration:none;white-space:nowrap}
.btn:hover{border-color:var(--ink-2);color:var(--ink)}
.btn.gold{background:var(--gold);border-color:var(--gold);color:#070b14;font-weight:600}
.btn.gold:hover{filter:brightness(1.07);color:#070b14}
.btn.sm{padding:8px 12px;font-size:13px}
.btn.link{border-color:transparent;padding-left:0;padding-right:0;color:var(--gold)}
.btn.danger{border-color:rgba(224,103,90,.5);color:#f0a59c}
.btn[disabled]{opacity:.5;cursor:not-allowed}
.card{background:var(--panel);border:1px solid var(--line);border-radius:4px;padding:24px}
.stack{display:flex;flex-direction:column;gap:16px}
.row{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
.grid2{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,420px),1fr));gap:20px}
.flash{border:1px solid rgba(79,191,143,.4);background:rgba(79,191,143,.08);color:#bfe9d6;padding:12px 16px;border-radius:3px;margin-bottom:24px}
.errors{border:1px solid rgba(224,103,90,.45);background:rgba(224,103,90,.08);color:#f3c1bb;padding:12px 16px;border-radius:3px;margin-bottom:20px}
.errors ul{margin:0;padding-left:18px}
label{display:flex;flex-direction:column;gap:6px;font-size:13px;color:var(--ink-2)}
input[type=text],input[type=email],input[type=password],input[type=number],input[type=date],input[type=search],input[type=url],select,textarea{width:100%;background:var(--space);border:1px solid var(--line-2);border-radius:2px;color:var(--ink);font:15px/1.4 var(--font);padding:11px 12px;color-scheme:dark}
select{appearance:none;cursor:pointer;padding-right:40px;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5 6 6.5 11 1.5' fill='none' stroke='%23e5bf6d' stroke-width='1.6' stroke-linecap='square'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center}
select option,select optgroup{background:var(--panel);color:var(--ink)}
select option:checked{background:var(--raise);color:var(--gold)}
input:focus,select:focus,textarea:focus,.btn:focus-visible,a:focus-visible{outline:2px solid var(--gold);outline-offset:2px}
.check{flex-direction:row;align-items:flex-start;gap:10px;font-size:13.5px;line-height:1.5;color:var(--ink-2)}
.check input{appearance:none;width:16px;height:16px;margin-top:2px;flex:none;border:1px solid var(--line-2);background:var(--space);border-radius:2px;display:grid;place-content:center}
.check input[type=radio]{border-radius:50%}
.check input:checked{border-color:var(--gold);background:var(--gold-soft)}
.check input[type=checkbox]:checked::after{content:"";width:8px;height:5px;border-left:2px solid var(--gold);border-bottom:2px solid var(--gold);transform:rotate(-45deg) translateY(-1px)}
.check input[type=radio]:checked::after{content:"";width:8px;height:8px;border-radius:50%;background:var(--gold)}
.picker{position:relative}
.picker-list{position:absolute;z-index:30;left:0;right:0;top:calc(100% + 4px);margin:0;padding:0;list-style:none;background:var(--panel);border:1px solid var(--line-2);border-radius:2px;max-height:240px;overflow:auto;box-shadow:0 16px 40px rgba(0,0,0,.45)}
.picker-list button{display:flex;flex-direction:column;align-items:flex-start;gap:2px;width:100%;text-align:left;background:transparent;color:var(--ink);border:0;border-bottom:1px solid var(--line);font:15px/1.35 var(--font);padding:10px 12px;cursor:pointer}
.picker-list li:last-child button{border-bottom:0}
.picker-list button small{color:var(--ink-3);font-size:12.5px}
.picker-list button:hover,.picker-list button[aria-selected="true"]{background:var(--raise);color:var(--gold)}
.picker-list button:hover small,.picker-list button[aria-selected="true"] small{color:var(--ink-2)}
.form{display:flex;flex-direction:column;gap:16px;max-width:460px}
.auth{max-width:460px;margin:0 auto;display:flex;flex-direction:column;gap:22px}
table.t{width:100%;border-collapse:collapse;font-size:14px}
table.t th,table.t td{text-align:left;padding:10px 8px;border-bottom:1px solid var(--line);vertical-align:top}
table.t th{font-family:var(--mono);font-weight:400;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:var(--ink-3)}
.tw{overflow-x:auto}
.pill{display:inline-block;font-family:var(--mono);font-size:11px;letter-spacing:.08em;text-transform:uppercase;padding:3px 8px;border-radius:999px;border:1px solid var(--line-2);color:var(--ink-2)}
.pill.gold{border-color:rgba(229,191,109,.5);color:var(--gold);background:var(--gold-soft)}
.pill.green{border-color:rgba(79,191,143,.5);color:#8fe0bd}
.pill.red{border-color:rgba(224,103,90,.5);color:#f0a59c}
.kv{display:grid;grid-template-columns:160px minmax(0,1fr);gap:8px 16px;font-size:14px}
.kv dt{color:var(--ink-3)}.kv dd{margin:0}
footer.foot{border-top:1px solid var(--line);padding:28px 0 calc(28px + env(safe-area-inset-bottom,0px));font-size:13px;color:var(--ink-3)}
footer.foot .wrap{display:flex;flex-wrap:wrap;gap:12px 28px;justify-content:space-between}
footer.foot nav{display:flex;flex-wrap:wrap;gap:8px 18px}
footer.foot a{color:var(--ink-2);text-decoration:none}footer.foot a:hover{color:var(--ink)}
.id-stack{display:flex;flex-direction:column;gap:10px;max-width:400px}
.id-note{margin:0;color:var(--ink-2);font-size:13.5px;line-height:1.5}
.idbtn{display:flex;align-items:center;justify-content:center;gap:12px;width:100%;min-height:44px;padding:0 16px;box-sizing:border-box;text-decoration:none;font-size:15px;font-weight:600;letter-spacing:0;line-height:1}
.idbtn svg{width:18px;height:18px;flex:none}
.idbtn.apple{background:#fff;color:#000;border-radius:8px}
.idbtn.apple:hover{background:#f2f2f2;color:#000}
.idbtn.google{background:#131314;color:#e3e3e3;border:1px solid #8e918f;border-radius:4px;font-weight:500;font-family:Roboto,Arial,sans-serif}
.idbtn.google:hover{background:#1f1f1f;color:#e3e3e3}
.idbtn.microsoft{background:#fff;color:#5e5e5e;border:1px solid #8c8c8c;border-radius:2px;font-family:"Segoe UI",system-ui,sans-serif}
.idbtn.microsoft:hover{background:#f3f3f3;color:#5e5e5e}
.idbtn.linkedin{background:#0a66c2;color:#fff;border-radius:2px}
.idbtn.linkedin:hover{background:#004182;color:#fff}
.cookie{position:relative;z-index:5;width:auto;background:var(--panel);border-bottom:1px solid var(--line-2);padding:14px 0;display:flex;flex-direction:row;align-items:center;justify-content:space-between;gap:16px}
.cookie p{margin:0;color:var(--ink-2);font-size:14px;line-height:1.5}
.cookie .btn{flex:none;min-height:44px}
body.sq,body.sq *,body.sq *::before,body.sq *::after{border-radius:0!important}
@media (max-width:1100px){.wrap{padding-left:max(16px,env(safe-area-inset-left));padding-right:max(16px,env(safe-area-inset-right))}}
@media (max-width:720px){
  .brand span{display:none}
  .top .wrap{flex-wrap:wrap;gap:4px 10px;min-height:56px;padding-top:8px;padding-bottom:8px}
  .nav{width:100%;justify-content:flex-start;gap:0}
  .nav a{min-height:44px;display:inline-flex;align-items:center;padding:8px 10px}
  main{padding:28px 0 72px}
  .kv{grid-template-columns:minmax(0,1fr)}
  .kv dt{margin-top:6px}
  .card{padding:16px}
  input,select,textarea{font-size:16px}
  .btn{min-height:44px}
  .h2{font-size:32px}
  .form,.id-stack{max-width:none}
  .cookie{flex-direction:column;align-items:stretch}
  .cookie .btn{align-self:flex-start}
}
@yield('style')
</style>
@stack('head')
</head>
<body @if(request()->is('admin*')) class="sq" @endif>
<header class="top">
  <div class="wrap">
    <a class="brand" href="/" aria-label="Sovereign Space ATLAS, open the atlas">
      <svg viewBox="-8 0 448 120" aria-hidden="true"><circle cx="60" cy="60" r="42" fill="none" stroke="#e6eaf1" stroke-width="12"/><g transform="translate(18 60) scale(0.42)"><rect x="-24" y="-76" width="90" height="152" fill="#070b14"/><polygon points="0,-64 6,-52 10,-34 10,-6 18,10 46,38 46,48 14,48 14,56 6,60 -6,60 -14,56 -14,48 -46,48 -46,38 -18,10 -10,-6 -10,-34 -6,-52" fill="#e5bf6d"/></g><g fill="#e6eaf1" transform="translate(140 32) scale(0.63636) translate(-224 -152)"><path d="M224 152H296V172H244V186H288V206H244V220H296V240H224Z"/><path d="M336 152H360L380 190L400 152H424V240H404V187.3L380 232.9L356 187.3V240H336Z"/><path d="M464 152H488L508 190L528 152H552V240H532V187.3L508 232.9L484 187.3V240H464Z"/><path fill-rule="evenodd" d="M592 240L618 152H646L672 240H651L645.09 220H618.91L613 240Z M624.82 200H639.18L632 175.7Z"/></g></svg>
      <span>Sovereign Space <em>ATLAS</em></span>
    </a>
    <nav class="nav" aria-label="Site">
      <a href="/">Atlas</a>
      <a href="/pricing" @if(request()->is('pricing')) aria-current="page" @endif>Plans</a>
      @auth
        @if(auth()->user()->team_id)<a href="/team" @if(request()->is('team')) aria-current="page" @endif>Team</a>@endif
        @if(auth()->user()->staffRole())<a href="/admin" @if(request()->is('admin*')) aria-current="page" @endif>Admin</a>@endif
        <a href="/account" @if(request()->is('account')) aria-current="page" @endif>Account</a>
      @else
        <a href="/login" @if(request()->is('login')) aria-current="page" @endif>Sign in</a>
      @endauth
    </nav>
  </div>
</header>
@include('partials.cookie-notice')
<main>
  <div class="wrap">
    @if (request()->hasSession() && session('status'))<div class="flash" role="status">{{ session('status') }}</div>@endif
    @if (isset($errors) && $errors->any())<div class="errors" role="alert"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    @yield('content')
  </div>
</main>
<footer class="foot">
  <div class="wrap">
    <span>&copy; {{ date('Y') }} EMMA Aerospace Consulting LLP. All rights reserved. Richmond, BC, Canada.</span>
    <nav aria-label="Legal">
      <a href="/legal#terms">Terms</a>
      <a href="/legal#privacy">Privacy</a>
      <a href="/legal#refunds">Refunds</a>
      <a href="/withdraw">Withdraw from a contract</a>
      <a href="/legal#imprint">Imprint</a>
      <a href="mailto:{{ config('atlas.contact') }}">{{ config('atlas.contact') }}</a>
    </nav>
  </div>
</footer>
@stack('scripts')
</body>
</html>
