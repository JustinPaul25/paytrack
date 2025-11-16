<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        /* Simple, print-friendly table (no colors) */
        body { margin: 0; padding: 0; font-family: sans-serif; font-size: 12px; color: #000; }
        h1 { margin: 0 0 10px 0; font-size: 16px; font-weight: 700; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 8px; text-align: right; }
        th:first-child, td:first-child { text-align: left; }
        tfoot th { font-weight: 700; }
        /* Remove decorative colors/backgrounds */
        .header, .logo, .muted { display: none; }
    </style>
</head>
<body>
    <h1>Financial Report</h1>
    <div>Generated: {{ now()->format('Y-m-d H:i') }}</div>
    <div style="height:8px"></div>

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

