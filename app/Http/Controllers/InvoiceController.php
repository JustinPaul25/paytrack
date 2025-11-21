<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\RefundRequest;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['customer', 'user']);

        // Customer role: restrict to own invoices only
        if ($request->user() && method_exists($request->user(), 'hasRole') && $request->user()->hasRole('Customer')) {
            $customerId = Customer::where('email', $request->user()->email)->value('id');
            // If we can't match a customer record, show an empty list
            if ($customerId) {
                $query->where('customer_id', $customerId);
            } else {
                $query->whereRaw('1=0');
            }
        }
        $search = $request->input('search');
        $status = $request->input('status');

        if ($search) {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $invoices = $query->orderBy('updated_at', 'desc')->paginate(10)->withQueryString();

        // Calculate statistics (respect same visibility scope)
        $statsBase = Invoice::query();
        if ($request->user() && method_exists($request->user(), 'hasRole') && $request->user()->hasRole('Customer')) {
            $customerId = Customer::where('email', $request->user()->email)->value('id');
            if ($customerId) {
                $statsBase->where('customer_id', $customerId);
            } else {
                $statsBase->whereRaw('1=0');
            }
        }
        $totalInvoices = (clone $statsBase)->count();
        $totalAmount = ((clone $statsBase)->sum('total_amount')) / 100;
        $pendingInvoices = (clone $statsBase)->where('status', 'pending')->count();
        $completedInvoices = (clone $statsBase)->where('status', 'completed')->count();

        return inertia('invoices/Index', [
            'invoices' => $invoices,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'stats' => [
                'totalInvoices' => $totalInvoices,
                'totalAmount' => $totalAmount,
                'pendingInvoices' => $pendingInvoices,
                'paidInvoices' => $completedInvoices,
            ],
        ]);
    }

    public function create()
    {
        // Only Admin|Staff can create
        if (auth()->user()?->hasRole('Customer')) {
            abort(403);
        }
        $customers = Customer::all(['id', 'name', 'company_name']);
        $products = Product::all(['id', 'name', 'selling_price', 'stock']);
        
        return inertia('invoices/Create', [
            'customers' => $customers,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        if (auth()->user()?->hasRole('Customer')) {
            abort(403);
        }
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'status' => 'required|string|in:draft,pending,completed,cancelled',
            'payment_method' => 'required|string|in:cash,bank_transfer,e-wallet,other',
            'notes' => 'nullable|string',
            'invoice_items' => 'required|array|min:1',
            'invoice_items.*.product_id' => 'required|exists:products,id',
            'invoice_items.*.quantity' => 'required|integer|min:1',
            'invoice_items.*.price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Calculate subtotal amount
            $subtotalAmount = 0;
            foreach ($validated['invoice_items'] as $item) {
                $subtotalAmount += $item['quantity'] * $item['price'];
            }

            // Calculate VAT (12%)
            $vatRate = 12.00;
            $vatAmount = $subtotalAmount * ($vatRate / 100);
            
            // Calculate total amount (subtotal + VAT)
            $totalAmount = $subtotalAmount + $vatAmount;

            // Create invoice
            $invoice = Invoice::create([
                'customer_id' => $validated['customer_id'],
                'user_id' => auth()->id(),
                'subtotal_amount' => $subtotalAmount,
                'vat_amount' => $vatAmount,
                'vat_rate' => $vatRate,
                'total_amount' => $totalAmount,
                'status' => $validated['status'],
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'],
            ]);

            // Create invoice items
            foreach ($validated['invoice_items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['quantity'] * $item['price'],
                ]);
            }

            // Deduct stock if invoice is completed
            if ($validated['status'] === 'completed') {
                $this->deductStockForInvoice($invoice);
            }

            DB::commit();
            return redirect()->route('invoices.index');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Invoice $invoice)
    {
        // Customers can only view their own invoices
        if (auth()->user()?->hasRole('Customer')) {
            $customerId = Customer::where('email', auth()->user()->email)->value('id');
            if (!$customerId || $invoice->customer_id !== $customerId) {
                abort(403);
            }
        }
        $invoice->load(['customer.media', 'user', 'invoiceItems.product', 'refunds.product', 'deliveries']);
        $refunds = $invoice->refunds()->with('product')->orderByDesc('created_at')->get()->map(function ($r) {
            return [
                'id' => $r->id,
                'refund_number' => $r->refund_number,
                'product_name' => $r->product?->name,
                'quantity_refunded' => $r->quantity_refunded,
                'refund_amount' => $r->refund_amount, // accessor returns currency
                'status' => $r->status,
                'created_at' => $r->created_at?->format('M d, Y'),
            ];
        });
        $refundRequests = RefundRequest::with('product')
            ->where('invoice_id', $invoice->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($rq) {
                return [
                    'id' => $rq->id,
                    'tracking_number' => $rq->tracking_number,
                    'product_name' => $rq->product?->name,
                    'quantity' => $rq->quantity,
                    'reason' => $rq->reason,
                    'review_notes' => $rq->review_notes,
                    'status' => $rq->status,
                    'created_at' => $rq->created_at?->format('M d, Y'),
                    'media_link' => $rq->media_link,
                ];
            });
        // Get customers for delivery form
        $customers = Customer::all(['id', 'name', 'company_name', 'address', 'location']);
        
        // Get deliveries for this invoice
        $deliveries = $invoice->deliveries()->orderByDesc('created_at')->get()->map(function ($d) {
            return [
                'id' => $d->id,
                'delivery_address' => $d->delivery_address,
                'contact_person' => $d->contact_person,
                'contact_phone' => $d->contact_phone,
                'delivery_date' => $d->delivery_date?->format('M d, Y'),
                'delivery_time' => $d->delivery_time,
                'status' => $d->status,
                'delivery_fee' => $d->delivery_fee,
                'notes' => $d->notes,
                'created_at' => $d->created_at?->format('M d, Y'),
            ];
        });
        
        return inertia('invoices/Show', [
            'invoice' => $invoice,
            'refunds' => $refunds,
            'refundRequests' => $refundRequests,
            'deliveries' => $deliveries,
            'customers' => $customers,
        ]);
    }

    public function edit(Invoice $invoice)
    {
        if (auth()->user()?->hasRole('Customer')) {
            abort(403);
        }
        $customers = Customer::all(['id', 'name', 'company_name']);
        $products = Product::all(['id', 'name', 'selling_price', 'stock']);
        $invoice->load(['invoiceItems.product']);
        
        return inertia('invoices/Edit', [
            'invoice' => $invoice,
            'customers' => $customers,
            'products' => $products,
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        if (auth()->user()?->hasRole('Customer')) {
            abort(403);
        }
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'status' => 'required|string|in:draft,pending,completed,cancelled',
            'payment_method' => 'required|string|in:cash,bank_transfer,e-wallet,other',
            'notes' => 'nullable|string',
            'invoice_items' => 'required|array|min:1',
            'invoice_items.*.product_id' => 'required|exists:products,id',
            'invoice_items.*.quantity' => 'required|integer|min:1',
            'invoice_items.*.price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $oldStatus = $invoice->status;
            
            // Calculate subtotal amount
            $subtotalAmount = 0;
            foreach ($validated['invoice_items'] as $item) {
                $subtotalAmount += $item['quantity'] * $item['price'];
            }

            // Calculate VAT (12%)
            $vatRate = 12.00;
            $vatAmount = $subtotalAmount * ($vatRate / 100);
            
            // Calculate total amount (subtotal + VAT)
            $totalAmount = $subtotalAmount + $vatAmount;

            // If old status was completed, restore stock before deleting items
            if ($oldStatus === 'completed') {
                $this->restoreStockForInvoice($invoice);
            }

            // Update invoice
            $invoice->update([
                'customer_id' => $validated['customer_id'],
                'subtotal_amount' => $subtotalAmount,
                'vat_amount' => $vatAmount,
                'vat_rate' => $vatRate,
                'total_amount' => $totalAmount,
                'status' => $validated['status'],
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'],
            ]);

            // Delete existing invoice items
            $invoice->invoiceItems()->delete();

            // Create new invoice items
            foreach ($validated['invoice_items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['quantity'] * $item['price'],
                ]);
            }

            // If new status is completed, deduct stock for new items
            if ($validated['status'] === 'completed') {
                $this->deductStockForInvoice($invoice);
            }

            DB::commit();
            return redirect()->route('invoices.index');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(Invoice $invoice)
    {
        if (auth()->user()?->hasRole('Customer')) {
            abort(403);
        }
        DB::beginTransaction();
        try {
            $invoice->invoiceItems()->delete();
            $invoice->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function markPaid(Invoice $invoice)
    {
        if (auth()->user()?->hasRole('Customer')) {
            abort(403);
        }
        
        DB::beginTransaction();
        try {
            // Allow quick status update without requiring full invoice payload
            if ($invoice->status !== 'completed') {
                $invoice->update(['status' => 'completed']);
                // Deduct stock when marking as paid/completed
                $this->deductStockForInvoice($invoice);
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->back();
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
                'notes' => "Invoice {$invoice->reference_number} completed",
            ]);
        }
    }

    /**
     * Restore stock for all items in an invoice (when status changes from completed)
     */
    private function restoreStockForInvoice(Invoice $invoice)
    {
        // Get existing stock movements for this invoice
        $stockMovements = StockMovement::where('invoice_id', $invoice->id)
            ->where('type', 'sale')
            ->get();

        if ($stockMovements->isEmpty()) {
            // No stock movements to restore
            return;
        }

        foreach ($stockMovements as $movement) {
            $product = Product::find($movement->product_id);
            if (!$product) {
                continue;
            }

            // Restore the stock
            $beforeStock = (int) $product->stock;
            $quantityToRestore = abs($movement->quantity); // Make it positive
            $product->stock = $beforeStock + $quantityToRestore;
            $product->save();

            // Create a reversal movement for traceability (optional, or you could delete the original)
            // For now, we'll just update the product stock and mark the movement as reversed
            // by creating a new adjustment movement
            StockMovement::create([
                'product_id' => $product->id,
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id(),
                'type' => 'adjustment',
                'quantity' => $quantityToRestore, // positive for inbound
                'quantity_before' => $beforeStock,
                'quantity_after' => $product->stock,
                'notes' => "Stock restored - Invoice {$invoice->reference_number} status changed",
            ]);
        }

        // Optionally delete or mark the original movements as reversed
        // For simplicity, we'll leave them for audit trail and just create adjustment entries
    }
} 