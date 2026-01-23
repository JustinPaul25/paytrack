<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Reminder;
use App\Models\Delivery;
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
                $defaultStart = Carbon::now()->subMonths(11)->startOfMonth();
                $defaultEnd = Carbon::now()->endOfMonth();
                return inertia('CustomerDashboard', [
                    'customer' => ['id' => null, 'name' => $request->user()->name, 'email' => $request->user()->email],
                    'monthlySpend' => [],
                    'statusBreakdown' => ['paid' => 0, 'pending' => 0, 'cancelled' => 0],
                    'topProducts' => [],
                    'categorySpend' => [],
                    'aovTrend' => [],
                    'reminders' => [],
                    'filters' => [
                        'start_date' => $defaultStart->format('Y-m-d'),
                        'end_date' => $defaultEnd->format('Y-m-d'),
                    ],
                ]);
            }

            // Get filter parameters
            $monthlySpendStart = $request->get('monthly_spend_start_date');
            $monthlySpendEnd = $request->get('monthly_spend_end_date');
            $topProductsStart = $request->get('top_products_start_date');
            $topProductsEnd = $request->get('top_products_end_date');
            $categorySpendStart = $request->get('category_spend_start_date');
            $categorySpendEnd = $request->get('category_spend_end_date');
            $aovTrendStart = $request->get('aov_trend_start_date');
            $aovTrendEnd = $request->get('aov_trend_end_date');
            
            // Default date range (last 12 months)
            $defaultStart = Carbon::now()->subMonths(11)->startOfMonth();
            $defaultEnd = Carbon::now()->endOfMonth();
            
            // Use filter dates if provided, otherwise use defaults
            $monthlySpendFilterStart = $monthlySpendStart ? Carbon::parse($monthlySpendStart)->startOfDay() : $defaultStart;
            $monthlySpendFilterEnd = $monthlySpendEnd ? Carbon::parse($monthlySpendEnd)->endOfDay() : $defaultEnd;
            
            $topProductsFilterStart = $topProductsStart ? Carbon::parse($topProductsStart)->startOfDay() : $defaultStart;
            $topProductsFilterEnd = $topProductsEnd ? Carbon::parse($topProductsEnd)->endOfDay() : $defaultEnd;
            
            $categorySpendFilterStart = $categorySpendStart ? Carbon::parse($categorySpendStart)->startOfDay() : $defaultStart;
            $categorySpendFilterEnd = $categorySpendEnd ? Carbon::parse($categorySpendEnd)->endOfDay() : $defaultEnd;
            
            $aovTrendFilterStart = $aovTrendStart ? Carbon::parse($aovTrendStart)->startOfDay() : $defaultStart;
            $aovTrendFilterEnd = $aovTrendEnd ? Carbon::parse($aovTrendEnd)->endOfDay() : $defaultEnd;

            // Monthly spend (completed invoices only)
            $monthlySpend = DB::table('invoices')
                ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"), DB::raw('SUM(total_amount) as total'))
                ->where('customer_id', $customerId)
                ->where('status', 'completed')
                ->whereBetween('created_at', [$monthlySpendFilterStart, $monthlySpendFilterEnd])
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
                ->whereBetween('invoices.created_at', [$topProductsFilterStart, $topProductsFilterEnd])
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
                ->whereBetween('invoices.created_at', [$categorySpendFilterStart, $categorySpendFilterEnd])
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
                ->whereBetween('created_at', [$aovTrendFilterStart, $aovTrendFilterEnd])
                ->groupBy('ym')
                ->orderBy('ym')
                ->get()
                ->map(function ($r) {
                    $total = ((int)$r->total) / 100;
                    $cnt = max(1, (int)$r->cnt);
                    return ['month' => $r->ym, 'aov' => $total / $cnt];
                });

            // Get invoice payment reminders for this customer
            // Only show reminders for invoices with pending payment status
            $reminders = Reminder::where('customer_id', $customerId)
                ->where('type', 'customer_due')
                ->where('status', 'pending')
                ->whereHas('invoice', function ($query) {
                    $query->where('payment_status', 'pending')
                          ->where('status', '!=', 'cancelled');
                })
                ->with('invoice')
                ->orderBy('due_date', 'asc')
                ->orderBy('priority', 'desc')
                ->get()
                ->filter(function ($reminder) {
                    // Additional safety check: ensure invoice exists and has pending payment status
                    return $reminder->invoice 
                        && $reminder->invoice->payment_status === 'pending'
                        && $reminder->invoice->status !== 'cancelled';
                })
                ->map(function ($reminder) {
                    // Use invoice total_amount if available (more accurate), otherwise fall back to reminder amount
                    // Note: invoice->total_amount is already in currency format (accessor divides by 100)
                    $amount = $reminder->invoice && $reminder->invoice->total_amount 
                        ? $reminder->invoice->total_amount 
                        : $reminder->amount;
                    
                    return [
                        'id' => $reminder->id,
                        'title' => $reminder->title,
                        'description' => $reminder->description,
                        'due_date' => $reminder->due_date->format('Y-m-d'),
                        'due_date_formatted' => $reminder->due_date->format('M d, Y'),
                        'amount' => $amount,
                        'currency' => $reminder->currency,
                        'priority' => $reminder->priority,
                        'is_read' => $reminder->is_read,
                        'invoice_id' => $reminder->invoice_id,
                        'invoice_reference' => $reminder->invoice ? $reminder->invoice->reference_number : null,
                        'days_until_due' => Carbon::today()->diffInDays($reminder->due_date, false),
                    ];
                });

            return inertia('CustomerDashboard', [
                'customer' => ['id' => $customerId],
                'monthlySpend' => $monthlySpend,
                'statusBreakdown' => $statusBreakdown,
                'topProducts' => $topProducts,
                'categorySpend' => $categorySpend,
                'aovTrend' => $aovTrend,
                'reminders' => $reminders,
                'filters' => [
                    'start_date' => $defaultStart->format('Y-m-d'),
                    'end_date' => $defaultEnd->format('Y-m-d'),
                ],
            ]);
        }

        $period = $request->get('period', 'month'); // month, quarter, year
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $categoryStartDate = $request->get('category_start_date');
        $categoryEndDate = $request->get('category_end_date');
        
        // Products table filters
        $productsStartDate = $request->get('products_start_date');
        $productsEndDate = $request->get('products_end_date');
        $productsSearch = $request->get('products_search');
        $productsSortBy = $request->get('products_sort_by', 'revenue');
        $productsSortOrder = $request->get('products_sort_order', 'desc');
        
        // Transactions table filters
        $transactionsStartDate = $request->get('transactions_start_date');
        $transactionsEndDate = $request->get('transactions_end_date');
        $transactionsStatus = $request->get('transactions_status', 'all');
        $transactionsSortBy = $request->get('transactions_sort_by', 'date');
        $transactionsSortOrder = $request->get('transactions_sort_order', 'desc');
        
        $useDummyData = $request->get('dummy', false); // Force dummy data for testing

        // Set default date range if not provided
        if (!$startDate || !$endDate) {
            $currentYear = Carbon::now()->year;
            switch ($period) {
                case 'week':
                    $endDate = Carbon::now()->endOfDay();
                    $startDate = Carbon::now()->subWeek()->startOfDay();
                    break;
                case 'quarter':
                    $endDate = Carbon::now()->endOfDay();
                    $startDate = Carbon::now()->subQuarter()->startOfDay();
                    break;
                case 'year':
                    $endDate = Carbon::now()->endOfDay();
                    $startDate = Carbon::now()->subYear()->startOfDay();
                    break;
                default: // month - default to last 3 months to show comprehensive data
                    // Set to last 3 months to show all seeded historical data
                    $startDate = Carbon::now()->subMonths(3)->startOfMonth();
                    $endDate = Carbon::now()->endOfDay();
                    break;
            }
        } else {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
        }

        // Use category-specific dates if provided, otherwise use main filter dates
        $categoryFilterStart = $categoryStartDate ? Carbon::parse($categoryStartDate)->startOfDay() : $startDate;
        $categoryFilterEnd = $categoryEndDate ? Carbon::parse($categoryEndDate)->endOfDay() : $endDate;
        
        // Use products-specific dates if provided, otherwise use main filter dates
        $productsFilterStart = $productsStartDate ? Carbon::parse($productsStartDate)->startOfDay() : $startDate;
        $productsFilterEnd = $productsEndDate ? Carbon::parse($productsEndDate)->endOfDay() : $endDate;
        
        // Use transactions-specific dates if provided, otherwise use main filter dates
        $transactionsFilterStart = $transactionsStartDate ? Carbon::parse($transactionsStartDate)->startOfDay() : $startDate;
        $transactionsFilterEnd = $transactionsEndDate ? Carbon::parse($transactionsEndDate)->endOfDay() : $endDate;

        // Get sales data
        if ($useDummyData) {
            // Use real datasets for all dashboard visuals even when dummy flag is present
            $salesData = $this->getSalesData($startDate, $endDate);
            $topProducts = $this->getTopProducts($productsFilterStart, $productsFilterEnd, $productsSearch, $productsSortBy, $productsSortOrder);
            $salesByDate = $this->getSalesByDate($startDate, $endDate);
            $salesByCategory = $this->getSalesByCategory($categoryFilterStart, $categoryFilterEnd);
            $recentInvoices = $this->getRecentInvoices($transactionsFilterStart, $transactionsFilterEnd, $transactionsStatus, $transactionsSortBy, $transactionsSortOrder);
            // Churn metrics can still fall back if needed
            $churnMetrics = $this->getChurnMetrics();
        } else {
            $salesData = $this->getSalesData($startDate, $endDate);
            $topProducts = $this->getTopProducts($productsFilterStart, $productsFilterEnd, $productsSearch, $productsSortBy, $productsSortOrder);
            $salesByDate = $this->getSalesByDate($startDate, $endDate);
            $salesByCategory = $this->getSalesByCategory($categoryFilterStart, $categoryFilterEnd);
            $recentInvoices = $this->getRecentInvoices($transactionsFilterStart, $transactionsFilterEnd, $transactionsStatus, $transactionsSortBy, $transactionsSortOrder);
            $churnMetrics = $this->getChurnMetrics();
        }

        // Get low stock products for staff users only (not admin)
        $lowStockProducts = [];
        if ($request->user() && method_exists($request->user(), 'hasRole') && 
            $request->user()->hasRole('Staff') && !$request->user()->hasRole('Admin')) {
            $lowStockProducts = Product::where('stock', '<=', 10)
                ->with('category')
                ->orderBy('stock', 'asc')
                ->limit(20)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'stock' => $product->stock,
                        'SKU' => $product->SKU,
                        'category' => $product->category ? $product->category->name : 'No category',
                    ];
                })
                ->toArray();
        }

        // Get today's deliveries for Admin/Staff users
        $todayDeliveries = [];
        if ($request->user() && method_exists($request->user(), 'hasRole') && 
            ($request->user()->hasRole('Admin') || $request->user()->hasRole('Staff'))) {
            $todayDeliveries = $this->getTodayDeliveries();
        }

        return inertia('Dashboard', [
            'salesData' => $salesData,
            'topProducts' => $topProducts,
            'salesByDate' => $salesByDate,
            'salesByCategory' => $salesByCategory,
            'recentInvoices' => $recentInvoices,
            'churnMetrics' => $churnMetrics,
            'lowStockProducts' => $lowStockProducts,
            'todayDeliveries' => $todayDeliveries,
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

    private function getTopProducts($startDate, $endDate, $search = null, $sortBy = 'revenue', $sortOrder = 'desc')
    {
        $query = InvoiceItem::select(
                'products.name',
                'products.id',
                DB::raw('SUM(invoice_items.quantity) as total_quantity'),
                DB::raw('SUM(invoice_items.total) as total_revenue')
            )
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereBetween('invoices.created_at', [$startDate, $endDate])
            ->where('invoices.status', 'completed');
        
        // Apply search filter
        if ($search) {
            $query->where('products.name', 'like', '%' . $search . '%');
        }
        
        $query->groupBy('products.id', 'products.name');
        
        // Apply sorting
        $orderByColumn = 'total_revenue';
        if ($sortBy === 'quantity') {
            $orderByColumn = 'total_quantity';
        } elseif ($sortBy === 'name') {
            $orderByColumn = 'products.name';
        }
        
        $query->orderBy($orderByColumn, $sortOrder);
        $query->limit(10);
        
        $products = $query->get();

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

    private function getRecentInvoices($startDate, $endDate, $status = 'all', $sortBy = 'date', $sortOrder = 'desc')
    {
        $query = Invoice::with(['customer', 'invoiceItems.product'])
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->whereBetween('invoices.created_at', [$startDate, $endDate]);
        
        // Apply status filter
        if ($status !== 'all') {
            $query->where('invoices.status', $status);
        }
        // If status is 'all', show all statuses
        
        // Apply sorting
        if ($sortBy === 'amount') {
            $query->orderBy('invoices.total_amount', $sortOrder);
        } elseif ($sortBy === 'customer') {
            $query->orderBy('customers.name', $sortOrder);
        } else {
            // Default to date
            $query->orderBy('invoices.created_at', $sortOrder);
        }
        
        $query->select('invoices.*');
        $query->limit(10);
        
        $recentInvoices = $query->get()
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

    private function getTodayDeliveries()
    {
        $today = Carbon::today();
        
        $deliveries = Delivery::with(['customer', 'invoice'])
            ->whereDate('delivery_date', $today)
            ->orderBy('delivery_time', 'asc')
            ->get()
            ->map(function ($delivery) {
                return [
                    'id' => $delivery->id,
                    'customer_name' => $delivery->customer ? $delivery->customer->name : 'N/A',
                    'delivery_address' => $delivery->delivery_address,
                    'delivery_time' => $delivery->delivery_time,
                    'status' => $delivery->status,
                    'contact_person' => $delivery->contact_person,
                    'contact_phone' => $delivery->contact_phone,
                    'type' => $delivery->type ?? 'order',
                    'invoice_id' => $delivery->invoice_id,
                ];
            });

        return $deliveries;
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