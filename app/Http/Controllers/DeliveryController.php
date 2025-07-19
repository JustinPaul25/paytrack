<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $query = Delivery::with(['customer', 'invoice']);
        $search = $request->input('search');
        if ($search) {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        $deliveries = $query->orderBy('updated_at', 'desc')->paginate(10)->withQueryString();

        // Calculate statistics
        $totalDeliveries = Delivery::count();
        $pendingDeliveries = Delivery::where('status', 'pending')->count();
        $completedDeliveries = Delivery::where('status', 'completed')->count();
        $cancelledDeliveries = Delivery::where('status', 'cancelled')->count();

        return inertia('deliveries/Index', [
            'deliveries' => $deliveries,
            'filters' => [
                'search' => $search,
            ],
            'stats' => [
                'totalDeliveries' => $totalDeliveries,
                'pendingDeliveries' => $pendingDeliveries,
                'completedDeliveries' => $completedDeliveries,
                'cancelledDeliveries' => $cancelledDeliveries,
            ],
        ]);
    }

    public function create()
    {
        $customers = Customer::all(['id', 'name', 'company_name', 'address', 'location']);
        $invoices = Invoice::with('customer')->get(['id', 'customer_id', 'total_amount']);
        
        return inertia('deliveries/Create', [
            'customers' => $customers,
            'invoices' => $invoices,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'delivery_address' => 'required|string|max:500',
            'contact_person' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:20',
            'delivery_date' => 'required|date|after_or_equal:today',
            'delivery_time' => 'required|string|max:50',
            'status' => 'required|string|in:pending,completed,cancelled',
            'notes' => 'nullable|string',
            'delivery_fee' => 'required|numeric|min:0',
        ]);

        $delivery = Delivery::create($validated);
        
        return redirect()->route('deliveries.index');
    }

    public function show(Delivery $delivery)
    {
        $delivery->load(['customer', 'invoice']);
        return inertia('deliveries/Show', [
            'delivery' => $delivery,
        ]);
    }

    public function edit(Delivery $delivery)
    {
        $customers = Customer::all(['id', 'name', 'company_name']);
        $invoices = Invoice::with('customer')->get(['id', 'customer_id', 'total_amount']);
        $delivery->load(['customer', 'invoice']);
        
        return inertia('deliveries/Edit', [
            'delivery' => $delivery,
            'customers' => $customers,
            'invoices' => $invoices,
        ]);
    }

    public function update(Request $request, Delivery $delivery)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'delivery_address' => 'required|string|max:500',
            'contact_person' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:20',
            'delivery_date' => 'required|date',
            'delivery_time' => 'required|string|max:50',
            'status' => 'required|string|in:pending,completed,cancelled',
            'notes' => 'nullable|string',
            'delivery_fee' => 'required|numeric|min:0',
        ]);

        $delivery->update($validated);
        
        return redirect()->route('deliveries.index');
    }

    public function destroy(Delivery $delivery)
    {
        $delivery->delete();
    }
}
