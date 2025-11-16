<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund request submitted</title>
    <style>
        body { font-family: -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif; color:#111827; }
        .container { max-width: 600px; margin: 0 auto; padding: 24px; }
        .card { border:1px solid #e5e7eb; border-radius: 8px; padding: 20px; }
        .h1 { font-size: 20px; font-weight: 700; margin: 0 0 12px; }
        .muted { color:#6b7280; }
        .badge { display:inline-block; padding:2px 8px; border-radius:9999px; background:#eef2ff; color:#4338ca; font-size:12px; font-weight:600; }
        .row { margin: 10px 0; }
        .label { font-weight:600; }
        .footer { margin-top: 16px; font-size: 12px; color:#6b7280; }
    </style>
    </head>
<body>
    <div class="container">
        <p class="muted">Hello {{ $refundRequest->customer_name }},</p>
        <div class="card">
            <h1 class="h1">We received your refund request</h1>
            <p>Use this tracking number to follow up with our staff:</p>
            <p class="badge">{{ $refundRequest->tracking_number }}</p>

            <div class="row"><span class="label">Invoice reference:</span> {{ $refundRequest->invoice_reference ?? '—' }}</div>
            <div class="row"><span class="label">Quantity:</span> {{ $refundRequest->quantity }}</div>
            <div class="row"><span class="label">Requested amount:</span>
                @if(!is_null($refundRequest->amount_requested))
                    ₱{{ number_format($refundRequest->amount_requested / 100, 2) }}
                @else
                    —
                @endif
            </div>
            <div class="row"><span class="label">Reason:</span> {{ $refundRequest->reason ?? '—' }}</div>
        </div>
        <p style="margin-top:16px;">
            <a href="{{ url('/refund-track?tracking=' . $refundRequest->tracking_number) }}" style="display:inline-block;padding:10px 16px;border-radius:6px;background:#4f46e5;color:#fff;text-decoration:none;font-weight:600">
                Track this refund
            </a>
        </p>
        <p class="footer">If you didn’t make this request, you can ignore this message.</p>
    </div>
</body>
</html>


