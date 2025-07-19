<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['customer', 'user']);
        $search = $request->input('search');
        if ($search) {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        $invoices = $query->orderBy('updated_at', 'desc')->paginate(10)->withQueryString();

        // Calculate statistics
        $totalInvoices = Invoice::count();
        $totalAmount = Invoice::sum('total_amount') / 100; // Convert from cents to dollars
        $pendingInvoices = Invoice::where('status', 'pending')->count();
        $completedInvoices = Invoice::where('status', 'completed')->count();

        return inertia('invoices/Index', [
            'invoices' => $invoices,
            'filters' => [
                'search' => $search,
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
        $customers = Customer::all(['id', 'name', 'company_name']);
        $products = Product::all(['id', 'name', 'selling_price', 'stock']);
        
        return inertia('invoices/Create', [
            'customers' => $customers,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'status' => 'required|string|in:draft,pending,completed,cancelled',
            'payment_method' => 'required|string|in:cash,bank_transfer,e-wallet,other',
            'payment_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'invoice_items' => 'required|array|min:1',
            'invoice_items.*.product_id' => 'required|exists:products,id',
            'invoice_items.*.quantity' => 'required|integer|min:1',
            'invoice_items.*.price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Calculate total amount
            $totalAmount = 0;
            foreach ($validated['invoice_items'] as $item) {
                $totalAmount += $item['quantity'] * $item['price'];
            }

            // Create invoice
            $invoice = Invoice::create([
                'customer_id' => $validated['customer_id'],
                'user_id' => auth()->id(),
                'total_amount' => $totalAmount,
                'status' => $validated['status'],
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'],
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

            DB::commit();
            return redirect()->route('invoices.index');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer.media', 'user', 'invoiceItems.product']);
        return inertia('invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function edit(Invoice $invoice)
    {
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
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'status' => 'required|string|in:draft,pending,completed,cancelled',
            'payment_method' => 'required|string|in:cash,bank_transfer,e-wallet,other',
            'payment_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'invoice_items' => 'required|array|min:1',
            'invoice_items.*.product_id' => 'required|exists:products,id',
            'invoice_items.*.quantity' => 'required|integer|min:1',
            'invoice_items.*.price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Calculate total amount
            $totalAmount = 0;
            foreach ($validated['invoice_items'] as $item) {
                $totalAmount += $item['quantity'] * $item['price'];
            }

            // Update invoice
            $invoice->update([
                'customer_id' => $validated['customer_id'],
                'total_amount' => $totalAmount,
                'status' => $validated['status'],
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'],
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

            DB::commit();
            return redirect()->route('invoices.index');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(Invoice $invoice)
    {
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
} 