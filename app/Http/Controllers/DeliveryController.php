<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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

    public function shortcut()
    {
        $deliveries = Delivery::with(['customer', 'invoice'])->orderByDesc('delivery_date')->get();

        $colorPalette = [
            '#1d4ed8',
            '#047857',
            '#7c3aed',
            '#ea580c',
            '#4338ca',
            '#dc2626',
            '#0f766e',
            '#db2777',
        ];

        $endpoints = $deliveries->values()->map(function (Delivery $delivery, int $index) use ($colorPalette) {
            $customer = $delivery->customer;
            $invoice = $delivery->invoice;
            $locationPayload = $customer?->location ?? [];
            $location = $this->normalizeLocation($locationPayload);
            $lat = $location['lat'] ?? null;
            $lng = $location['lng'] ?? null;

            $tag = $customer?->company_name ?? $customer?->name ?? 'Delivery ' . $delivery->id;
            $displayName = $customer?->name ?? $tag;

            return [
                'id' => $delivery->id,
                'name' => $displayName,
                'status' => $delivery->status,
                'tag' => $tag,
                'tagColor' => $colorPalette[$index % count($colorPalette)],
                'coordinates' => $lat !== null && $lng !== null ? [(float) $lat, (float) $lng] : null,
                'delivery_address' => $delivery->delivery_address,
                'delivery_date' => optional($delivery->delivery_date)->toDateString(),
                'delivery_time' => $delivery->delivery_time,
                'delivery_fee' => $delivery->delivery_fee,
                'contact_person' => $delivery->contact_person,
                'contact_phone' => $delivery->contact_phone,
                'invoice' => $invoice ? [
                    'id' => $invoice->id,
                    'total_amount' => $invoice->total_amount,
                    'status' => $invoice->status,
                    'created_at' => optional($invoice->created_at)->toDateString(),
                ] : null,
                'customer' => $customer ? [
                    'name' => $customer->name,
                    'company_name' => $customer->company_name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                ] : null,
            ];
        })->toArray();

        $stats = [
            'totalDeliveries' => $deliveries->count(),
            'inProgressDeliveries' => $deliveries->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'pendingDeliveries' => $deliveries->where('status', 'pending')->count(),
            'completedDeliveries' => $deliveries->where('status', 'completed')->count(),
            'cancelledDeliveries' => $deliveries->where('status', 'cancelled')->count(),
            'totalRevenue' => $deliveries->sum(function (Delivery $delivery) {
                $invoice = $delivery->invoice;
                return $invoice ? $invoice->total_amount : 0;
            }),
        ];

        $defaultCenter = [14.5995, 120.9842];
        foreach ($endpoints as $endpoint) {
            if (isset($endpoint['coordinates'])) {
                $defaultCenter = $endpoint['coordinates'];
                break;
            }
        }

        return Inertia::render('deliveries/Shortcut', [
            'deliveryEndpoints' => $endpoints,
            'stats' => $stats,
            'mapCenter' => $defaultCenter,
        ]);
    }

    /**
     * @param  mixed  $locationPayload
     * @return array{lat: float|null, lng: float|null}
     */
    protected function normalizeLocation(mixed $locationPayload): array
    {
        if (is_string($locationPayload)) {
            $decoded = json_decode($locationPayload, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $locationPayload = $decoded;
            }
        }

        if (!is_array($locationPayload)) {
            return ['lat' => null, 'lng' => null];
        }

        $lat = $locationPayload['lat'] ?? ($locationPayload['latitude'] ?? null);
        $lng = $locationPayload['lng'] ?? ($locationPayload['longitude'] ?? null);

        return [
            'lat' => $lat !== null ? (float) $lat : null,
            'lng' => $lng !== null ? (float) $lng : null,
        ];
    }
}
