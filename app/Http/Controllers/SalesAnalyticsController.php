<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        // Customers: render a customer-specific dashboard with their own data only
        if ($request->user() && method_exists($request->user(), 'hasRole') && $request->user()->hasRole('Customer')) {
            $customerId = Customer::where('email', $request->user()->email)->value('id');
            if (!$customerId) {
                // If no matching customer record, render an empty dashboard
                return inertia('CustomerDashboard', [
                    'customer' => ['id' => null, 'name' => $request->user()->name, 'email' => $request->user()->email],
                    'monthlySpend' => [],
                    'statusBreakdown' => ['paid' => 0, 'pending' => 0, 'cancelled' => 0],
                    'topProducts' => [],
                    'categorySpend' => [],
                    'aovTrend' => [],
                ]);
            }

            $start = Carbon::now()->subMonths(11)->startOfMonth();
            $end = Carbon::now()->endOfMonth();

            // Monthly spend (completed invoices only), last 12 months
            $monthlySpend = DB::table('invoices')
                ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"), DB::raw('SUM(total_amount) as total'))
                ->where('customer_id', $customerId)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('ym')
                ->orderBy('ym')
                ->get()
                ->map(fn($r) => ['month' => $r->ym, 'total' => ((int)$r->total) / 100]);

            // Status breakdown (all invoices)
            $statusBreakdown = [
                // Keep key name 'paid' for UI compatibility, but count 'completed' invoices
                'paid' => (int) DB::table('invoices')->where('customer_id', $customerId)->where('status', 'completed')->count(),
                'pending' => (int) DB::table('invoices')->where('customer_id', $customerId)->where('status', 'pending')->count(),
                'cancelled' => (int) DB::table('invoices')->where('customer_id', $customerId)->where('status', 'cancelled')->count(),
            ];

            // Top products purchased (by quantity)
            $topProducts = DB::table('invoice_items')
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->join('products', 'invoice_items.product_id', '=', 'products.id')
                ->where('invoices.customer_id', $customerId)
                ->whereBetween('invoices.created_at', [$start, $end])
                ->where('invoices.status', 'completed')
                ->groupBy('products.id', 'products.name')
                ->select('products.id', 'products.name', DB::raw('SUM(invoice_items.quantity) as total_quantity'))
                ->orderByDesc('total_quantity')
                ->limit(8)
                ->get();

            // Category spend
            $categorySpend = DB::table('invoice_items')
                ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                ->join('products', 'invoice_items.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->where('invoices.customer_id', $customerId)
                ->whereBetween('invoices.created_at', [$start, $end])
                ->where('invoices.status', 'completed')
                ->groupBy('categories.id', 'categories.name')
                ->select('categories.name as category', DB::raw('SUM(invoice_items.total) as total'))
                ->orderByDesc('total')
                ->get()
                ->map(fn($r) => ['category' => $r->category, 'total' => ((int)$r->total) / 100]);

            // Average order value trend (by month)
            $aovTrend = DB::table('invoices')
                ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"), DB::raw('SUM(total_amount) as total'), DB::raw('COUNT(*) as cnt'))
                ->where('customer_id', $customerId)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('ym')
                ->orderBy('ym')
                ->get()
                ->map(function ($r) {
                    $total = ((int)$r->total) / 100;
                    $cnt = max(1, (int)$r->cnt);
                    return ['month' => $r->ym, 'aov' => $total / $cnt];
                });

            return inertia('CustomerDashboard', [
                'customer' => ['id' => $customerId],
                'monthlySpend' => $monthlySpend,
                'statusBreakdown' => $statusBreakdown,
                'topProducts' => $topProducts,
                'categorySpend' => $categorySpend,
                'aovTrend' => $aovTrend,
                'filters' => [
                    'start_date' => $start->format('Y-m-d'),
                    'end_date' => $end->format('Y-m-d'),
                ],
            ]);
        }

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
            // Use real datasets for all dashboard visuals even when dummy flag is present
            $salesData = $this->getSalesData($startDate, $endDate);
            $topProducts = $this->getTopProducts($startDate, $endDate);
            $salesByDate = $this->getSalesByDate($startDate, $endDate);
            $salesByCategory = $this->getSalesByCategory($startDate, $endDate);
            $recentInvoices = $this->getRecentInvoices($startDate, $endDate);
            // Churn metrics can still fall back if needed
            $churnMetrics = $this->getChurnMetrics();
        } else {
            $salesData = $this->getSalesData($startDate, $endDate);
            $topProducts = $this->getTopProducts($startDate, $endDate);
            $salesByDate = $this->getSalesByDate($startDate, $endDate);
            $salesByCategory = $this->getSalesByCategory($startDate, $endDate);
            $recentInvoices = $this->getRecentInvoices($startDate, $endDate);
            $churnMetrics = $this->getChurnMetrics();
        }

        return inertia('Dashboard', [
            'salesData' => $salesData,
            'topProducts' => $topProducts,
            'salesByDate' => $salesByDate,
            'salesByCategory' => $salesByCategory,
            'recentInvoices' => $recentInvoices,
            'churnMetrics' => $churnMetrics,
            'filters' => [
                'period' => $period,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
        ]);
    }

    private function getChurnMetrics()
    {
        // Last purchase per customer
        $lastPurchases = Invoice::select('customer_id', DB::raw('MAX(created_at) as last_purchase'))
            ->where('status', 'completed')
            ->groupBy('customer_id')
            ->pluck('last_purchase', 'customer_id');

        $totalCustomers = Customer::count();
        if ($totalCustomers === 0) {
            return $this->getDummyChurnMetrics();
        }

        $now = Carbon::now();
        $churnThreshold = $now->copy()->subDays(60);
        $atRiskMin = $now->copy()->subDays(60);
        $atRiskMax = $now->copy()->subDays(31);

        $churned = 0;
        $atRisk = 0;
        $atRiskList = [];

        if ($lastPurchases->isEmpty()) {
            return $this->getDummyChurnMetrics();
        }

        foreach ($lastPurchases as $customerId => $lastDate) {
            $last = Carbon::parse($lastDate);
            if ($last->lessThanOrEqualTo($churnThreshold)) {
                $churned++;
            } elseif ($last->between($atRiskMin, $atRiskMax)) {
                $atRisk++;
                if (count($atRiskList) < 4) {
                    $customer = Customer::find($customerId);
                    if ($customer) {
                        $atRiskList[] = [
                            'id' => $customerId,
                            'name' => $customer->name,
                            'lastPurchase' => $last->format('M d, Y'),
                            'riskLevel' => 'Medium',
                            'daysSincePurchase' => $last->diffInDays($now),
                        ];
                    }
                }
            }
        }

        $churnRate = $totalCustomers > 0 ? round(($churned / $totalCustomers) * 100, 1) : 0;
        $retentionRate = max(0, 100 - $churnRate);

        // Churn trend for last 6 months (snapshot at each month end)
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthEnd = $now->copy()->subMonths($i)->endOfMonth();
            $threshold = $monthEnd->copy()->subDays(60);
            $snapshotChurned = 0;
            foreach ($lastPurchases as $lastDate) {
                $last = Carbon::parse($lastDate);
                if ($last->lessThanOrEqualTo($threshold)) {
                    $snapshotChurned++;
                }
            }
            $rate = $totalCustomers > 0 ? round(($snapshotChurned / $totalCustomers) * 100, 1) : 0;
            $trend[] = ['month' => $monthEnd->format('M'), 'rate' => $rate];
        }

        return [
            'churnRate' => $churnRate,
            'retentionRate' => $retentionRate,
            'atRiskCustomers' => $atRisk,
            'churnTrend' => $trend,
            'customerSegments' => [], // optional for now
            'churnBySegment' => [],   // optional for now
            'atRiskCustomerList' => $atRiskList,
            'recommendations' => [],
        ];
    }

    private function getDummyChurnMetrics()
    {
        return [
            'churnRate' => 0,
            'retentionRate' => 100,
            'atRiskCustomers' => 0,
            'churnTrend' => collect(range(5,0))->map(fn($i)=>['month'=>Carbon::now()->subMonths($i)->format('M'),'rate'=>0])->toArray(),
            'customerSegments' => [],
            'churnBySegment' => [],
            'atRiskCustomerList' => [],
            'recommendations' => [],
        ];
    }

    private function getSalesData($startDate, $endDate)
    {
        // Revenue: completed invoices only (stored in cents; normalize to currency)
        $totalSalesCents = Invoice::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('total_amount');
        $totalSales = $totalSalesCents / 100;

        // Total invoices: all statuses
        $totalInvoices = Invoice::whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Average order value: use completed invoices to reflect realized sales
        $paidInvoicesCount = Invoice::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();
        $averageOrderValue = $paidInvoicesCount > 0 ? $totalSales / $paidInvoicesCount : 0;

        $pendingInvoices = Invoice::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'pending')
            ->count();

        // Pending orders (all orders, not filtered by date range - these are current action items)
        $pendingOrders = Order::where('status', 'pending')->count();

        return [
            'total_sales' => $totalSales,
            'total_invoices' => $totalInvoices,
            'average_order_value' => $averageOrderValue,
            'pending_invoices' => $pendingInvoices,
            'pending_orders' => $pendingOrders,
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
            ->where('invoices.status', 'completed')
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get();

        // Normalize revenue to currency
        return $products->map(function ($p) {
            $p->total_revenue = ((int) $p->total_revenue) / 100;
            return $p;
        });
    }

    private function getSalesByDate($startDate, $endDate)
    {
        // Count all invoices; sum revenue only from completed invoices
        $salesByDate = Invoice::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as daily_sales"),
                DB::raw('COUNT(*) as invoice_count')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'sales' => ((int) $item->daily_sales) / 100,
                    'invoices' => $item->invoice_count,
                ];
            });

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
            ->where('invoices.status', 'completed')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_revenue', 'desc')
            ->get();

        // Normalize revenue to currency
        return $salesByCategory->map(function ($c) {
            $c->total_revenue = ((int) $c->total_revenue) / 100;
            return $c;
        });
    }

    private function getRecentInvoices($startDate, $endDate)
    {
        $recentInvoices = Invoice::with(['customer', 'invoiceItems.product'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
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
                    'items_count' => $invoice->invoiceItems ? $invoice->invoiceItems->count() : 0,
                ];
            });

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