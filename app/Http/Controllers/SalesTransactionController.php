<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class SalesTransactionController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'month'); // week, month, year
        $customer = $request->get('customer', 'all');
        $sortBy = $request->get('sort_by', 'transaction_date');
        $sortOrder = $request->get('sort_order', 'desc');

        // Generate dummy data
        $transactions = $this->generateDummyTransactions($period, $customer, $sortBy, $sortOrder);
        $customers = $this->getDummyCustomers();
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

    private function generateDummyTransactions($period, $customer, $sortBy, $sortOrder)
    {
        $customers = [
            'Maria Santos Cruz' => 'Cruz Trading Company',
            'Juan Dela Cruz' => 'Dela Cruz Enterprises',
            'Ana Reyes Mendoza' => null,
            'Pedro Villanueva' => 'Villanueva Construction',
            'Carmen Rodriguez' => null,
            'Roberto Aquino' => 'Aquino Farm Supply',
            'Luzviminda Santos' => null,
            'Antonio Reyes' => 'Reyes Hardware Store',
            'Elena Martinez' => null,
            'Fernando Torres' => 'Torres Auto Parts',
        ];

        $products = [
            'Laptop Computer',
            'Office Chair',
            'Printer Paper',
            'Coffee Maker',
            'Desk Lamp',
            'Whiteboard',
            'Projector',
            'Wireless Mouse',
            'USB Cable',
            'Notebook',
        ];

        $paymentMethods = ['Cash', 'Credit Card', 'Bank Transfer', 'Digital Wallet'];
        $statuses = ['Completed', 'Pending', 'Cancelled', 'Refunded'];

        $transactions = [];
        $startDate = $this->getStartDate($period);

        for ($i = 1; $i <= 50; $i++) {
            $customerName = array_rand($customers);
            
            // Filter by customer if specified
            if ($customer !== 'all' && $customerName !== $customer) {
                continue;
            }

            $date = $startDate->copy()->addDays(rand(0, $startDate->diffInDays(now())));
            
            $transaction = [
                'id' => 'TXN-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'customer_name' => $customerName,
                'company_name' => $customers[$customerName],
                'product_name' => $products[array_rand($products)],
                'quantity' => rand(1, 10),
                'unit_price' => rand(100, 5000),
                'total_amount' => 0, // Will be calculated
                'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                'status' => $statuses[array_rand($statuses)],
                'transaction_date' => $date->format('Y-m-d H:i:s'),
                'invoice_number' => 'INV-' . str_pad(rand(1, 999), 6, '0', STR_PAD_LEFT),
                'notes' => rand(0, 1) ? 'Sample transaction note' : null,
            ];

            $transaction['total_amount'] = $transaction['quantity'] * $transaction['unit_price'];
            $transactions[] = $transaction;
        }

        // Sort transactions
        usort($transactions, function ($a, $b) use ($sortBy, $sortOrder) {
            $aValue = $a[$sortBy];
            $bValue = $b[$sortBy];

            if ($sortBy === 'transaction_date') {
                $aValue = strtotime($aValue);
                $bValue = strtotime($bValue);
            }

            if ($sortOrder === 'asc') {
                return $aValue <=> $bValue;
            } else {
                return $bValue <=> $aValue;
            }
        });

        return $transactions;
    }

    private function getStartDate($period)
    {
        switch ($period) {
            case 'week':
                return Carbon::now()->subWeek();
            case 'month':
                return Carbon::now()->subMonth();
            case 'year':
                return Carbon::now()->subYear();
            default:
                return Carbon::now()->subMonth();
        }
    }

    private function getDummyCustomers()
    {
        return [
            ['value' => 'all', 'label' => 'All Customers'],
            ['value' => 'Maria Santos Cruz', 'label' => 'Maria Santos Cruz - Cruz Trading Company'],
            ['value' => 'Juan Dela Cruz', 'label' => 'Juan Dela Cruz - Dela Cruz Enterprises'],
            ['value' => 'Ana Reyes Mendoza', 'label' => 'Ana Reyes Mendoza'],
            ['value' => 'Pedro Villanueva', 'label' => 'Pedro Villanueva - Villanueva Construction'],
            ['value' => 'Carmen Rodriguez', 'label' => 'Carmen Rodriguez'],
            ['value' => 'Roberto Aquino', 'label' => 'Roberto Aquino - Aquino Farm Supply'],
            ['value' => 'Luzviminda Santos', 'label' => 'Luzviminda Santos'],
            ['value' => 'Antonio Reyes', 'label' => 'Antonio Reyes - Reyes Hardware Store'],
            ['value' => 'Elena Martinez', 'label' => 'Elena Martinez'],
            ['value' => 'Fernando Torres', 'label' => 'Fernando Torres - Torres Auto Parts'],
        ];
    }

    private function calculateSummary($transactions)
    {
        $totalRevenue = collect($transactions)->sum('total_amount');
        $totalTransactions = count($transactions);
        $completedTransactions = collect($transactions)->where('status', 'Completed')->count();
        $pendingTransactions = collect($transactions)->where('status', 'Pending')->count();
        $averageOrderValue = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;

        return [
            'total_revenue' => $totalRevenue,
            'total_transactions' => $totalTransactions,
            'completed_transactions' => $completedTransactions,
            'pending_transactions' => $pendingTransactions,
            'average_order_value' => $averageOrderValue,
        ];
    }
} 