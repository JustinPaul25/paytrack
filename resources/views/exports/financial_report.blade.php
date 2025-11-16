<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #cccccc; padding: 8px; text-align: right; }
        th:first-child, td:first-child { text-align: left; }
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
        .logo { height: 40px; }
        .title { font-weight: bold; font-size: 14px; }
        .muted { color: #666; font-size: 12px; }
        .totals th { background: #f0f0f0; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ asset('img/merakilogo.jpg') }}" alt="Meraki" class="logo">
        <div style="text-align:right">
            <div class="title">Financial Report</div>
            <div class="muted">Generated: {{ now()->format('Y-m-d H:i') }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th>Income (₱)</th>
                <th>Expenses (₱)</th>
                <th>Net (₱)</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td>{{ number_format($row['income'], 2, '.', ',') }}</td>
                <td>{{ number_format($row['expenses'], 2, '.', ',') }}</td>
                <td>{{ number_format($row['net'], 2, '.', ',') }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr class="totals">
                <th>Totals</th>
                <th>{{ number_format($totals['income'], 2, '.', ',') }}</th>
                <th>{{ number_format($totals['expenses'], 2, '.', ',') }}</th>
                <th>{{ number_format($totals['net'], 2, '.', ',') }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>

