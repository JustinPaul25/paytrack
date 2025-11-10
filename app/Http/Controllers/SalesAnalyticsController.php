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
        $useDummyData = $request->get('dummy', false); // Force dummy data for testing

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
        if ($useDummyData) {
            $salesData = $this->getDummySalesData();
            $topProducts = $this->getDummyTopProducts();
            $salesByDate = $this->getDummySalesByDate($startDate, $endDate);
            $salesByCategory = $this->getDummySalesByCategory();
            $recentInvoices = $this->getDummyRecentInvoices();
        } else {
            $salesData = $this->getSalesData($startDate, $endDate);
            $topProducts = $this->getTopProducts($startDate, $endDate);
            $salesByDate = $this->getSalesByDate($startDate, $endDate);
            $salesByCategory = $this->getSalesByCategory($startDate, $endDate);
            $recentInvoices = $this->getRecentInvoices($startDate, $endDate);
        }

        return inertia('Dashboard', [
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

        // If no real data, return dummy data
        if ($totalSales == 0 && $totalInvoices == 0) {
            return $this->getDummySalesData();
        }

        return [
            'total_sales' => $totalSales,
            'total_invoices' => $totalInvoices,
            'average_order_value' => $averageOrderValue,
            'pending_invoices' => $pendingInvoices,
        ];
    }

    private function getTopProducts($startDate, $endDate)
    {
        $products = InvoiceItem::select(
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

        // If no real data, return dummy data
        if ($products->isEmpty()) {
            return $this->getDummyTopProducts();
        }

        return $products;
    }

    private function getSalesByDate($startDate, $endDate)
    {
        $salesByDate = Invoice::select(
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

        // If no real data, return dummy data
        if ($salesByDate->isEmpty()) {
            return $this->getDummySalesByDate($startDate, $endDate);
        }

        return $salesByDate;
    }

    private function getSalesByCategory($startDate, $endDate)
    {
        $salesByCategory = InvoiceItem::select(
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

        // If no real data, return dummy data
        if ($salesByCategory->isEmpty()) {
            return $this->getDummySalesByCategory();
        }

        return $salesByCategory;
    }

    private function getRecentInvoices($startDate, $endDate)
    {
        $recentInvoices = Invoice::with(['customer', 'invoice_items.product'])
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

        // If no real data, return dummy data
        if ($recentInvoices->isEmpty()) {
            return $this->getDummyRecentInvoices();
        }

        return $recentInvoices;
    }

    // Dummy data methods
    private function getDummySalesData()
    {
        return [
            'total_sales' => 125000.00,
            'total_invoices' => 45,
            'average_order_value' => 2777.78,
            'pending_invoices' => 8,
        ];
    }

    private function getDummyTopProducts()
    {
        return collect([
            [
                'id' => 1,
                'name' => 'Ink (Brother 500C, Cyan)',
                'total_quantity' => 25,
                'total_revenue' => 18750.00,
            ],
            [
                'id' => 2,
                'name' => 'Paper A4 (500 sheets)',
                'total_quantity' => 40,
                'total_revenue' => 16000.00,
            ],
            [
                'id' => 3,
                'name' => 'Toner (HP LaserJet)',
                'total_quantity' => 15,
                'total_revenue' => 22500.00,
            ],
            [
                'id' => 4,
                'name' => 'USB Cable (3ft)',
                'total_quantity' => 60,
                'total_revenue' => 9000.00,
            ],
            [
                'id' => 5,
                'name' => 'Mouse (Wireless)',
                'total_quantity' => 20,
                'total_revenue' => 12000.00,
            ],
            [
                'id' => 6,
                'name' => 'Keyboard (Mechanical)',
                'total_quantity' => 12,
                'total_revenue' => 14400.00,
            ],
            [
                'id' => 7,
                'name' => 'Monitor Stand',
                'total_quantity' => 8,
                'total_revenue' => 6400.00,
            ],
            [
                'id' => 8,
                'name' => 'Webcam (HD)',
                'total_quantity' => 18,
                'total_revenue' => 10800.00,
            ],
        ]);
    }

    private function getDummySalesByDate($startDate, $endDate)
    {
        $dates = [];
        $currentDate = $startDate->copy();
        
        // Create more realistic sales patterns
        $baseSales = 3000;
        $weekendBoost = 1.3; // 30% boost on weekends
        $midMonthBoost = 1.2; // 20% boost mid-month
        
        while ($currentDate <= $endDate) {
            $dayOfWeek = $currentDate->dayOfWeek;
            $dayOfMonth = $currentDate->day;
            
            // Base sales with some randomness
            $sales = $baseSales + rand(-500, 1000);
            
            // Apply weekend boost
            if ($dayOfWeek == 0 || $dayOfWeek == 6) { // Sunday or Saturday
                $sales *= $weekendBoost;
            }
            
            // Apply mid-month boost (around 15th)
            if ($dayOfMonth >= 12 && $dayOfMonth <= 18) {
                $sales *= $midMonthBoost;
            }
            
            // Ensure minimum sales
            $sales = max($sales, 1500);
            
            $dates[] = [
                'date' => $currentDate->format('Y-m-d'),
                'sales' => round($sales, 2),
                'invoices' => max(1, round($sales / 1000)), // Roughly 1 invoice per 1000 pesos
            ];
            $currentDate->addDay();
        }

        return collect($dates);
    }

    private function getDummySalesByCategory()
    {
        return collect([
            [
                'category_name' => 'Office Supplies',
                'total_revenue' => 45000.00,
                'total_quantity' => 120,
            ],
            [
                'category_name' => 'Printing Materials',
                'total_revenue' => 35000.00,
                'total_quantity' => 85,
            ],
            [
                'category_name' => 'Computer Accessories',
                'total_revenue' => 25000.00,
                'total_quantity' => 65,
            ],
            [
                'category_name' => 'Electronics',
                'total_revenue' => 15000.00,
                'total_quantity' => 25,
            ],
            [
                'category_name' => 'Stationery',
                'total_revenue' => 5000.00,
                'total_quantity' => 45,
            ],
            [
                'category_name' => 'Furniture',
                'total_revenue' => 8000.00,
                'total_quantity' => 15,
            ],
            [
                'category_name' => 'Software',
                'total_revenue' => 12000.00,
                'total_quantity' => 30,
            ],
        ]);
    }

    private function getDummyRecentInvoices()
    {
        $customers = [
            'ABC Company', 'XYZ Corporation', 'Tech Solutions Inc', 'Office Plus', 
            'Digital Print Shop', 'Creative Agency', 'Law Firm Partners', 'Medical Center', 
            'School District', 'Retail Store', 'Marketing Pro', 'Design Studio',
            'Consulting Group', 'Startup Hub', 'Enterprise Solutions'
        ];
        $statuses = ['paid', 'pending', 'cancelled'];
        
        return collect(range(1, 10))->map(function ($i) use ($customers, $statuses) {
            $amount = rand(1000, 8000);
            $status = $statuses[array_rand($statuses)];
            
            return [
                'id' => 1000 + $i,
                'customer_name' => $customers[array_rand($customers)],
                'total_amount' => $amount,
                'status' => $status,
                'created_at' => Carbon::now()->subDays(rand(1, 30))->format('M d, Y'),
                'items_count' => rand(1, 8),
            ];
        });
    }
} 