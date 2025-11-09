<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
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
        $search = $request->input('search', '');
        
        $expenses = Expense::query()
            ->with(['user:id,name', 'branch:id,name'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('expense_type', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate statistics
        $totalExpenses = $expenses->sum('amount');
        $totalCount = $expenses->count();
        $expensesByType = $expenses->groupBy('expense_type')->map->sum('amount');

        return Inertia::render('expenses/Index', [
            'expenses' => $expenses,
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
    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Add the authenticated user's ID
        $validated['user_id'] = auth()->id();

        Expense::create($validated);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense created successfully');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expense $expense): Response
    {
        return Inertia::render('expenses/Edit', [
            'expense' => $expense,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $validated = $request->validated();
        
        $expense->update($validated);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'Expense deleted successfully');
    }
} 