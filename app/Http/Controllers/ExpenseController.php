<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        // Demo data - no database operations
        $search = $request->input('search', '');
        
        $expenses = collect([
            [
                'id' => 1,
                'amount' => 5000.00,
                'expense_type' => 'Salary',
                'description' => 'Monthly salary payment for January 2024',
                'date' => '2024-01-15',
                'created_at' => '2024-01-15 09:00:00',
                'updated_at' => '2024-01-15 09:00:00',
            ],
            [
                'id' => 2,
                'amount' => 2500.00,
                'expense_type' => 'Bills',
                'description' => 'Electricity bill for December 2023',
                'date' => '2024-01-10',
                'created_at' => '2024-01-10 14:30:00',
                'updated_at' => '2024-01-10 14:30:00',
            ],
            [
                'id' => 3,
                'amount' => 1500.00,
                'expense_type' => 'Transportation',
                'description' => 'Fuel expenses for company vehicles',
                'date' => '2024-01-08',
                'created_at' => '2024-01-08 11:15:00',
                'updated_at' => '2024-01-08 11:15:00',
            ],
            [
                'id' => 4,
                'amount' => 3000.00,
                'expense_type' => 'Cash Advance',
                'description' => 'Advance payment for business trip',
                'date' => '2024-01-05',
                'created_at' => '2024-01-05 16:45:00',
                'updated_at' => '2024-01-05 16:45:00',
            ],
            [
                'id' => 5,
                'amount' => 8000.00,
                'expense_type' => 'Tax',
                'description' => 'Quarterly tax payment',
                'date' => '2024-01-01',
                'created_at' => '2024-01-01 10:00:00',
                'updated_at' => '2024-01-01 10:00:00',
            ],
            [
                'id' => 6,
                'amount' => 1200.00,
                'expense_type' => 'Bills',
                'description' => 'Internet and phone bills',
                'date' => '2023-12-28',
                'created_at' => '2023-12-28 13:20:00',
                'updated_at' => '2023-12-28 13:20:00',
            ],
            [
                'id' => 7,
                'amount' => 2000.00,
                'expense_type' => 'Transportation',
                'description' => 'Vehicle maintenance and repairs',
                'date' => '2023-12-25',
                'created_at' => '2023-12-25 08:30:00',
                'updated_at' => '2023-12-25 08:30:00',
            ],
            [
                'id' => 8,
                'amount' => 4500.00,
                'expense_type' => 'Salary',
                'description' => 'Bonus payment for December 2023',
                'date' => '2023-12-20',
                'created_at' => '2023-12-20 15:45:00',
                'updated_at' => '2023-12-20 15:45:00',
            ],
        ]);

        // Filter by search if provided
        if ($search) {
            $expenses = $expenses->filter(function ($expense) use ($search) {
                return str_contains(strtolower($expense['expense_type']), strtolower($search)) ||
                       str_contains(strtolower($expense['description']), strtolower($search));
            });
        }

        // Calculate statistics
        $totalExpenses = $expenses->sum('amount');
        $totalCount = $expenses->count();
        $expensesByType = $expenses->groupBy('expense_type')->map->sum('amount');

        return Inertia::render('expenses/Index', [
            'expenses' => $expenses->values(),
            'filters' => [
                'search' => $search,
            ],
            'stats' => [
                'totalExpenses' => $totalExpenses,
                'totalCount' => $totalCount,
                'expensesByType' => $expensesByType,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('expenses/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Demo - just redirect back with success message
        return redirect()->route('expenses.index')
            ->with('success', 'Expense created successfully (Demo - no data saved)');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): Response
    {
        // Demo data for editing
        $expense = [
            'id' => $id,
            'amount' => 5000.00,
            'expense_type' => 'Salary',
            'description' => 'Monthly salary payment for January 2024',
            'date' => '2024-01-15',
        ];

        return Inertia::render('expenses/Edit', [
            'expense' => $expense,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        // Demo - just redirect back with success message
        return redirect()->route('expenses.index')
            ->with('success', 'Expense updated successfully (Demo - no data saved)');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        // Demo - just redirect back with success message
        return redirect()->route('expenses.index')
            ->with('success', 'Expense deleted successfully (Demo - no data saved)');
    }
} 