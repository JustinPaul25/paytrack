<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Payment Reminder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #8f5be8;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
        }
        .invoice-details {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .due-date {
            font-size: 18px;
            font-weight: bold;
            color: {{ $daysUntilDue < 0 ? '#dc2626' : ($daysUntilDue === 0 ? '#f59e0b' : '#059669') }};
            margin: 15px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #8f5be8;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Invoice Payment Reminder</h1>
    </div>
    
    <div class="content">
        <p>Dear {{ $invoice->customer->name }},</p>
        
        @if($daysUntilDue < 0)
            <p><strong>This is a reminder that your invoice payment is overdue.</strong></p>
        @elseif($daysUntilDue === 0)
            <p><strong>This is a reminder that your invoice payment is due today.</strong></p>
        @else
            <p><strong>This is a reminder that your invoice payment is due in {{ $daysUntilDue }} day(s).</strong></p>
        @endif
        
        <div class="invoice-details">
            <h3>Invoice Details:</h3>
            <p><strong>Invoice Number:</strong> {{ $invoice->reference_number }}</p>
            <p><strong>Amount Due:</strong> ₱{{ number_format($invoice->total_amount / 100, 2) }}</p>
            <p><strong>Due Date:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('F d, Y') }}</p>
            @if($invoice->payment_method)
                <p><strong>Payment Method:</strong> {{ ucfirst(str_replace('_', ' ', $invoice->payment_method)) }}</p>
            @endif
            @if($daysUntilDue < 0)
                <p class="due-date">⚠️ Overdue by {{ abs($daysUntilDue) }} day(s)</p>
            @elseif($daysUntilDue === 0)
                <p class="due-date">⚠️ Due Today</p>
            @else
                <p class="due-date">Due in {{ $daysUntilDue }} day(s)</p>
            @endif
        </div>
        
        <p>Please ensure payment is made by the due date to avoid any late fees or service interruptions.</p>
        
        <p>If you have already made the payment, please disregard this email.</p>
        
        <p>Thank you for your business!</p>
        
        <p>Best regards,<br>{{ config('app.name') }}</p>
    </div>
    
    <div class="footer">
        <p>This is an automated reminder. Please do not reply to this email.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>

