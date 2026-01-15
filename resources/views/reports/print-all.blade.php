<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Comprehensive Reports - PayTrack</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            line-height: 1.4;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        
        .header h1 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .header .date-range {
            font-size: 12px;
            color: #333;
        }
        
        .header .generated {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        
        .section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #000;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .stat-box {
            border: 1px solid #000;
            padding: 10px;
            text-align: center;
        }
        
        .stat-label {
            font-size: 9px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 14px;
            font-weight: bold;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
        
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        
        td.text-right, th.text-right {
            text-align: right;
        }
        
        td.text-center, th.text-center {
            text-align: center;
        }
        
        tfoot th, tfoot td {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        @media print {
            body {
                padding: 15px;
            }
            
            .page-break {
                page-break-before: always;
            }
            
            .section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>Comprehensive Business Reports</h1>
        <div class="date-range">Period: {{ $dateRangeText }}</div>
        <div class="generated">Generated: {{ $generatedAt }}</div>
    </div>

    <!-- Sales Report Section -->
    <div class="section">
        <div class="section-title">1. Sales Report</div>
        
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">Total Sales</div>
                <div class="stat-value">₱{{ number_format($salesReport['total_sales'], 2, '.', ',') }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Invoices</div>
                <div class="stat-value">{{ $salesReport['total_invoices'] }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Average Order Value</div>
                <div class="stat-value">₱{{ number_format($salesReport['average_order_value'], 2, '.', ',') }}</div>
            </div>
        </div>
        
        <h3 style="margin-bottom: 10px; font-size: 12px;">Sales by Month</h3>
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="text-right">Total Sales</th>
                    <th class="text-right">Invoice Count</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesReport['sales_by_month'] as $month)
                <tr>
                    <td>{{ $month['month'] }}</td>
                    <td class="text-right">₱{{ number_format($month['total_sales'], 2, '.', ',') }}</td>
                    <td class="text-right">{{ $month['invoice_count'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center">No data available</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Financial Report Section -->
    <div class="section page-break">
        <div class="section-title">2. Financial Report</div>
        
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="text-right">Income (₱)</th>
                    <th class="text-right">Expenses (₱)</th>
                    <th class="text-right">Net (₱)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($financialReport['rows'] as $row)
                <tr>
                    <td>{{ $row['month'] }}</td>
                    <td class="text-right">₱{{ number_format($row['income'], 2, '.', ',') }}</td>
                    <td class="text-right">₱{{ number_format($row['expenses'], 2, '.', ',') }}</td>
                    <td class="text-right">₱{{ number_format($row['net'], 2, '.', ',') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center">No data available</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th class="text-right">₱{{ number_format($financialReport['totals']['income'], 2, '.', ',') }}</th>
                    <th class="text-right">₱{{ number_format($financialReport['totals']['expenses'], 2, '.', ',') }}</th>
                    <th class="text-right">₱{{ number_format($financialReport['totals']['net'], 2, '.', ',') }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Delivery Summary Section -->
    <div class="section page-break">
        <div class="section-title">3. Delivery Summary</div>
        
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-label">Total Deliveries</div>
                <div class="stat-value">{{ $deliverySummary['total'] }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Pending</div>
                <div class="stat-value">{{ $deliverySummary['pending'] }}</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Completed</div>
                <div class="stat-value">{{ $deliverySummary['completed'] }}</div>
            </div>
        </div>
        
            <div class="stat-box" style="grid-column: 1 / -1; margin-top: 10px;">
                <div class="stat-label">Total Delivery Fees</div>
                <div class="stat-value">₱{{ number_format($deliverySummary['total_fee'], 2, '.', ',') }}</div>
            </div>
        
        <table style="margin-top: 15px;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Delivery Address</th>
                    <th>Contact Person</th>
                    <th>Contact Phone</th>
                    <th>Delivery Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th class="text-right">Fee</th>
                    <th>Invoice #</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveries as $delivery)
                <tr>
                    <td>{{ $delivery['id'] }}</td>
                    <td>{{ $delivery['customer_name'] }}</td>
                    <td>{{ $delivery['delivery_address'] }}</td>
                    <td>{{ $delivery['contact_person'] }}</td>
                    <td>{{ $delivery['contact_phone'] }}</td>
                    <td>{{ $delivery['delivery_date'] }}</td>
                    <td>{{ $delivery['delivery_time'] }}</td>
                    <td>{{ ucfirst($delivery['status']) }}</td>
                    <td class="text-right">₱{{ number_format($delivery['delivery_fee'], 2, '.', ',') }}</td>
                    <td>{{ $delivery['invoice_number'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center">No deliveries found</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="8" class="text-right"><strong>Total Delivery Fees:</strong></td>
                    <td class="text-right"><strong>₱{{ number_format($deliverySummary['total_fee'], 2, '.', ',') }}</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Transactions Report Section -->
    <div class="section page-break">
        <div class="section-title">4. Transactions Report</div>
        
        <p style="margin-bottom: 10px; font-size: 10px;">Total Transactions: {{ count($transactions) }}</p>
        
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Company</th>
                    <th>Product</th>
                    <th class="text-right">Quantity</th>
                    <th class="text-right">Amount</th>
                    <th>Status</th>
                    <th>Payment Method</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                <tr>
                    <td>{{ $transaction['invoice_number'] }}</td>
                    <td>{{ $transaction['customer_name'] }}</td>
                    <td>{{ $transaction['company_name'] ?? 'N/A' }}</td>
                    <td>{{ $transaction['product_name'] }}</td>
                    <td class="text-right">{{ $transaction['quantity'] }}</td>
                    <td class="text-right">₱{{ number_format($transaction['total_amount'] / 100, 2, '.', ',') }}</td>
                    <td>{{ ucfirst($transaction['status']) }}</td>
                    <td>{{ ucfirst($transaction['payment_method']) }}</td>
                    <td>{{ $transaction['transaction_date'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center">No transactions found</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right"><strong>Total:</strong></td>
                    <td class="text-right"><strong>₱{{ number_format(collect($transactions)->sum(function($t) { return $t['total_amount'] / 100; }), 2, '.', ',') }}</strong></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>

