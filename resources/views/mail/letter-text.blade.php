{{ $letter->eyebrow }}
{{ $letter->title }}

{{ $letter->greeting }}

@foreach ($letter->paragraphs as $paragraph)
{{ $paragraph }}

@endforeach
@foreach ($letter->facts as $fact)
{{ $fact['label'] }}: {{ $fact['value'] }}
@endforeach
@if ($letter->code)

Key, shown once:
{{ $letter->code }}
@endif
@foreach ($letter->items as $item)
@if ($item['meta'] !== ''){{ $item['meta'] }}
@endif
{{ $item['title'] }}
{{ $item['text'] }}

@endforeach
@if ($letter->actionUrl)
{{ $letter->actionLabel }}: {{ $letter->actionUrl }}
@endif
@if ($letter->note)

{{ $letter->note }}
@endif

@foreach ($letter->legalLines() as $line)
{{ $line }}
@endforeach

Terms: {{ url('/legal#terms') }}
Privacy: {{ url('/legal#privacy') }}
Refunds: {{ url('/legal#refunds') }}
Withdraw from a contract: {{ url('/withdraw') }}
Imprint: {{ url('/legal#imprint') }}
@if ($letter->unsubscribeUrl)

Unsubscribe: {{ $letter->unsubscribeUrl }}
@endif

© {{ date('Y') }} EMMA Aerospace Consulting LLP
