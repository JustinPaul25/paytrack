<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund request update</title>
    <style>
        body { font-family: -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif; color:#111827; }
        .container { max-width: 600px; margin: 0 auto; padding: 24px; }
        .card { border:1px solid #e5e7eb; border-radius: 8px; padding: 20px; }
        .h1 { font-size: 20px; font-weight: 700; margin: 0 0 12px; }
        .muted { color:#6b7280; }
        .badge { display:inline-block; padding:2px 8px; border-radius:9999px; background:#ecfeff; color:#0891b2; font-size:12px; font-weight:600; }
        .row { margin: 10px 0; }
        .label { font-weight:600; }
        .footer { margin-top: 16px; font-size: 12px; color:#6b7280; }
    </style>
</head>
<body>
<div class="container">
    <p class="muted">Hello {{ $refundRequest->customer_name }},</p>

    <div class="card">
        <h1 class="h1">
            @if($decision === 'approved')
                Good news! Your refund request was approved
            @else
                Update on your refund request
            @endif
        </h1>

        <div class="row"><span class="label">Tracking:</span> {{ $refundRequest->tracking_number }}</div>
        <div class="row"><span class="label">Invoice:</span> {{ $refundRequest->invoice_reference ?? ('#' . $refundRequest->invoice_id) }}</div>
        @if($refundRequest->product)
            <div class="row"><span class="label">Product:</span> {{ $refundRequest->product->name }}</div>
        @endif
        <div class="row"><span class="label">Quantity:</span> {{ $refundRequest->quantity }}</div>
        @if(!empty($refundRequest->reason))
            <div class="row"><span class="label">Your description:</span> {{ $refundRequest->reason }}</div>
        @endif
        @if(!empty($refundRequest->review_notes))
            <div class="row"><span class="label">Notes from our team:</span> {{ $refundRequest->review_notes }}</div>
        @endif
        <div class="row">
            <span class="label">Status:</span>
            <span class="badge">{{ $decision }}</span>
        </div>
    </div>

    <p style="margin-top:16px;" class="muted">
        If you have any questions, just reply to this email.
    </p>
    <p class="footer">Thank you.</p>
</div>
</body>
</html>


