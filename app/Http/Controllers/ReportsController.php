<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\User;
use App\Models\Expense;
use App\Models\Delivery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $filterType = $request->input('filter_type', 'all'); // all, month, year, date_range
        $filterMonth = $request->input('filter_month');
        $filterYear = $request->input('filter_year');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Financial Report specific filters
        $financialFilterType = $request->input('financial_filter_type', 'all');
        $financialFilterMonth = $request->input('financial_filter_month');
        $financialFilterYear = $request->input('financial_filter_year');
        $financialStartDate = $request->input('financial_start_date');
        $financialEndDate = $request->input('financial_end_date');
        
        // Transactions Report specific filters
        $transactionFilterType = $request->input('transaction_filter_type', 'all');
        $transactionFilterMonth = $request->input('transaction_filter_month');
        $transactionFilterYear = $request->input('transaction_filter_year');
        $transactionStartDate = $request->input('transaction_start_date');
        $transactionEndDate = $request->input('transaction_end_date');
        
        // Calculate date range based on filter
        $dateRange = $this->getDateRange($filterType, $filterMonth, $filterYear, $startDate, $endDate);
        
        // Calculate financial report date range (use financial filters if provided, otherwise use global filters)
        $financialDateRange = $this->getDateRange(
            $financialFilterType !== 'all' ? $financialFilterType : $filterType,
            $financialFilterMonth !== null && $financialFilterMonth !== '' ? $financialFilterMonth : $filterMonth,
            $financialFilterYear !== null && $financialFilterYear !== '' ? $financialFilterYear : $filterYear,
            $financialStartDate !== null && $financialStartDate !== '' ? $financialStartDate : $startDate,
            $financialEndDate !== null && $financialEndDate !== '' ? $financialEndDate : $endDate
        );
        
        // Calculate transaction report date range (use transaction filters if provided, otherwise use global filters)
        $transactionDateRange = $this->getDateRange(
            $transactionFilterType !== 'all' ? $transactionFilterType : $filterType,
            $transactionFilterMonth !== null && $transactionFilterMonth !== '' ? $transactionFilterMonth : $filterMonth,
            $transactionFilterYear !== null && $transactionFilterYear !== '' ? $transactionFilterYear : $filterYear,
            $transactionStartDate !== null && $transactionStartDate !== '' ? $transactionStartDate : $startDate,
            $transactionEndDate !== null && $transactionEndDate !== '' ? $transactionEndDate : $endDate
        );
        
        // Sales Report
        $salesReport = $this->getSalesReport($dateRange);
        
        // Transactions (uses its own date range)
        $transactions = $this->getTransactions($transactionDateRange);
        
        // Financial Report (uses its own date range)
        $financialReport = $this->getFinancialReport($financialDateRange);
        
        // Delivery Summary
        $deliveriesData = $this->getDeliveries($dateRange);
        $deliveries = $deliveriesData['deliveries'];
        $deliverySummary = $deliveriesData['summary'];
        
        return Inertia::render('reports/Index', [
            'salesReport' => $salesReport,
            'transactions' => $transactions,
            'financialReport' => $financialReport,
            'deliveries' => $deliveries,
            'deliverySummary' => $deliverySummary,
            'filters' => [
                'filter_type' => $filterType,
                'filter_month' => $filterMonth,
                'filter_year' => $filterYear,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'financial_filter_type' => $financialFilterType,
                'financial_filter_month' => $financialFilterMonth,
                'financial_filter_year' => $financialFilterYear,
                'financial_start_date' => $financialStartDate,
                'financial_end_date' => $financialEndDate,
                'transaction_filter_type' => $transactionFilterType,
                'transaction_filter_month' => $transactionFilterMonth,
                'transaction_filter_year' => $transactionFilterYear,
                'transaction_start_date' => $transactionStartDate,
                'transaction_end_date' => $transactionEndDate,
            ],
        ]);
    }
    
    private function getDateRange($filterType, $filterMonth, $filterYear, $startDate, $endDate)
    {
        $now = Carbon::now();
        
        switch ($filterType) {
            case 'month':
                if ($filterMonth && $filterMonth !== '') {
                    try {
                        $date = Carbon::createFromFormat('Y-m', $filterMonth);
                        return [
                            'start' => $date->copy()->startOfMonth(),
                            'end' => $date->copy()->endOfMonth(),
                        ];
                    } catch (\Exception $e) {
                        // Fall through to default
                    }
                }
                // If no month selected, default to current month
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth(),
                ];
                
            case 'year':
                if ($filterYear && $filterYear !== '') {
                    $year = (int)$filterYear;
                    return [
                        'start' => Carbon::createFromDate($year, 1, 1)->startOfYear(),
                        'end' => Carbon::createFromDate($year, 12, 31)->endOfYear(),
                    ];
                }
                // If no year selected, default to current year
                return [
                    'start' => Carbon::createFromDate($now->year, 1, 1)->startOfYear(),
                    'end' => Carbon::createFromDate($now->year, 12, 31)->endOfYear(),
                ];
                
            case 'date_range':
                if ($startDate && $endDate && $startDate !== '' && $endDate !== '') {
                    return [
                        'start' => Carbon::parse($startDate)->startOfDay(),
                        'end' => Carbon::parse($endDate)->endOfDay(),
                    ];
                }
                break;
        }
        
        // Default: all time
        return [
            'start' => Carbon::createFromDate(2000, 1, 1),
            'end' => $now->copy()->endOfDay(),
        ];
    }
    
    private function getSalesReport($dateRange)
    {
        $invoices = Invoice::whereIn('status', ['completed', 'paid'])
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->with(['customer', 'invoiceItems.product'])
            ->get();
        
        $totalSales = $invoices->sum('total_amount') / 100;
        $totalInvoices = $invoices->count();
        $averageOrderValue = $totalInvoices > 0 ? $totalSales / $totalInvoices : 0;
        
        // Sales by customer
        $salesByCustomer = $invoices->groupBy('customer_id')->map(function ($customerInvoices, $customerId) {
            $customer = $customerInvoices->first()->customer;
            return [
                'customer_name' => $customer->name,
                'company_name' => $customer->company_name,
                'total_invoices' => $customerInvoices->count(),
                'total_amount' => $customerInvoices->sum('total_amount') / 100,
            ];
        })->values()->sortByDesc('total_amount')->take(10);
        
        // Sales by month
        $salesByMonth = $invoices->groupBy(function ($invoice) {
            return $invoice->created_at->format('Y-m');
        })->map(function ($monthInvoices, $month) {
            $monthDate = Carbon::createFromFormat('Y-m', $month);
            return [
                'month' => $monthDate->format('F Y'),
                'total_sales' => $monthInvoices->sum('total_amount') / 100,
                'invoice_count' => $monthInvoices->count(),
                'sort_key' => $month, // Keep original Y-m format for sorting
            ];
        })->sortBy('sort_key')->map(function ($item) {
            // Remove sort_key after sorting, return only needed fields
            return [
                'month' => $item['month'],
                'total_sales' => $item['total_sales'],
                'invoice_count' => $item['invoice_count'],
            ];
        })->values();
        
        return [
            'total_sales' => $totalSales,
            'total_invoices' => $totalInvoices,
            'average_order_value' => $averageOrderValue,
            'sales_by_customer' => $salesByCustomer,
            'sales_by_month' => $salesByMonth,
        ];
    }
    
    private function getTransactions($dateRange)
    {
        return Invoice::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->with(['customer', 'user', 'invoiceItems.product'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($invoice) {
                $productInfo = $this->getProductInfo($invoice);
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->reference_number,
                    'customer_name' => $invoice->customer->name,
                    'company_name' => $invoice->customer->company_name,
                    'product_name' => $productInfo['name'],
                    'quantity' => $productInfo['quantity'],
                    'total_amount' => $invoice->total_amount,
                    'status' => $invoice->status,
                    'payment_method' => $invoice->payment_method,
                    'transaction_date' => $invoice->created_at->format('Y-m-d H:i:s'),
                ];
            });
    }
    
    private function getDeliveries($dateRange)
    {
        // Query deliveries within date range
        // Use whereDate with >= and <= for proper date column comparison
        $query = Delivery::whereDate('delivery_date', '>=', $dateRange['start']->toDateString())
            ->whereDate('delivery_date', '<=', $dateRange['end']->toDateString())
            ->with(['customer', 'invoice']);
        
        $deliveries = $query->orderBy('delivery_date', 'desc')->get();
        
        // Calculate summary from model collection before mapping
        $deliverySummary = [
            'total' => $deliveries->count(),
            'pending' => $deliveries->where('status', 'pending')->count(),
            'completed' => $deliveries->where('status', 'completed')->count(),
            'cancelled' => $deliveries->where('status', 'cancelled')->count(),
            'total_fee' => $deliveries->where('status', 'completed')->sum(function ($delivery) {
                return $delivery->delivery_fee;
            }),
        ];
        
        // Map to array format for frontend
        $deliveriesArray = $deliveries->map(function ($delivery) {
            return [
                'id' => $delivery->id,
                'customer_name' => $delivery->customer->name ?? 'N/A',
                'delivery_address' => $delivery->delivery_address,
                'contact_person' => $delivery->contact_person,
                'contact_phone' => $delivery->contact_phone,
                'delivery_date' => $delivery->delivery_date->format('Y-m-d'),
                'delivery_time' => $delivery->delivery_time,
                'status' => $delivery->status,
                'delivery_fee' => $delivery->delivery_fee,
                'invoice_number' => $delivery->invoice->reference_number ?? 'N/A',
            ];
        });
        
        return [
            'deliveries' => $deliveriesArray,
            'summary' => $deliverySummary,
        ];
    }
    
    private function getProductInfo($invoice)
    {
        $items = $invoice->invoiceItems;
        if ($items->isEmpty()) {
            return ['name' => 'N/A', 'quantity' => 0];
        }
        
        $totalQuantity = $items->sum('quantity');
        $productNames = $items->map(function ($item) {
            return $item->product->name ?? 'N/A';
        })->unique()->implode(', ');
        
        return [
            'name' => $productNames,
            'quantity' => $totalQuantity,
        ];
    }
    
    private function getFinancialReport($dateRange)
    {
        $rows = [];
        $cursor = $dateRange['start']->copy()->startOfMonth();
        $end = $dateRange['end']->copy()->endOfMonth();
        
        while ($cursor->lte($end)) {
            $monthStart = $cursor->copy();
            $monthEnd = $cursor->copy()->endOfMonth();
            
            // Ensure we don't go beyond the date range
            if ($monthStart->lt($dateRange['start'])) {
                $monthStart = $dateRange['start']->copy();
            }
            if ($monthEnd->gt($dateRange['end'])) {
                $monthEnd = $dateRange['end']->copy();
            }
            
            $income = Invoice::whereBetween('created_at', [$monthStart, $monthEnd])
                ->whereIn('status', ['completed', 'paid'])
                ->sum('total_amount') / 100;
            
            $expenses = Expense::whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('amount');
            
            $net = $income - $expenses;
            
            $rows[] = [
                'month' => $monthStart->format('F Y'),
                'income' => round($income, 2),
                'expenses' => round($expenses, 2),
                'net' => round($net, 2),
            ];
            
            $cursor->addMonth();
        }
        
        $totals = [
            'income' => round(collect($rows)->sum('income'), 2),
            'expenses' => round(collect($rows)->sum('expenses'), 2),
            'net' => round(collect($rows)->sum('net'), 2),
        ];
        
        return [
            'rows' => $rows,
            'totals' => $totals,
        ];
    }
    
    /**
     * Print all reports in one document
     */
    public function printAll(Request $request)
    {
        $filterType = $request->input('filter_type', 'all');
        $filterMonth = $request->input('filter_month');
        $filterYear = $request->input('filter_year');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Financial Report specific filters
        $financialFilterType = $request->input('financial_filter_type', 'all');
        $financialFilterMonth = $request->input('financial_filter_month');
        $financialFilterYear = $request->input('financial_filter_year');
        $financialStartDate = $request->input('financial_start_date');
        $financialEndDate = $request->input('financial_end_date');
        
        // Transactions Report specific filters
        $transactionFilterType = $request->input('transaction_filter_type', 'all');
        $transactionFilterMonth = $request->input('transaction_filter_month');
        $transactionFilterYear = $request->input('transaction_filter_year');
        $transactionStartDate = $request->input('transaction_start_date');
        $transactionEndDate = $request->input('transaction_end_date');
        
        // Calculate date range based on filter
        $dateRange = $this->getDateRange($filterType, $filterMonth, $filterYear, $startDate, $endDate);
        
        // Calculate financial report date range (use financial filters if provided, otherwise use global filters)
        $financialDateRange = $this->getDateRange(
            $financialFilterType !== 'all' ? $financialFilterType : $filterType,
            $financialFilterMonth !== null && $financialFilterMonth !== '' ? $financialFilterMonth : $filterMonth,
            $financialFilterYear !== null && $financialFilterYear !== '' ? $financialFilterYear : $filterYear,
            $financialStartDate !== null && $financialStartDate !== '' ? $financialStartDate : $startDate,
            $financialEndDate !== null && $financialEndDate !== '' ? $financialEndDate : $endDate
        );
        
        // Calculate transaction report date range (use transaction filters if provided, otherwise use global filters)
        $transactionDateRange = $this->getDateRange(
            $transactionFilterType !== 'all' ? $transactionFilterType : $filterType,
            $transactionFilterMonth !== null && $transactionFilterMonth !== '' ? $transactionFilterMonth : $filterMonth,
            $transactionFilterYear !== null && $transactionFilterYear !== '' ? $transactionFilterYear : $filterYear,
            $transactionStartDate !== null && $transactionStartDate !== '' ? $transactionStartDate : $startDate,
            $transactionEndDate !== null && $transactionEndDate !== '' ? $transactionEndDate : $endDate
        );
        
        // Get all report data
        $salesReport = $this->getSalesReport($dateRange);
        $transactions = $this->getTransactions($transactionDateRange);
        $financialReport = $this->getFinancialReport($financialDateRange);
        $deliveriesData = $this->getDeliveries($dateRange);
        $deliveries = $deliveriesData['deliveries'];
        $deliverySummary = $deliveriesData['summary'];
        
        // Format date range for display
        $dateRangeText = $this->getDateRangeText($filterType, $filterMonth, $filterYear, $startDate, $endDate, $dateRange);
        
        return view('reports.print-all', [
            'salesReport' => $salesReport,
            'transactions' => $transactions,
            'financialReport' => $financialReport,
            'deliveries' => $deliveries,
            'deliverySummary' => $deliverySummary,
            'dateRangeText' => $dateRangeText,
            'generatedAt' => Carbon::now()->format('F d, Y h:i A'),
        ]);
    }
    
    private function getDateRangeText($filterType, $filterMonth, $filterYear, $startDate, $endDate, $dateRange)
    {
        switch ($filterType) {
            case 'month':
                if ($filterMonth) {
                    try {
                        $date = Carbon::createFromFormat('Y-m', $filterMonth);
                        return $date->format('F Y');
                    } catch (\Exception $e) {
                        // Fall through
                    }
                }
                return Carbon::now()->format('F Y');
                
            case 'year':
                if ($filterYear) {
                    return $filterYear;
                }
                return Carbon::now()->format('Y');
                
            case 'date_range':
                if ($startDate && $endDate) {
                    return Carbon::parse($startDate)->format('M d, Y') . ' - ' . Carbon::parse($endDate)->format('M d, Y');
                }
                break;
        }
        
        // Default: all time
        return 'All Time';
    }
}

