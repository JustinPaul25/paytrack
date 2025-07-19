<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'month'); // month, quarter, year
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Set default date range if not provided
        if (!$startDate || !$endDate) {
            $endDate = Carbon::now()->endOfDay();
            switch ($period) {
                case 'week':
                    $startDate = Carbon::now()->subWeek()->startOfDay();
                    break;
                case 'quarter':
                    $startDate = Carbon::now()->subQuarter()->startOfDay();
                    break;
                case 'year':
                    $startDate = Carbon::now()->subYear()->startOfDay();
                    break;
                default: // month
                    $startDate = Carbon::now()->subMonth()->startOfDay();
                    break;
            }
        } else {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
        }

        // Get sales data
        $salesData = $this->getSalesData($startDate, $endDate);
        $topProducts = $this->getTopProducts($startDate, $endDate);
        $salesByDate = $this->getSalesByDate($startDate, $endDate);
        $salesByCategory = $this->getSalesByCategory($startDate, $endDate);
        $recentInvoices = $this->getRecentInvoices($startDate, $endDate);

        return inertia('sales/Analytics', [
            'salesData' => $salesData,
            'topProducts' => $topProducts,
            'salesByDate' => $salesByDate,
            'salesByCategory' => $salesByCategory,
            'recentInvoices' => $recentInvoices,
            'filters' => [
                'period' => $period,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
        ]);
    }

    private function getSalesData($startDate, $endDate)
    {
        $totalSales = Invoice::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('total_amount');

        $totalInvoices = Invoice::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->count();

        $averageOrderValue = $totalInvoices > 0 ? $totalSales / $totalInvoices : 0;

        $pendingInvoices = Invoice::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'pending')
            ->count();

        return [
            'total_sales' => $totalSales,
            'total_invoices' => $totalInvoices,
            'average_order_value' => $averageOrderValue,
            'pending_invoices' => $pendingInvoices,
        ];
    }

    private function getTopProducts($startDate, $endDate)
    {
        return InvoiceItem::select(
                'products.name',
                'products.id',
                DB::raw('SUM(invoice_items.quantity) as total_quantity'),
                DB::raw('SUM(invoice_items.total) as total_revenue')
            )
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereBetween('invoices.created_at', [$startDate, $endDate])
            ->where('invoices.status', 'paid')
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get();
    }

    private function getSalesByDate($startDate, $endDate)
    {
        return Invoice::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as daily_sales'),
                DB::raw('COUNT(*) as invoice_count')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'sales' => $item->daily_sales,
                    'invoices' => $item->invoice_count,
                ];
            });
    }

    private function getSalesByCategory($startDate, $endDate)
    {
        return InvoiceItem::select(
                'categories.name as category_name',
                DB::raw('SUM(invoice_items.total) as total_revenue'),
                DB::raw('SUM(invoice_items.quantity) as total_quantity')
            )
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereBetween('invoices.created_at', [$startDate, $endDate])
            ->where('invoices.status', 'paid')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_revenue', 'desc')
            ->get();
    }

    private function getRecentInvoices($startDate, $endDate)
    {
        return Invoice::with(['customer', 'invoice_items.product'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'customer_name' => $invoice->customer->name,
                    'total_amount' => $invoice->total_amount,
                    'status' => $invoice->status,
                    'created_at' => $invoice->created_at->format('M d, Y'),
                    'items_count' => $invoice->invoice_items->count(),
                ];
            });
    }
} 