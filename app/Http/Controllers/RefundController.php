<?php

namespace App\Http\Controllers;

use App\Models\Refund;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;
use App\Http\Requests\RefundRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Refund::with(['invoice.customer', 'product', 'user']);
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('refund_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('invoice.customer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('product', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }
        $refunds = $query->orderBy('updated_at', 'desc')->paginate(10)->withQueryString();

        // Calculate statistics
        $totalRefunds = Refund::count();
        $totalAmount = Refund::sum('refund_amount') / 100; // Convert from cents to dollars
        $pendingRefunds = Refund::where('status', 'pending')->count();
        $completedRefunds = Refund::where('status', 'completed')->count();

        return Inertia::render('refunds/Index', [
            'refunds' => $refunds,
            'filters' => [
                'search' => $search,
            ],
            'stats' => [
                'totalRefunds' => $totalRefunds,
                'totalAmount' => $totalAmount,
                'pendingRefunds' => $pendingRefunds,
                'completedRefunds' => $completedRefunds,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $invoiceId = $request->query('invoice_id');
        $invoiceItemId = $request->query('invoice_item_id');

        $invoice = null;
        $invoiceItem = null;
        $availableInvoices = collect();

        if ($invoiceId) {
            $invoice = Invoice::with(['customer', 'invoiceItems.product'])->find($invoiceId);
        } else {
            // Get invoices that can be refunded (not too old, etc.)
            $availableInvoices = Invoice::with(['customer', 'invoiceItems.product'])
                ->where('status', 'completed')
                ->where('created_at', '>=', now()->subMonths(3)) // Only recent invoices
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Get some statistics for debugging
            $totalInvoices = Invoice::count();
            $completedInvoices = Invoice::where('status', 'completed')->count();
            $recentInvoices = Invoice::where('created_at', '>=', now()->subMonths(3))->count();
        }

        if ($invoiceItemId) {
            $invoiceItem = InvoiceItem::with(['product', 'invoice.customer'])->find($invoiceItemId);
        }

        return Inertia::render('refunds/Create', [
            'invoice' => $invoice,
            'invoiceItem' => $invoiceItem,
            'availableInvoices' => $availableInvoices,
            'invoiceStats' => [
                'totalInvoices' => $totalInvoices ?? 0,
                'completedInvoices' => $completedInvoices ?? 0,
                'recentInvoices' => $recentInvoices ?? 0,
                'availableForRefund' => $availableInvoices->count(),
            ],
            'refundTypes' => [
                ['value' => 'full', 'label' => 'Full Refund'],
                ['value' => 'partial', 'label' => 'Partial Refund'],
                ['value' => 'exchange', 'label' => 'Exchange'],
            ],
            'refundMethods' => [
                ['value' => 'cash', 'label' => 'Cash'],
                ['value' => 'bank_transfer', 'label' => 'Bank Transfer'],
                ['value' => 'e-wallet', 'label' => 'E-Wallet'],
                ['value' => 'credit_note', 'label' => 'Credit Note'],
                ['value' => 'exchange', 'label' => 'Exchange'],
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RefundRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $refund = Refund::create([
                'invoice_id' => $request->invoice_id,
                'invoice_item_id' => $request->invoice_item_id,
                'product_id' => $request->product_id,
                'user_id' => auth()->id(),
                'quantity_refunded' => $request->quantity_refunded,
                'refund_amount' => $request->refund_amount,
                'refund_type' => $request->refund_type,
                'refund_method' => $request->refund_method,
                'reason' => $request->reason,
                'notes' => $request->notes,
                'reference_number' => $request->reference_number === 'Auto-generated' ? null : $request->reference_number,
                'status' => 'pending',
            ]);

            // Update product stock if it's a return (not exchange)
            if ($request->refund_type !== 'exchange') {
                $product = Product::find($request->product_id);
                $product->increment('stock', $request->quantity_refunded);
            }

            DB::commit();

            return redirect()->route('refunds.index')
                ->with('success', 'Refund created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Refund creation failed: ' . $e->getMessage());
            
            return back()->withInput()
                ->with('error', 'Failed to create refund. Please try again.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Refund $refund): Response
    {
        $refund->load(['invoice.customer', 'invoiceItem.product', 'product', 'user']);

        return Inertia::render('refunds/Show', [
            'refund' => $refund,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Refund $refund): Response
    {
        // Only allow editing if refund is pending
        if (!$refund->isPending()) {
            return redirect()->route('refunds.show', $refund)
                ->with('error', 'Only pending refunds can be edited.');
        }

        $refund->load(['invoice.customer', 'invoiceItem.product', 'product']);

        return Inertia::render('refunds/Edit', [
            'refund' => $refund,
            'refundTypes' => [
                ['value' => 'full', 'label' => 'Full Refund'],
                ['value' => 'partial', 'label' => 'Partial Refund'],
                ['value' => 'exchange', 'label' => 'Exchange'],
            ],
            'refundMethods' => [
                ['value' => 'cash', 'label' => 'Cash'],
                ['value' => 'bank_transfer', 'label' => 'Bank Transfer'],
                ['value' => 'e-wallet', 'label' => 'E-Wallet'],
                ['value' => 'credit_note', 'label' => 'Credit Note'],
                ['value' => 'exchange', 'label' => 'Exchange'],
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RefundRequest $request, Refund $refund): RedirectResponse
    {
        if (!$refund->isPending()) {
            return redirect()->route('refunds.show', $refund)
                ->with('error', 'Only pending refunds can be updated.');
        }

        try {
            DB::beginTransaction();

            $refund->update([
                'quantity_refunded' => $request->quantity_refunded,
                'refund_amount' => $request->refund_amount,
                'refund_type' => $request->refund_type,
                'refund_method' => $request->refund_method,
                'reason' => $request->reason,
                'notes' => $request->notes,
                'reference_number' => $request->reference_number,
            ]);

            DB::commit();

            return redirect()->route('refunds.index')
                ->with('success', 'Refund updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Refund update failed: ' . $e->getMessage());
            
            return back()->withInput()
                ->with('error', 'Failed to update refund. Please try again.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Refund $refund): RedirectResponse
    {
        try {
            DB::beginTransaction();

            // Restore product stock if it was a return
            if ($refund->refund_type !== 'exchange' && $refund->status === 'completed') {
                $product = Product::find($refund->product_id);
                $product->decrement('stock', $refund->quantity_refunded);
            }

            $refund->delete();

            DB::commit();

            return redirect()->route('refunds.index')
                ->with('success', 'Refund deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Refund deletion failed: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to delete refund. Please try again.');
        }
    }

    /**
     * Approve a refund.
     */
    public function approve(Refund $refund): RedirectResponse
    {
        if (!$refund->isPending()) {
            return back()->with('error', 'Only pending refunds can be approved.');
        }

        try {
            $refund->approve();
            return back()->with('success', 'Refund approved successfully.');
        } catch (\Exception $e) {
            Log::error('Refund approval failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to approve refund.');
        }
    }

    /**
     * Process a refund.
     */
    public function process(Refund $refund): RedirectResponse
    {
        if (!$refund->isApproved()) {
            return back()->with('error', 'Only approved refunds can be processed.');
        }

        try {
            $refund->process();
            return back()->with('success', 'Refund processed successfully.');
        } catch (\Exception $e) {
            Log::error('Refund processing failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to process refund.');
        }
    }

    /**
     * Complete a refund.
     */
    public function complete(Refund $refund): RedirectResponse
    {
        if (!$refund->isProcessed()) {
            return back()->with('error', 'Only processed refunds can be completed.');
        }

        try {
            DB::beginTransaction();

            $refund->complete();

            // Update product stock if it's a return (not exchange)
            if ($refund->refund_type !== 'exchange') {
                $product = Product::find($refund->product_id);
                $product->increment('stock', $refund->quantity_refunded);
            }

            DB::commit();

            return back()->with('success', 'Refund completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Refund completion failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to complete refund.');
        }
    }

    /**
     * Cancel a refund.
     */
    public function cancel(Refund $refund): RedirectResponse
    {
        if (!$refund->canBeCancelled()) {
            return back()->with('error', 'This refund cannot be cancelled.');
        }

        try {
            $refund->cancel();
            return back()->with('success', 'Refund cancelled successfully.');
        } catch (\Exception $e) {
            Log::error('Refund cancellation failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to cancel refund.');
        }
    }

    /**
     * Get invoice items for a specific invoice.
     */
    public function getInvoiceItems(Request $request): JsonResponse
    {
        $invoiceId = $request->query('invoice_id');
        
        if (!$invoiceId) {
            return response()->json([]);
        }

        $invoiceItems = InvoiceItem::with(['product'])
            ->where('invoice_id', $invoiceId)
            ->get();

        return response()->json($invoiceItems);
    }

    /**
     * Get refund statistics.
     */
    public function getStats(): JsonResponse
    {
        $stats = [
            'total' => Refund::count(),
            'pending' => Refund::pending()->count(),
            'approved' => Refund::approved()->count(),
            'processed' => Refund::processed()->count(),
            'completed' => Refund::completed()->count(),
            'cancelled' => Refund::cancelled()->count(),
        ];

        return response()->json($stats);
    }
}
