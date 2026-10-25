<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="dark">
<meta name="supported-color-schemes" content="dark">
<title>{{ $letter->subject }}</title>
</head>
<body style="margin:0;padding:0;background:#070b14;color:#e6eaf1;">
<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">{{ $letter->preview() }}</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#070b14" style="background:#070b14;margin:0;padding:0;">
<tr>
<td align="center" style="padding:28px 12px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" bgcolor="#070b14" style="width:100%;max-width:600px;background:#070b14;">
<tr>
<td style="padding:8px 8px 20px;">
<img src="{{ $letter->logoUrl() }}" width="36" height="36" alt="" style="display:inline-block;vertical-align:middle;border:0;border-radius:8px;">
<span style="display:inline-block;vertical-align:middle;margin-left:12px;padding-left:12px;border-left:1px solid rgba(255,255,255,.16);font-family:Georgia,'Times New Roman',serif;font-size:20px;line-height:36px;color:#e6eaf1;">Sovereign Space <span style="color:#e5bf6d;">ATLAS</span></span>
</td>
</tr>
<tr>
<td bgcolor="#e5bf6d" style="height:2px;background:#e5bf6d;font-size:0;line-height:0;">&nbsp;</td>
</tr>
<tr>
<td style="padding:28px 8px 8px;">
<p style="margin:0 0 12px;font-family:Consolas,Menlo,monospace;font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:#e5bf6d;">{{ $letter->eyebrow }}</p>
<h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-weight:400;font-size:32px;line-height:1.15;color:#e6eaf1;">{{ $letter->title }}</h1>
<p style="margin:0 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.6;color:#e6eaf1;">{{ $letter->greeting }}</p>
@foreach ($letter->paragraphs as $paragraph)
<p style="margin:0 0 14px;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.6;color:#a9b3c3;">{{ $paragraph }}</p>
@endforeach
</td>
</tr>
@if ($letter->facts !== [])
<tr>
<td style="padding:4px 8px 8px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#0c1220" style="background:#0c1220;border:1px solid rgba(255,255,255,.09);">
@foreach ($letter->facts as $fact)
<tr>
<td style="padding:10px 14px;border-bottom:1px solid rgba(255,255,255,.09);font-family:Consolas,Menlo,monospace;font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#8892a4;width:34%;vertical-align:top;">{{ $fact['label'] }}</td>
<td style="padding:10px 14px;border-bottom:1px solid rgba(255,255,255,.09);font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.45;color:#e6eaf1;vertical-align:top;">{{ $fact['value'] }}</td>
</tr>
@endforeach
</table>
</td>
</tr>
@endif
@if ($letter->code)
<tr>
<td style="padding:8px;">
<p style="margin:0 0 8px;font-family:Consolas,Menlo,monospace;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#e5bf6d;">Key, shown once</p>
<p style="margin:0;padding:14px;background:#0a101c;border:1px solid rgba(229,191,109,.45);font-family:Consolas,Menlo,monospace;font-size:13px;line-height:1.5;color:#e6eaf1;word-break:break-all;">{{ $letter->code }}</p>
</td>
</tr>
@endif
@if ($letter->items !== [])
<tr>
<td style="padding:8px;">
@foreach ($letter->items as $item)
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 10px;background:#0c1220;border:1px solid rgba(255,255,255,.09);">
<tr>
<td style="padding:14px 16px;">
@if ($item['meta'] !== '')
<p style="margin:0 0 6px;font-family:Consolas,Menlo,monospace;font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#e5bf6d;">{{ $item['meta'] }}</p>
@endif
<p style="margin:0 0 6px;font-family:Georgia,'Times New Roman',serif;font-size:18px;line-height:1.3;color:#e6eaf1;">{{ $item['title'] }}</p>
@if ($item['text'] !== '')
<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.55;color:#a9b3c3;">{{ $item['text'] }}</p>
@endif
</td>
</tr>
</table>
@endforeach
</td>
</tr>
@endif
@if ($letter->actionUrl && $letter->actionLabel)
<tr>
<td style="padding:18px 8px 8px;">
<table role="presentation" cellpadding="0" cellspacing="0">
<tr>
<td bgcolor="#e5bf6d" style="background:#e5bf6d;border-radius:2px;">
<a href="{{ $letter->actionUrl }}" style="display:inline-block;padding:14px 22px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;line-height:1;color:#070b14;text-decoration:none;">{{ $letter->actionLabel }}</a>
</td>
</tr>
</table>
</td>
</tr>
@endif
@if ($letter->note)
<tr>
<td style="padding:8px 8px 0;">
<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.55;color:#a9b3c3;">{{ $letter->note }}</p>
</td>
</tr>
@endif
<tr>
<td style="padding:28px 8px 8px;">
<p style="margin:0 0 12px;border-top:1px solid rgba(255,255,255,.09);font-size:0;line-height:0;">&nbsp;</p>
@foreach ($letter->legalLines() as $line)
<p style="margin:0 0 8px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;color:#a9b3c3;">{{ $line }}</p>
@endforeach
<p style="margin:12px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;">
<a href="{{ url('/legal#terms') }}" style="color:#e5bf6d;text-decoration:underline;">Terms</a>
&nbsp;&middot;&nbsp;
<a href="{{ url('/legal#privacy') }}" style="color:#e5bf6d;text-decoration:underline;">Privacy</a>
&nbsp;&middot;&nbsp;
<a href="{{ url('/legal#refunds') }}" style="color:#e5bf6d;text-decoration:underline;">Refunds</a>
&nbsp;&middot;&nbsp;
<a href="{{ url('/withdraw') }}" style="color:#e5bf6d;text-decoration:underline;">Withdraw from a contract</a>
&nbsp;&middot;&nbsp;
<a href="{{ url('/legal#imprint') }}" style="color:#e5bf6d;text-decoration:underline;">Imprint</a>
</p>
@if ($letter->unsubscribeUrl)
<p style="margin:12px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;color:#a9b3c3;">
<a href="{{ $letter->unsubscribeUrl }}" style="color:#e5bf6d;text-decoration:underline;">Unsubscribe</a>
from {{ $letter->kind === 'briefing' ? 'the morning briefing' : 'the monthly note' }}.
You can also change this on your <a href="{{ url('/account') }}" style="color:#e5bf6d;text-decoration:underline;">account page</a>.
</p>
@endif
<p style="margin:14px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.5;color:#8892a4;">&copy; {{ date('Y') }} EMMA Aerospace Consulting LLP</p>
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
