<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\StockMovement;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $query = Delivery::with(['customer', 'invoice']);
        $search = $request->input('search');
        $customerId = $request->input('customer_id');
        if ($search) {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        if ($customerId) {
            $query->where('customer_id', $customerId);
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
                'customer_id' => $customerId,
            ],
            'stats' => [
                'totalDeliveries' => $totalDeliveries,
                'pendingDeliveries' => $pendingDeliveries,
                'completedDeliveries' => $completedDeliveries,
                'cancelledDeliveries' => $cancelledDeliveries,
            ],
        ]);
    }

    public function create(Request $request)
    {
        // Get customers with properly cast location
        $customers = Customer::select(['id', 'name', 'company_name', 'address', 'purok', 'barangay', 'city_municipality', 'province', 'location', 'phone'])
            ->get()
            ->map(function ($customer) {
                // Ensure location is properly formatted as array or null
                // Convert string coordinates to numbers
                $location = $customer->location;
                if ($location && is_array($location) && isset($location['lat'], $location['lng'])) {
                    // Convert strings to numbers if needed
                    $lat = is_numeric($location['lat']) ? (float) $location['lat'] : null;
                    $lng = is_numeric($location['lng']) ? (float) $location['lng'] : null;
                    
                    // Ensure it's valid and not 0,0
                    if ($lat !== null && $lng !== null && ($lat != 0 || $lng != 0)) {
                        $location = ['lat' => $lat, 'lng' => $lng];
                    } else {
                        $location = null;
                    }
                } else {
                    $location = null;
                }
                
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'company_name' => $customer->company_name,
                    'address' => $customer->address,
                    'purok' => $customer->purok,
                    'barangay' => $customer->barangay,
                    'city_municipality' => $customer->city_municipality,
                    'province' => $customer->province,
                    'phone' => $customer->phone,
                    'location' => $location,
                ];
            });
        $preselectedInvoiceId = $request->query('invoice_id');
        $refundRequestId = $request->query('refund_request_id');
        $refundType = $request->query('refund_type'); // 'return_pickup' or other types
        
        // Load refund request data if provided
        $refundRequestData = null;
        $refundRequestInvoiceId = null;
        if ($refundRequestId) {
            $refundRequest = \App\Models\RefundRequest::with(['product', 'invoice.customer'])->find($refundRequestId);
            if ($refundRequest) {
                $refundRequestInvoiceId = $refundRequest->invoice_id;
                $customer = $refundRequest->invoice->customer ?? null;
                $product = $refundRequest->product;
                
                // Build delivery address from customer's address fields
                $addressParts = [];
                if ($customer) {
                    if ($customer->purok) {
                        $addressParts[] = $customer->purok;
                    }
                    if ($customer->barangay) {
                        $addressParts[] = $customer->barangay;
                    }
                    if ($customer->city_municipality) {
                        $addressParts[] = $customer->city_municipality;
                    }
                    if ($customer->province) {
                        $addressParts[] = $customer->province;
                    }
                }
                $deliveryAddress = !empty($addressParts) ? implode(', ', $addressParts) : ($customer->address ?? 'To be confirmed');
                
                // Build notes for return pickup
                $productName = $product?->name ?? 'Product';
                $notes = "Return pickup for refund request {$refundRequest->tracking_number}. Product: {$productName} (Qty: {$refundRequest->quantity})";
                
                $refundRequestData = [
                    'id' => $refundRequest->id,
                    'tracking_number' => $refundRequest->tracking_number,
                    'invoice_id' => $refundRequest->invoice_id,
                    'customer_id' => $customer?->id,
                    'product_name' => $productName,
                    'quantity' => $refundRequest->quantity,
                    'delivery_address' => $deliveryAddress,
                    'contact_person' => $customer->name ?? 'Customer',
                    'contact_phone' => $customer->phone ?? '',
                    'notes' => $notes,
                ];
                
                // If invoice_id wasn't provided, use the refund request's invoice
                if (!$preselectedInvoiceId && $refundRequest->invoice_id) {
                    $preselectedInvoiceId = $refundRequest->invoice_id;
                }
            }
        }
        
        // Get invoices - include pending ones, and also include the preselected invoice or refund request invoice if they exist
        $invoiceIdsToInclude = array_filter([$preselectedInvoiceId, $refundRequestInvoiceId]);
        $invoicesQuery = Invoice::with('customer')->where('status', 'pending');
        if (!empty($invoiceIdsToInclude)) {
            $invoicesQuery->orWhereIn('id', $invoiceIdsToInclude);
        }
        $invoices = $invoicesQuery->get(['id', 'customer_id', 'total_amount', 'reference_number']);
        $baseDeliveryFee = (float) Setting::get('base_delivery_fee', '50.00');
        $ratePerKm = (float) Setting::get('rate_per_km', '10.00');
        
        // Get delivery origin location from settings
        $deliveryOriginLocation = Setting::get('delivery_origin_location', null);
        if (is_string($deliveryOriginLocation)) {
            $deliveryOriginLocation = json_decode($deliveryOriginLocation, true);
        }
        
        return inertia('deliveries/Create', [
            'customers' => $customers,
            'invoices' => $invoices,
            'preselectedInvoiceId' => $preselectedInvoiceId ? (int) $preselectedInvoiceId : null,
            'refundRequest' => $refundRequestData,
            'refundType' => $refundType,
            'baseDeliveryFee' => $baseDeliveryFee,
            'ratePerKm' => $ratePerKm,
            'deliveryOriginLocation' => $deliveryOriginLocation,
        ]);
    }

    public function store(Request $request)
    {
        // Check if this is a refund-related delivery (return pickup)
        $isRefundDelivery = $request->has('refund_request_id') || 
                           (isset($request->notes) && stripos($request->notes, 'Return pickup for refund request') !== false);
        
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'required|exists:invoices,id',
            'delivery_address' => 'required|string|max:500',
            'contact_person' => 'required|string|max:255',
            // Philippine mobile: 09XXXXXXXXX, +639XXXXXXXXX, or 639XXXXXXXXX
            'contact_phone' => ['required','regex:/^(?:\\+?63|0)9\\d{9}$/'],
            'delivery_date' => 'required|date|after_or_equal:today',
            'delivery_time' => 'required|string|max:50',
            'status' => 'required|string|in:pending,completed,cancelled',
            'notes' => 'nullable|string',
            'delivery_fee' => $isRefundDelivery ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
        ]);

        // Default to 0 if not provided for refund deliveries (but allow setting a fee)
        if ($isRefundDelivery && !isset($validated['delivery_fee'])) {
            $validated['delivery_fee'] = 0;
        }
        
        // Note: Pass delivery_fee as dollars - the Delivery model setter will convert to cents with proper rounding
        // Do NOT convert here, as the model setter will handle the conversion

        DB::beginTransaction();
        try {
            $delivery = Delivery::create($validated);
            
            // Update invoice status and total when delivery is created
            if ($validated['invoice_id']) {
                $invoice = Invoice::find($validated['invoice_id']);
                
                if ($invoice) {
                    // Calculate new total: Subtotal + VAT - Withholding Tax + all delivery fees
                    $subtotal = $invoice->subtotal_amount; // Already in dollars (accessor)
                    $vatAmount = $invoice->vat_amount; // Already in dollars (accessor)
                    $withholdingTaxAmount = $invoice->withholding_tax_amount; // Already in dollars (accessor)
                    
                    // Sum all deliveries (including the one just created) - delivery_fee is stored in cents, convert to dollars
                    $allDeliveryFees = $invoice->deliveries()
                        ->sum('delivery_fee') / 100;
                    
                    // Total = Subtotal + VAT - Withholding Tax + Delivery Fees
                    $newTotal = $subtotal + $vatAmount - $withholdingTaxAmount + $allDeliveryFees;
                    
                    // Update invoice total to include all delivery fees
                    $invoice->total_amount = $newTotal;
                    
                    // If delivery is created with 'pending' status (Out for Delivery), update invoice status to 'pending'
                    // This indicates the invoice is now "out for delivery"
                    if ($validated['status'] === 'pending' && !in_array($invoice->status, ['completed', 'cancelled'])) {
                        $invoice->status = 'pending';
                    }
                    // If delivery is created as completed, also mark the associated invoice as completed
                    // Only mark as paid if payment method is NOT credit (credit invoices must be manually marked as paid by staff)
                    elseif ($validated['status'] === 'completed') {
                        if ($invoice->status !== 'completed') {
                            $invoice->status = 'completed';
                            // Only auto-mark as paid if payment method is not credit
                            if ($invoice->payment_method !== 'credit') {
                                $invoice->payment_status = 'paid';
                            }
                            // Deduct stock for the invoice if not already done
                            $this->deductStockForInvoice($invoice);
                        } elseif ($invoice->status === 'completed') {
                            // If invoice is already completed, only update payment status if not credit
                            if ($invoice->payment_method !== 'credit') {
                                $invoice->payment_status = 'paid';
                            }
                        }
                    }
                    
                    $invoice->save();
                }
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
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
        $invoices = Invoice::with('customer')->get(['id', 'customer_id', 'total_amount', 'reference_number']);
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
            // Philippine mobile: 09XXXXXXXXX, +639XXXXXXXXX, or 639XXXXXXXXX
            'contact_phone' => ['required','regex:/^(?:\\+?63|0)9\\d{9}$/'],
            'delivery_date' => 'required|date',
            'delivery_time' => 'required|string|max:50',
            'status' => 'required|string|in:pending,completed,cancelled',
            'notes' => 'nullable|string',
            'delivery_fee' => 'required|numeric|min:0',
            'proof_of_delivery' => 'nullable|image|max:5120', // Max 5MB
        ]);

        $oldStatus = $delivery->status;
        $newStatus = $validated['status'];

        DB::beginTransaction();
        try {
            $oldDeliveryFee = $delivery->delivery_fee; // Get old fee before update
            
            // Handle proof of delivery file upload
            if ($request->hasFile('proof_of_delivery')) {
                // Remove old proof of delivery if exists
                $delivery->clearMediaCollection('proof_of_delivery');
                
                // Add new proof of delivery
                $delivery->addMediaFromRequest('proof_of_delivery')
                    ->usingFileName($request->file('proof_of_delivery')->hashName())
                    ->usingName($request->file('proof_of_delivery')->getClientOriginalName())
                    ->toMediaCollection('proof_of_delivery');
            }
            
            // Remove proof_of_delivery from validated array since it's already handled above
            unset($validated['proof_of_delivery']);
            
            $delivery->update($validated);
            $delivery->refresh(); // Refresh to get the latest data
            
            // Update invoice total if delivery fee changed
            $invoiceId = $validated['invoice_id'] ?? $delivery->invoice_id;
            if ($invoiceId) {
                $invoice = Invoice::find($invoiceId);
                if ($invoice) {
                    // Recalculate total: Subtotal + VAT - Withholding Tax + all delivery fees
                    $subtotal = $invoice->subtotal_amount; // Already in dollars (accessor)
                    $vatAmount = $invoice->vat_amount; // Already in dollars (accessor)
                    $withholdingTaxAmount = $invoice->withholding_tax_amount; // Already in dollars (accessor)
                    
                    // Sum all delivery fees (including the updated one) - delivery_fee is stored in cents, convert to dollars
                    $allDeliveryFees = $invoice->deliveries()
                        ->sum('delivery_fee') / 100;
                    
                    // Total = Subtotal + VAT - Withholding Tax + Delivery Fees
                    $newTotal = $subtotal + $vatAmount - $withholdingTaxAmount + $allDeliveryFees;
                    
                    // Update invoice total
                    $invoice->total_amount = $newTotal;
                    $invoice->save();
                    
                    // If delivery is being marked as completed, also mark the associated invoice as completed
                    // Only mark as paid if payment method is NOT credit (credit invoices must be manually marked as paid by staff)
                    if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                        if ($invoice->status !== 'completed') {
                            $updateData = ['status' => 'completed'];
                            // Only auto-mark as paid if payment method is not credit
                            if ($invoice->payment_method !== 'credit') {
                                $updateData['payment_status'] = 'paid';
                            }
                            $invoice->update($updateData);
                            // Deduct stock for the invoice if not already done
                            $this->deductStockForInvoice($invoice);
                        } elseif ($invoice->status === 'completed') {
                            // If invoice is already completed, only update payment status if not credit
                            if ($invoice->payment_method !== 'credit') {
                                $invoice->update(['payment_status' => 'paid']);
                            }
                        }
                    }
                }
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        return redirect()->route('deliveries.index');
    }

    public function destroy(Delivery $delivery)
    {
        $delivery->delete();
    }

    public function customerDeliveries(Request $request)
    {
        // Find customer by matching user's email
        $user = $request->user();
        $customer = Customer::where('email', $user->email)->first();

        $query = Delivery::with(['customer', 'invoice']);
        
        if (!$customer) {
            // Return empty paginated result if no customer found
            $query->whereRaw('1=0');
            $totalDeliveries = 0;
            $pendingDeliveries = 0;
            $completedDeliveries = 0;
            $cancelledDeliveries = 0;
        } else {
            $query->where('customer_id', $customer->id);
            
            // Calculate statistics for this customer only
            $totalDeliveries = Delivery::where('customer_id', $customer->id)->count();
            $pendingDeliveries = Delivery::where('customer_id', $customer->id)->where('status', 'pending')->count();
            $completedDeliveries = Delivery::where('customer_id', $customer->id)->where('status', 'completed')->count();
            $cancelledDeliveries = Delivery::where('customer_id', $customer->id)->where('status', 'cancelled')->count();
        }
        
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('delivery_address', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('contact_phone', 'like', "%{$search}%");
            });
        }

        $deliveries = $query->orderBy('delivery_date', 'desc')
            ->orderBy('delivery_time', 'desc')
            ->paginate(10)
            ->withQueryString();

        return inertia('deliveries/CustomerIndex', [
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

    public function customerShow(Request $request, Delivery $delivery)
    {
        // Find customer by matching user's email
        $user = $request->user();
        $customer = Customer::where('email', $user->email)->first();

        // Verify the delivery belongs to this customer
        if (!$customer || $delivery->customer_id !== $customer->id) {
            abort(403, 'Unauthorized access to this delivery.');
        }

        $delivery->load(['customer', 'invoice']);
        return inertia('deliveries/CustomerShow', [
            'delivery' => $delivery,
        ]);
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
     * Deduct stock for all items in an invoice
     */
    private function deductStockForInvoice(Invoice $invoice)
    {
        // Check if stock was already deducted for this invoice
        $existingMovements = StockMovement::where('invoice_id', $invoice->id)
            ->where('type', 'sale')
            ->exists();
        
        if ($existingMovements) {
            // Stock already deducted, skip
            return;
        }

        $invoiceItems = $invoice->invoiceItems()->with('product')->get();

        foreach ($invoiceItems as $invoiceItem) {
            $product = $invoiceItem->product;
            if (!$product) {
                continue;
            }

            $beforeStock = (int) $product->stock;
            $quantityToDeduct = (int) $invoiceItem->quantity;

            // Check stock availability
            if ($beforeStock < $quantityToDeduct) {
                throw new \Exception("Insufficient stock for product {$product->name}. Available: {$beforeStock}, Required: {$quantityToDeduct}");
            }

            $product->stock = $beforeStock - $quantityToDeduct;
            $product->save();

            // Record stock movement
            StockMovement::create([
                'product_id' => $product->id,
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id(),
                'type' => 'sale',
                'quantity' => -1 * $quantityToDeduct, // negative for outbound
                'quantity_before' => $beforeStock,
                'quantity_after' => $product->stock,
                'notes' => "Invoice {$invoice->reference_number} completed via delivery",
            ]);
        }
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
