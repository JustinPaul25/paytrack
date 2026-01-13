<?php

namespace App\Http\Controllers;

use App\Models\Refund;
use App\Models\RefundRequest;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        if (!($request->user()?->hasRole('Admin') || $request->user()?->hasRole('Staff'))) {
            abort(403);
        }
        $status = $request->get('status', ''); // approved, processed, completed, cancelled, or empty for all
        $isDamaged = $request->get('is_damaged');
        $query = Refund::with(['invoice', 'product', 'user'])->orderByDesc('created_at');
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($isDamaged !== null && $isDamaged !== '') {
            $query->where('is_damaged', filter_var($isDamaged, FILTER_VALIDATE_BOOLEAN));
        }
        $refunds = $query->paginate(10)->withQueryString();

        return inertia('refunds/Manage', [
            'refunds' => $refunds,
            'filters' => ['status' => $status, 'is_damaged' => $isDamaged],
        ]);
    }

    public function process(Refund $refund, Request $request)
    {
        if (!($request->user()?->hasRole('Admin') || $request->user()?->hasRole('Staff'))) {
            abort(403);
        }
        if (!in_array($refund->status, ['approved', 'processed'])) {
            return redirect()->back();
        }
        $data = $request->validate([
            'refund_method' => 'required|string|in:cash,bank_transfer,e-wallet,credit_note',
            'reference_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);
        $refund->update([
            'refund_method' => $data['refund_method'],
            'reference_number' => $data['reference_number'] ?? $refund->reference_number,
            'notes' => $data['notes'] ?? $refund->notes,
            'status' => 'processed',
            'processed_at' => now(),
        ]);
        return redirect()->back();
    }

    public function complete(Refund $refund, Request $request)
    {
        if (!($request->user()?->hasRole('Admin') || $request->user()?->hasRole('Staff'))) {
            abort(403);
        }
        if (!in_array($refund->status, ['processed', 'approved'])) {
            return redirect()->back();
        }

        // If items are damaged, automatically set return_to_stock to false
        $defaultReturnToStock = $refund->is_damaged ? false : true;
        $returnToStock = (bool) $request->get('return_to_stock', $defaultReturnToStock);
        $notes = $request->get('notes');

        // Handle inventory
        // For exchanges: return original item to stock AND deduct exchange item from stock
        // For refunds: return item to stock (if returnToStock) or write-off
        // Note: Damaged items are automatically written off (not returned to stock)
        
        if ($refund->refund_type === 'exchange' && $refund->exchange_product_id && $refund->exchange_quantity) {
            // EXCHANGE: Handle both returned item and exchanged item
            
            // 1. Return original item to stock
            $originalProduct = \App\Models\Product::find($refund->product_id);
            if ($originalProduct) {
                $beforeOriginal = (int) $originalProduct->stock;
                $originalProduct->stock = $beforeOriginal + (int) $refund->quantity_refunded;
                $originalProduct->save();

                // Record stock movement for returned item
                \App\Models\StockMovement::create([
                    'product_id' => $refund->product_id,
                    'refund_id' => $refund->id,
                    'invoice_id' => $refund->invoice_id,
                    'user_id' => $request->user()->id,
                    'type' => 'refund',
                    'quantity' => (int) $refund->quantity_refunded,
                    'quantity_before' => $beforeOriginal,
                    'quantity_after' => $originalProduct->stock,
                    'notes' => 'Item returned from exchange. ' . ($notes ?? ''),
                ]);
            }
            
            // 2. Deduct exchange item from stock
            $exchangeProduct = \App\Models\Product::find($refund->exchange_product_id);
            if ($exchangeProduct) {
                $beforeExchange = (int) $exchangeProduct->stock;
                $exchangeQty = (int) $refund->exchange_quantity;
                
                // Check stock availability
                if ($beforeExchange < $exchangeQty) {
                    return redirect()->back()
                        ->with('error', "Insufficient stock for exchange product. Available: {$beforeExchange}, Required: {$exchangeQty}");
                }
                
                $exchangeProduct->stock = $beforeExchange - $exchangeQty;
                $exchangeProduct->save();

                // Record stock movement for exchanged item (sale type, negative quantity)
                \App\Models\StockMovement::create([
                    'product_id' => $refund->exchange_product_id,
                    'refund_id' => $refund->id,
                    'invoice_id' => $refund->invoice_id,
                    'user_id' => $request->user()->id,
                    'type' => 'sale',
                    'quantity' => -1 * $exchangeQty,
                    'quantity_before' => $beforeExchange,
                    'quantity_after' => $exchangeProduct->stock,
                    'notes' => 'Item exchanged. ' . ($notes ?? ''),
                ]);
            }
        } elseif ($returnToStock) {
            // REFUND: Return item to stock
            $product = \App\Models\Product::find($refund->product_id);
            if ($product) {
                $before = (int) $product->stock;
                $product->stock = $before + (int) $refund->quantity_refunded;
                $product->save();

                // Record stock movement
                \App\Models\StockMovement::create([
                    'product_id' => $refund->product_id,
                    'refund_id' => $refund->id,
                    'invoice_id' => $refund->invoice_id,
                    'user_id' => $request->user()->id,
                    'type' => 'refund',
                    'quantity' => (int) $refund->quantity_refunded,
                    'quantity_before' => $before,
                    'quantity_after' => $product->stock,
                    'notes' => $notes,
                ]);
            }
        } else {
            // Write-off: Record a write-off movement for traceability
            \App\Models\StockMovement::create([
                'product_id' => $refund->product_id,
                'refund_id' => $refund->id,
                'invoice_id' => $refund->invoice_id,
                'user_id' => $request->user()->id,
                'type' => 'writeoff',
                'quantity' => -1 * (int) $refund->quantity_refunded,
                'notes' => $notes,
            ]);
        }

        $refund->update([
            'status' => 'completed',
            'completed_at' => now(),
            'notes' => $notes ?? $refund->notes,
        ]);

        // For credit invoices: Check if refund fully settles the invoice
        $invoice = $refund->invoice;
        $invoiceAutoSettled = false;
        if ($invoice && $invoice->payment_method === 'credit' && $invoice->payment_status === 'pending') {
            // Calculate net balance after this refund completion
            $netBalance = $invoice->net_balance;
            
            // If net balance is zero or negative, automatically mark as paid
            if ($netBalance <= 0) {
                $invoice->update(['payment_status' => 'paid']);
                $invoiceAutoSettled = true;
                
                // Create notification for customer if invoice was auto-settled
                $customerUser = \App\Models\User::where('email', $invoice->customer?->email)->first();
                if ($customerUser) {
                    $notification = \App\Models\Notification::create([
                        'user_id' => $customerUser->id,
                        'type' => 'invoice.auto_settled',
                        'notifiable_id' => $invoice->id,
                        'notifiable_type' => \App\Models\Invoice::class,
                        'title' => 'Invoice Automatically Settled',
                        'message' => "Invoice {$invoice->reference_number} has been automatically marked as paid. The refund amount fully covers the invoice balance.",
                        'action_url' => "/invoices/{$invoice->id}",
                        'read' => false,
                        'data' => [
                            'invoice_id' => $invoice->id,
                            'invoice_reference' => $invoice->reference_number,
                            'refund_id' => $refund->id,
                        ],
                    ]);
                    // Broadcast notification
                    broadcast(new \App\Events\NotificationCreated($notification));
                }
            }
        }

        return redirect()->back();
    }

    public function cancel(Refund $refund, Request $request)
    {
        if (!($request->user()?->hasRole('Admin') || $request->user()?->hasRole('Staff'))) {
            abort(403);
        }
        if (in_array($refund->status, ['completed', 'cancelled'])) {
            return redirect()->back();
        }
        $refund->update([
            'status' => 'cancelled',
        ]);
        return redirect()->back();
    }

    public function damagedItems(Request $request)
    {
        if (!($request->user()?->hasRole('Admin') || $request->user()?->hasRole('Staff'))) {
            abort(403);
        }

        $status = $request->get('status', ''); // Filter by status for refunds
        $requestStatus = $request->get('request_status', ''); // Filter by status for refund requests

        // Get damaged refunds with stock movements
        $refundsQuery = Refund::with(['invoice', 'product', 'user'])
            ->where('is_damaged', true)
            ->orderByDesc('created_at');
        
        if ($status && $status !== 'all') {
            $refundsQuery->where('status', $status);
        }
        
        $refunds = $refundsQuery->paginate(10, ['*'], 'refunds_page')->withQueryString();

        // Get damaged refund requests
        $requestsQuery = RefundRequest::with(['invoice', 'product'])
            ->where('is_damaged', true)
            ->orderByDesc('created_at');
        
        if ($requestStatus && $requestStatus !== 'all') {
            $requestsQuery->where('status', $requestStatus);
        }
        
        $refundRequests = $requestsQuery->paginate(10, ['*'], 'requests_page')->withQueryString();

        // Get all stock movements for damaged items (writeoffs)
        $damagedStockMovements = StockMovement::whereHas('refund', function ($query) {
                $query->where('is_damaged', true);
            })
            ->with(['refund', 'product', 'invoice', 'user'])
            ->orderByDesc('created_at')
            ->get();

        // Calculate statistics
        $stats = [
            'total_damaged_refunds' => Refund::where('is_damaged', true)->count(),
            'total_damaged_requests' => RefundRequest::where('is_damaged', true)->count(),
            'pending_requests' => RefundRequest::where('is_damaged', true)->where('status', 'pending')->count(),
            'total_damaged_value' => Refund::where('is_damaged', true)->sum('refund_amount') / 100, // Convert from cents
            'total_writeoffs' => StockMovement::whereHas('refund', function ($query) {
                $query->where('is_damaged', true);
            })->where('type', 'writeoff')->count(),
        ];

        return inertia('refunds/DamagedItems', [
            'refunds' => $refunds,
            'refundRequests' => $refundRequests,
            'stockMovements' => $damagedStockMovements,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
                'request_status' => $requestStatus,
            ],
        ]);
    }
}


