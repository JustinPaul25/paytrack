<?php

namespace App\Http\Controllers;

use App\Http\Requests\RefundRequestStoreRequest;
use App\Events\RefundRequestCreated;
use App\Events\NotificationCreated;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\RefundRequest;
use App\Models\Refund;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RefundRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', ''); // pending, approved, rejected, or empty for all
        $query = RefundRequest::query()->with(['invoice', 'product', 'media']);

        // Customer role: restrict to own refund requests only
        if ($request->user()?->hasRole('Customer')) {
            $customerId = Customer::where('email', $request->user()->email)->value('id');
            if ($customerId) {
                $query->where('email', $request->user()->email);
            } else {
                // If we can't match a customer record, show an empty list
                $query->whereRaw('1=0');
            }
        } elseif (!($request->user()?->hasRole('Admin') || $request->user()?->hasRole('Staff'))) {
            // Only Admin, Staff, and Customer can access
            abort(403);
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        $refundRequests = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        
        // Transform refund requests to ensure proof_images are properly formatted with URLs
        $refundRequests->through(function ($refundRequest) {
            // getMedia('proof_images') returns a collection (array) of media items
            $mediaCollection = $refundRequest->getMedia('proof_images');
            
            // Map each media item in the array to format it with URL
            $proofImages = $mediaCollection->map(function ($mediaItem) {
                // Get URL - getUrl() should work correctly with Cloudinary adapter
                $url = $mediaItem->getUrl();
                
                return [
                    'id' => $mediaItem->id,
                    'file_name' => $mediaItem->file_name,
                    'mime_type' => $mediaItem->mime_type,
                    'size' => $mediaItem->size,
                    'url' => $url,
                ];
            })->values()->toArray(); // Convert collection to array and reindex
            
            // Set the proof_images attribute (this is now an array of formatted media items)
            $refundRequest->setAttribute('proof_images', $proofImages);
            return $refundRequest;
        });

        // Get refunds data for the combined page (Admin/Staff only)
        $refunds = collect([]);
        if ($request->user()?->hasRole('Admin') || $request->user()?->hasRole('Staff')) {
            $refundStatus = $request->get('refund_status', $request->get('status', ''));
            $refundsQuery = Refund::with(['invoice', 'product', 'user'])->orderByDesc('created_at');
            if ($refundStatus && $refundStatus !== 'all') {
                $refundsQuery->where('status', $refundStatus);
            }
            $refunds = $refundsQuery->paginate(10)->withQueryString();
        }

        return inertia('refunds/Index', [
            'refundRequests' => $refundRequests,
            'refunds' => $refunds,
            'filters' => [
                'status' => $status,
            ],
        ]);
    }
    public function create(Invoice $invoice, Request $request)
    {
        // Only allow refund for completed invoices
        if ($invoice->status !== 'completed') {
            abort(403);
        }
        // Prevent creating when there is already a pending request for this invoice
        $hasPending = \App\Models\RefundRequest::where('invoice_id', $invoice->id)
            ->where('status', 'pending')
            ->exists();
        if ($hasPending) {
            return redirect()->route('invoices.show', $invoice->id)
                ->with('error', 'You already have a pending refund request for this invoice.');
        }
        // Only the owning customer can access the form
        if ($request->user()?->hasRole('Customer')) {
            $customerId = Customer::where('email', $request->user()->email)->value('id');
            if (!$customerId || $invoice->customer_id !== $customerId) {
                abort(403);
            }
        } else {
            // Allow Admin/Staff to open the form too (optional)
            if (!($request->user()?->hasRole('Admin') || $request->user()?->hasRole('Staff'))) {
                abort(403);
            }
        }

        $invoice->load(['invoiceItems.product', 'customer']);

        return inertia('refunds/Create', [
            'invoice' => [
                'id' => $invoice->id,
                'reference_number' => $invoice->reference_number,
                'customer' => [
                    'name' => $invoice->customer?->name,
                    'email' => $invoice->customer?->email,
                    'phone' => $invoice->customer?->phone,
                ],
                'invoice_items' => $invoice->invoiceItems->map(function (InvoiceItem $item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product?->name,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'total' => $item->total,
                    ];
                }),
            ],
        ]);
    }

    public function store(Invoice $invoice, Request $request)
    {
        // Only allow refund for completed invoices
        if ($invoice->status !== 'completed') {
            abort(403);
        }
        // Prevent duplicate pending request
        $hasPending = \App\Models\RefundRequest::where('invoice_id', $invoice->id)
            ->where('status', 'pending')
            ->exists();
        if ($hasPending) {
            return redirect()->route('invoices.show', $invoice->id)
                ->with('error', 'You already have a pending refund request for this invoice.');
        }
        // Customer can submit for own invoice only
        if ($request->user()?->hasRole('Customer')) {
            $customerId = Customer::where('email', $request->user()->email)->value('id');
            if (!$customerId || $invoice->customer_id !== $customerId) {
                abort(403);
            }
        } else {
            // Admin/Staff can also submit on behalf if needed
            if (!($request->user()?->hasRole('Admin') || $request->user()?->hasRole('Staff'))) {
                abort(403);
            }
        }

        // Expect a payload with selected items
        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:2000'],
            'media_link' => ['nullable', 'url', 'max:1024'], // Keep for backward compatibility
            'proof_images' => ['nullable', 'array'],
            'proof_images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:5120'], // 5MB max per image
            'request_type' => ['required', 'in:refund'],
            'damaged_items_terms' => ['nullable', 'string', 'max:2000'],
            'is_damaged' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.invoice_item_id' => ['required', 'exists:invoice_items,id'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $created = [];
        foreach ($validated['items'] as $item) {
            $invoiceItem = InvoiceItem::where('id', $item['invoice_item_id'])
                ->where('invoice_id', $invoice->id)
                ->firstOrFail();

            // Cap quantity to purchased quantity
            $qty = min((int)$item['quantity'], (int)$invoiceItem->quantity);

            $refundRequest = RefundRequest::create([
                'tracking_number' => strtoupper(Str::random(10)),
                'customer_name' => $invoice->customer?->name ?? 'Customer',
                'email' => $invoice->customer?->email,
                'phone' => $invoice->customer?->phone,
                'invoice_reference' => $invoice->reference_number,
                'invoice_id' => $invoice->id,
                'invoice_item_id' => $item['invoice_item_id'],
                'product_id' => $item['product_id'],
                'quantity' => $qty,
                // amount_requested left null; could be computed qty * price if desired
                'reason' => $validated['description'] ?? null,
                'notes' => null,
                'media_link' => $validated['media_link'] ?? null, // Keep for backward compatibility
                'status' => 'pending',
                'request_type' => 'refund',
                'damaged_items_terms' => $validated['damaged_items_terms'] ?? null,
                'is_damaged' => (bool) ($validated['is_damaged'] ?? false),
            ]);

            // Handle proof images upload
            if ($request->hasFile('proof_images')) {
                $files = $request->file('proof_images');
                // Handle both single file and array of files
                $fileArray = is_array($files) ? $files : [$files];
                foreach ($fileArray as $image) {
                    if ($image && $image->isValid()) {
                        try {
                            $refundRequest->addMedia($image->getRealPath())
                                ->usingFileName($image->hashName())
                                ->usingName($image->getClientOriginalName())
                                ->toMediaCollection('proof_images');
                        } catch (\Exception $e) {
                            \Log::error('Failed to upload proof image: ' . $e->getMessage());
                        }
                    }
                }
            }

            $created[] = $refundRequest;

            // Create notification for customer when refund request is submitted
            $customerUser = \App\Models\User::where('email', $refundRequest->email)->first();
            if ($customerUser) {
                $notification = Notification::create([
                    'user_id' => $customerUser->id,
                    'type' => 'refund.request.submitted',
                    'notifiable_id' => $refundRequest->id,
                    'notifiable_type' => RefundRequest::class,
                    'title' => 'Refund Request Submitted',
                    'message' => "Your refund request {$refundRequest->tracking_number} for invoice {$refundRequest->invoice_reference} has been submitted and is pending review.",
                    'action_url' => "/invoices/{$refundRequest->invoice_id}",
                    'read' => false,
                    'data' => [
                        'refund_request_id' => $refundRequest->id,
                        'tracking_number' => $refundRequest->tracking_number,
                        'invoice_reference' => $refundRequest->invoice_reference,
                        'invoice_id' => $refundRequest->invoice_id,
                    ],
                ]);
                // Broadcast notification
                broadcast(new NotificationCreated($notification));
            }

            // Dispatch event for real-time notifications to Admin/Staff
            event(new RefundRequestCreated($refundRequest));
        }

        return redirect()->route('invoices.show', $invoice->id)
            ->with('success', 'Your refund request has been submitted.');
    }

    public function approve(RefundRequest $refundRequest, Request $request)
    {
        // Admin and Staff only
        if (!($request->user()?->hasRole('Admin') || $request->user()?->hasRole('Staff'))) {
            abort(403);
        }
        if ($refundRequest->status !== 'pending') {
            return redirect()->back();
        }

        // Create actual refund record in refunds table
        $invoiceItem = InvoiceItem::findOrFail($refundRequest->invoice_item_id);
        $qty = min($refundRequest->quantity, $invoiceItem->quantity);
        // Calculate amount in currency (invoiceItem->price accessor returns currency)
        // The setter will convert to cents automatically
        $amount = $qty * $invoiceItem->price;
        $type = $qty >= $invoiceItem->quantity ? 'full' : 'partial';

        // Determine refund type and method
        $refundMethod = 'credit_note';
        $refundTypeValue = $type;

        $refund = Refund::create([
            'invoice_id' => $refundRequest->invoice_id,
            'invoice_item_id' => $refundRequest->invoice_item_id,
            'product_id' => $refundRequest->product_id,
            'user_id' => $request->user()->id, // processor
            'refund_number' => 'RF-' . strtoupper(Str::random(8)),
            'quantity_refunded' => $qty,
            'refund_amount' => $amount, // currency (setter converts to cents)
            'refund_type' => $refundTypeValue,
            'refund_method' => $refundMethod,
            'status' => 'approved',
            'reason' => $refundRequest->reason,
            'notes' => $refundRequest->media_link ? ('Media: ' . $refundRequest->media_link) : null,
            'reference_number' => $refundRequest->invoice_reference,
            'processed_at' => now(),
            'is_damaged' => $refundRequest->is_damaged ?? false,
        ]);

        // Link refund back to request and mark as approved
        $refundRequest->update([
            'status' => 'approved',
            'review_notes' => $request->get('review_notes'),
            'completed_refund_id' => $refund->id,
        ]);

        // Get the invoice
        $invoice = $refund->invoice;

        // Remove delivery fees when refund is approved
        // Calculate total delivery fees before removing them
        $totalDeliveryFees = $invoice->deliveries()
            ->where('delivery_fee', '>', 0)
            ->sum('delivery_fee') / 100; // Convert from cents to dollars

        // If there are delivery fees, remove them by setting to 0
        if ($totalDeliveryFees > 0) {
            // Set all delivery fees to 0 for this invoice (refund the delivery fees)
            $invoice->deliveries()
                ->where('delivery_fee', '>', 0)
                ->update(['delivery_fee' => 0]);

            // Recalculate invoice total excluding delivery fees
            // Total = Subtotal + VAT - Withholding Tax (delivery fees removed)
            $subtotal = $invoice->subtotal_amount; // Already in dollars (accessor)
            $vatAmount = $invoice->vat_amount; // Already in dollars (accessor)
            $withholdingTaxAmount = $invoice->withholding_tax_amount; // Already in dollars (accessor)

            // New total without delivery fees
            $newTotal = $subtotal + $vatAmount - $withholdingTaxAmount;

            // Update invoice total to reflect removal of delivery fees
            $invoice->total_amount = $newTotal;
            $invoice->save();
        }

        // For credit invoices: Check if refund fully settles the invoice
        $invoiceAutoSettled = false;
        if ($invoice && $invoice->payment_method === 'credit' && $invoice->payment_status === 'pending') {
            // Calculate net balance after this refund
            $netBalance = $invoice->net_balance;
            
            // If net balance is zero or negative, automatically mark as paid
            if ($netBalance <= 0) {
                $invoice->update(['payment_status' => 'paid']);
                $invoiceAutoSettled = true;
            }
        }

        // Load customer relationship if not already loaded
        if (!$invoice->relationLoaded('customer')) {
            $invoice->load('customer');
        }
        $customer = $invoice->customer;

        // Create notification for customer
        $customerUser = \App\Models\User::where('email', $refundRequest->email)->first();
        if ($customerUser) {
            $message = "Your refund request {$refundRequest->tracking_number} for invoice {$refundRequest->invoice_reference} has been approved.";
            if ($invoiceAutoSettled) {
                $message .= " Your invoice has been automatically marked as paid as the refund fully settles the amount due.";
            }
            
            $notification = \App\Models\Notification::create([
                'user_id' => $customerUser->id,
                'type' => 'refund.request.approved',
                'notifiable_id' => $refundRequest->id,
                'notifiable_type' => \App\Models\RefundRequest::class,
                'title' => 'Refund Request Approved' . ($invoiceAutoSettled ? ' - Invoice Settled' : ''),
                'message' => $message,
                'action_url' => "/invoices/{$refundRequest->invoice_id}",
                'read' => false,
                'data' => [
                    'refund_request_id' => $refundRequest->id,
                    'tracking_number' => $refundRequest->tracking_number,
                    'invoice_reference' => $refundRequest->invoice_reference,
                    'invoice_id' => $refundRequest->invoice_id,
                    'invoice_auto_settled' => $invoiceAutoSettled,
                ],
            ]);
            // Broadcast notification
            broadcast(new \App\Events\NotificationCreated($notification));
        }

        // Redirect to deliveries create page with pre-filled data for return pickup
        if ($customer && $invoice) {
            return redirect()->route('deliveries.create', [
                'invoice_id' => $invoice->id,
                'customer_id' => $customer->id,
                'refund_request_id' => $refundRequest->id,
                'refund_type' => 'return_pickup',
            ])->with('success', 'Refund request approved. Please schedule the delivery for return pickup.');
        }

        return redirect()->back()->with('success', 'Refund request approved.');
    }

    public function reject(RefundRequest $refundRequest, Request $request)
    {
        // Admin and Staff only
        if (!($request->user()?->hasRole('Admin') || $request->user()?->hasRole('Staff'))) {
            abort(403);
        }
        if ($refundRequest->status !== 'pending') {
            return redirect()->back();
        }

        $refundRequest->update([
            'status' => 'rejected',
            'review_notes' => $request->get('review_notes'),
        ]);

        // Create notification for customer
        $customerUser = \App\Models\User::where('email', $refundRequest->email)->first();
        if ($customerUser) {
            $notification = \App\Models\Notification::create([
                'user_id' => $customerUser->id,
                'type' => 'refund.request.rejected',
                'notifiable_id' => $refundRequest->id,
                'notifiable_type' => \App\Models\RefundRequest::class,
                'title' => 'Refund Request Declined',
                'message' => "Your refund request {$refundRequest->tracking_number} for invoice {$refundRequest->invoice_reference} has been declined.",
                'action_url' => "/invoices/{$refundRequest->invoice_id}",
                'read' => false,
                'data' => [
                    'refund_request_id' => $refundRequest->id,
                    'tracking_number' => $refundRequest->tracking_number,
                    'invoice_reference' => $refundRequest->invoice_reference,
                    'invoice_id' => $refundRequest->invoice_id,
                ],
            ]);
            // Broadcast notification
            broadcast(new \App\Events\NotificationCreated($notification));
        }

        return redirect()->back();
    }
}


