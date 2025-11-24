<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Models\Customer;
use App\Models\Expense;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ReminderController extends Controller
{
    /**
     * Display a listing of reminders.
     */
    public function index(Request $request)
    {
        $query = Reminder::with(['customer', 'invoice', 'order', 'expense']);

        $type = $request->input('type');
        $status = $request->input('status');
        $priority = $request->input('priority');
        $search = $request->input('search');
        $filter = $request->input('filter', 'all'); // all, overdue, upcoming, today

        // Filter by type
        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        // Filter by status
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        } else {
            // Default to pending only
            $query->where('status', 'pending');
        }

        // Filter by priority
        if ($priority && $priority !== 'all') {
            $query->where('priority', $priority);
        }

        // Filter by date
        switch ($filter) {
            case 'overdue':
                $query->overdue();
                break;
            case 'upcoming':
                $query->upcoming(7);
                break;
            case 'today':
                $query->whereDate('due_date', Carbon::today());
                break;
        }

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        $reminders = $query->orderBy('due_date', 'asc')
            ->orderBy('priority', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Calculate statistics
        $stats = [
            'total' => Reminder::where('status', 'pending')->count(),
            'overdue' => Reminder::overdue()->count(),
            'upcoming' => Reminder::upcoming(7)->count(),
            'today' => Reminder::where('status', 'pending')
                ->whereDate('due_date', Carbon::today())
                ->count(),
            'byType' => [
                'bill_payment' => Reminder::where('status', 'pending')
                    ->where('type', 'bill_payment')
                    ->count(),
                'customer_due' => Reminder::where('status', 'pending')
                    ->where('type', 'customer_due')
                    ->count(),
                'credit_term' => Reminder::where('status', 'pending')
                    ->where('type', 'credit_term')
                    ->count(),
            ],
        ];

        return Inertia::render('reminders/Index', [
            'reminders' => $reminders,
            'filters' => [
                'type' => $type ?? 'all',
                'status' => $status ?? 'pending',
                'priority' => $priority ?? 'all',
                'search' => $search,
                'filter' => $filter,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Mark a reminder as read.
     */
    public function markAsRead(Request $request, Reminder $reminder)
    {
        $reminder->update(['is_read' => true]);

        return redirect()->back()->with('success', 'Reminder marked as read.');
    }

    /**
     * Mark a reminder as completed.
     */
    public function markAsCompleted(Request $request, Reminder $reminder)
    {
        $reminder->update(['status' => 'completed', 'is_read' => true]);

        return redirect()->back()->with('success', 'Reminder marked as completed.');
    }

    /**
     * Dismiss a reminder.
     */
    public function dismiss(Request $request, Reminder $reminder)
    {
        $reminder->update(['status' => 'dismissed', 'is_read' => true]);

        return redirect()->back()->with('success', 'Reminder dismissed.');
    }

    /**
     * Mark all reminders as read.
     */
    public function markAllAsRead(Request $request)
    {
        Reminder::where('is_read', false)
            ->where('status', 'pending')
            ->update(['is_read' => true]);

        return redirect()->back()->with('success', 'All reminders marked as read.');
    }

    /**
     * Show the form for creating a new reminder.
     */
    public function create()
    {
        $customers = Customer::all(['id', 'name', 'company_name']);
        $expenses = Expense::orderBy('date', 'desc')->limit(50)->get(['id', 'expense_type', 'amount', 'date']);
        
        return Inertia::render('reminders/Create', [
            'customers' => $customers,
            'expenses' => $expenses,
        ]);
    }

    /**
     * Store a newly created reminder.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:bill_payment,customer_due,credit_term',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'priority' => 'required|string|in:low,medium,high',
            'customer_id' => 'nullable|exists:customers,id',
            'expense_id' => 'nullable|exists:expenses,id',
        ]);

        // Calculate priority based on due date if not explicitly set
        if (empty($validated['priority'])) {
            $daysUntilDue = Carbon::parse($validated['due_date'])->diffInDays(Carbon::now());
            $validated['priority'] = $daysUntilDue <= 7 ? 'high' : ($daysUntilDue <= 14 ? 'medium' : 'low');
        }

        $reminder = Reminder::create([
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_date' => $validated['due_date'],
            'amount' => $validated['amount'] ?? null,
            'currency' => $validated['currency'] ?? 'USD',
            'priority' => $validated['priority'],
            'status' => 'pending',
            'is_read' => false,
            'customer_id' => $validated['customer_id'] ?? null,
            'expense_id' => $validated['expense_id'] ?? null,
            'remindable_type' => $validated['expense_id'] ? Expense::class : null,
            'remindable_id' => $validated['expense_id'] ?? null,
        ]);

        return redirect()->route('reminders.index')
            ->with('success', 'Reminder created successfully.');
    }
}
