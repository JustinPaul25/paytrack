<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Invoice;
use App\Models\Customer;

class SalesTransactionController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'month'); // week, month, year
        $customer = $request->get('customer', 'all');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Get real invoice data
        $transactions = $this->getRealTransactions($period, $customer, $sortBy, $sortOrder);
        $customers = $this->getRealCustomers();
        $summary = $this->calculateSummary($transactions);

        return inertia('sales/Transactions', [
            'transactions' => $transactions,
            'customers' => $customers,
            'summary' => $summary,
            'filters' => [
                'period' => $period,
                'customer' => $customer,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
        ]);
    }

    private function getRealTransactions($period, $customer, $sortBy, $sortOrder)
    {
        $query = Invoice::with(['customer', 'user', 'invoiceItems.product'])
            ->where('created_at', '>=', $this->getStartDate($period));

        // Filter by customer if specified
        if ($customer !== 'all') {
            $query->whereHas('customer', function ($q) use ($customer) {
                $q->where('id', $customer);
            });
        }

        // Apply sorting (whitelist + mapping)
        $sortMap = [
            'created_at' => 'created_at',
            'total_amount' => 'total_amount',
            // UI sends "payment_status" – actual column is "status"
            'payment_status' => 'status',
            'status' => 'status',
        ];
        $column = $sortMap[$sortBy] ?? 'created_at';
        $direction = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';
        
        // Special handling for status sorting to ensure proper order
        if ($column === 'status') {
            // Use CASE statement to give priority: completed = 1, cancelled = 2, draft = 3, pending = 4
            // For ascending: completed first (1), cancelled second (2)
            // For descending: cancelled first (2), completed second (1)
            if ($direction === 'asc') {
                $query->orderByRaw("CASE 
                    WHEN status = 'completed' THEN 1
                    WHEN status = 'cancelled' THEN 2
                    WHEN status = 'draft' THEN 3
                    WHEN status = 'pending' THEN 4
                    ELSE 5
                END ASC");
            } else {
                $query->orderByRaw("CASE 
                    WHEN status = 'completed' THEN 1
                    WHEN status = 'cancelled' THEN 2
                    WHEN status = 'draft' THEN 3
                    WHEN status = 'pending' THEN 4
                    ELSE 5
                END DESC");
            }
        } else {
            $query->orderBy($column, $direction);
        }

        $invoices = $query->get();

        // Calculate running balance (cumulative total of sales with VAT, excluding cancelled invoices)
        $runningBalance = 0;
        $transactions = [];

        foreach ($invoices as $invoice) {
            // Get the actual invoice amounts
            $saleNonVatTotal = $invoice->subtotal_amount; // Subtotal (no VAT)
            $vatAmount = $invoice->vat_amount; // 12% VAT
            $withholdingTax = $invoice->withholding_tax_amount ?? 0; // 1% withholding tax from invoice
            
            // Calculate 5% tax that's already included in the product price
            // Formula: Tax Amount = Price × (Tax Rate / (100 + Tax Rate))
            // For 5% tax: Tax Amount = Price × (5 / 105) = Price × 0.047619
            $tax5Percent = $saleNonVatTotal * (5 / 105);
            
            // Cash amount = Total amount (which already includes: Subtotal + VAT - Withholding Tax)
            $cashAmount = $invoice->total_amount;
            
            // Add to running balance only if not cancelled
            $isCompleted = $this->formatStatus($invoice->status) === 'Completed';
            if ($isCompleted) {
                $runningBalance += $invoice->total_amount;
            }

            $transactions[] = [
                'id' => $invoice->id,
                'customer_name' => $invoice->customer->name,
                'company_name' => $invoice->customer->company_name,
                'product_name' => $this->getProductInfo($invoice)['name'],
                'quantity' => $this->getProductInfo($invoice)['quantity'],
                'unit_price' => $invoice->total_amount / max(1, $this->getProductInfo($invoice)['quantity']), // Average unit price
                'total_amount' => $invoice->total_amount,
                'status' => $this->formatStatus($invoice->status),
                'transaction_date' => $invoice->created_at->format('Y-m-d H:i:s'),
                'invoice_number' => $invoice->reference_number,
                'notes' => $invoice->notes,
                'cash_amount' => $cashAmount,
                'withholding_tax' => $withholdingTax,
                'tax_5_percent' => $tax5Percent,
                'sale_non_vat_total' => $saleNonVatTotal,
                'vat_amount' => $vatAmount,
                'running_balance' => $runningBalance,
            ];
        }

        return $transactions;
    }

    private function getProductInfo($invoice)
    {
        $items = $invoice->invoiceItems;
        
        if ($items->count() === 0) {
            return ['name' => 'No items', 'quantity' => 0];
        }
        
        if ($items->count() === 1) {
            $item = $items->first();
            return [
                'name' => $item->product->name ?? 'Unknown Product',
                'quantity' => $item->quantity
            ];
        }
        
        // Multiple items - show summary
        $totalQuantity = $items->sum('quantity');
        $firstProduct = $items->first()->product->name ?? 'Unknown Product';
        
        return [
            'name' => $firstProduct . ' +' . ($items->count() - 1) . ' more',
            'quantity' => $totalQuantity
        ];
    }



    private function formatStatus($status)
    {
        return match($status) {
            'completed' => 'Completed',
            'cancelled' => 'Canceled',
            'draft' => 'Canceled',
            'pending' => 'Canceled',
            default => ucfirst($status)
        };
    }

    private function getStartDate($period)
    {
        return match($period) {
            'week' => Carbon::now()->subWeek(),
            'month' => Carbon::now()->subMonth(),
            'year' => Carbon::now()->subYear(),
            default => Carbon::now()->subMonth(),
        };
    }

    private function getRealCustomers()
    {
        $customers = Customer::orderBy('name')->get();
        
        $customerOptions = [
            ['value' => 'all', 'label' => 'All Customers']
        ];
        
        foreach ($customers as $customer) {
            $label = $customer->name;
            if ($customer->company_name) {
                $label .= ' - ' . $customer->company_name;
            }
            
            $customerOptions[] = [
                'value' => $customer->id,
                'label' => $label
            ];
        }
        
        return $customerOptions;
    }

    private function calculateSummary($transactions)
    {
        $totalRevenue = collect($transactions)->sum('total_amount');
        $totalTransactions = count($transactions);
        $completedTransactions = collect($transactions)->where('status', 'Completed')->count();
        $canceledTransactions = collect($transactions)->where('status', 'Canceled')->count();
        $averageOrderValue = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;

        return [
            'total_revenue' => $totalRevenue,
            'total_transactions' => $totalTransactions,
            'completed_transactions' => $completedTransactions,
            'pending_transactions' => $canceledTransactions,
            'average_order_value' => $averageOrderValue,
        ];
    }
} 