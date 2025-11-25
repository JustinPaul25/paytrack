<?php

namespace App\Http\Controllers;

use App\Models\Refund;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        if (!($request->user()?->hasRole('Admin') || $request->user()?->hasRole('Staff'))) {
            abort(403);
        }
        $status = $request->get('status', ''); // approved, processed, completed, cancelled, or empty for all
        $query = Refund::with(['invoice', 'product', 'user'])->orderByDesc('created_at');
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        $refunds = $query->paginate(10)->withQueryString();

        return inertia('refunds/Manage', [
            'refunds' => $refunds,
            'filters' => ['status' => $status],
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

        $returnToStock = (bool) $request->get('return_to_stock', true);
        $notes = $request->get('notes');

        // Handle inventory if returning to stock
        if ($returnToStock) {
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
            // Record a write-off movement for traceability
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
}


