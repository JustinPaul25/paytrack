<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerLogController extends Controller
{
    /**
     * Display a listing of customer logs.
     * Only Admin and Staff can view logs.
     */
    public function index(Request $request)
    {
        // Check authorization
        if (!auth()->user() || !(auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Staff'))) {
            abort(403, 'Unauthorized access.');
        }

        $search = $request->input('search');
        $customerId = $request->input('customer_id');
        $action = $request->input('action');

        $query = CustomerLog::with(['customer', 'user']);

        // Filter by customer if specified
        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        // Filter by action if specified
        if ($action) {
            $query->where('action', $action);
        }

        // Search functionality
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get unique actions for filter dropdown
        $actions = CustomerLog::distinct()->pluck('action')->sort()->values();

        return Inertia::render('customers/Logs', [
            'logs' => $logs,
            'filters' => [
                'search' => $search,
                'customer_id' => $customerId,
                'action' => $action,
            ],
            'actions' => $actions,
        ]);
    }

    /**
     * Display logs for a specific customer.
     */
    public function show(Customer $customer, Request $request)
    {
        // Check authorization
        if (!auth()->user() || !(auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Staff'))) {
            abort(403, 'Unauthorized access.');
        }

        $action = $request->input('action');

        $query = $customer->logs()->with(['user']);

        // Filter by action if specified
        if ($action) {
            $query->where('action', $action);
        }

        $logs = $query
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get unique actions for filter dropdown
        $actions = $customer->logs()->distinct()->pluck('action')->sort()->values();

        return Inertia::render('customers/Logs', [
            'customer' => $customer->only(['id', 'name', 'email']),
            'logs' => $logs,
            'filters' => [
                'action' => $action,
            ],
            'actions' => $actions,
        ]);
    }
}
