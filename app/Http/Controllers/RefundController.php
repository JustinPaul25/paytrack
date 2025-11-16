<?php

namespace App\Http\Controllers;

use App\Models\Refund;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        if (!($request->user()?->hasRole('Admin'))) {
            abort(403);
        }
        $status = $request->get('status', 'approved'); // approved, processed, completed, cancelled
        $query = Refund::with(['invoice', 'product', 'user'])->orderByDesc('created_at');
        if ($status) {
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
        if (!($request->user()?->hasRole('Admin'))) {
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
        if (!($request->user()?->hasRole('Admin'))) {
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
        return redirect()->back();
    }

    public function cancel(Refund $refund, Request $request)
    {
        if (!($request->user()?->hasRole('Admin'))) {
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


