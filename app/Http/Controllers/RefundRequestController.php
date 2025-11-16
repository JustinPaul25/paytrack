<?php

namespace App\Http\Controllers;

use App\Http\Requests\RefundRequestStoreRequest;
use App\Mail\RefundRequestSubmitted;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\RefundRequest;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RefundRequestController extends Controller
{
    public function index(Request $request)
    {
        // Admin only list for now
        if (!($request->user()?->hasRole('Admin'))) {
            abort(403);
        }

        $status = $request->get('status', 'pending');
        $query = RefundRequest::query()->with(['invoice', 'product']);
        if ($status) {
            $query->where('status', $status);
        }
        $refundRequests = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return inertia('refunds/Index', [
            'refundRequests' => $refundRequests,
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
            'media_link' => ['nullable', 'url', 'max:1024'],
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
                'media_link' => $validated['media_link'] ?? null,
                'status' => 'pending',
            ]);

            $created[] = $refundRequest;

            // Email confirmation to requester if available
            if (!empty($refundRequest->email)) {
                Mail::to($refundRequest->email)->queue(new RefundRequestSubmitted($refundRequest));
            }
        }

        return redirect()->route('invoices.show', $invoice->id)
            ->with('success', 'Your refund request has been submitted.');
    }

    public function approve(RefundRequest $refundRequest, Request $request)
    {
        // Admin only
        if (!($request->user()?->hasRole('Admin'))) {
            abort(403);
        }
        if ($refundRequest->status !== 'pending') {
            return redirect()->back();
        }

        // Create actual refund record in refunds table
        $invoiceItem = InvoiceItem::findOrFail($refundRequest->invoice_item_id);
        $qty = min($refundRequest->quantity, $invoiceItem->quantity);
        $amount = $qty * $invoiceItem->price;
        $type = $qty >= $invoiceItem->quantity ? 'full' : 'partial';

        $refund = Refund::create([
            'invoice_id' => $refundRequest->invoice_id,
            'invoice_item_id' => $refundRequest->invoice_item_id,
            'product_id' => $refundRequest->product_id,
            'user_id' => $request->user()->id, // processor
            'refund_number' => 'RF-' . strtoupper(Str::random(8)),
            'quantity_refunded' => $qty,
            'refund_amount' => $amount,
            'refund_type' => $type,
            'refund_method' => 'credit_note', // default method for system-approved refunds
            'status' => 'approved',
            'reason' => $refundRequest->reason,
            'notes' => $refundRequest->media_link ? ('Media: ' . $refundRequest->media_link) : null,
            'reference_number' => $refundRequest->invoice_reference,
            'processed_at' => now(),
        ]);

        // Link refund back to request and mark as converted
        $refundRequest->update([
            'status' => 'converted',
            'review_notes' => $request->get('review_notes'),
            'converted_refund_id' => $refund->id,
        ]);

        return redirect()->back();
    }
}


