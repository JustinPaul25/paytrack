<?php

namespace App\Http\Controllers;

use App\Events\OrderCommentAdded;
use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderComment;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Display a listing of orders for customers (their own orders) or staff (all orders).
     */
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'approvedBy', 'invoice']);

        // Customer role: restrict to own orders only
        if ($request->user() && method_exists($request->user(), 'hasRole') && $request->user()->hasRole('Customer')) {
            $customerId = Customer::where('email', $request->user()->email)->value('id');
            if ($customerId) {
                $query->where('customer_id', $customerId);
            } else {
                $query->whereRaw('1=0');
            }
        }

        $search = $request->input('search');
        $status = $request->input('status');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        // Calculate statistics
        $statsBase = Order::query();
        if ($request->user() && method_exists($request->user(), 'hasRole') && $request->user()->hasRole('Customer')) {
            $customerId = Customer::where('email', $request->user()->email)->value('id');
            if ($customerId) {
                $statsBase->where('customer_id', $customerId);
            } else {
                $statsBase->whereRaw('1=0');
            }
        }
        
        $totalOrders = (clone $statsBase)->count();
        $pendingOrders = (clone $statsBase)->where('status', 'pending')->count();
        $approvedOrders = (clone $statsBase)->where('status', 'approved')->count();
        $rejectedOrders = (clone $statsBase)->where('status', 'rejected')->count();

        return inertia('orders/Index', [
            'orders' => $orders,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'stats' => [
                'totalOrders' => $totalOrders,
                'pendingOrders' => $pendingOrders,
                'approvedOrders' => $approvedOrders,
                'rejectedOrders' => $rejectedOrders,
            ],
        ]);
    }

    /**
     * Show the form for creating a new order (for customers only).
     */
    public function create()
    {
        // Only customers can create orders
        if (!auth()->user()?->hasRole('Customer')) {
            abort(403);
        }

        // Get customer ID
        $customerId = Customer::where('email', auth()->user()->email)->value('id');
        if (!$customerId) {
            abort(403, 'Customer record not found.');
        }

        $products = Product::all(['id', 'name', 'selling_price', 'stock', 'unit', 'category_id']);
        $categories = Category::all(['id', 'name']);
        
        return inertia('orders/Create', [
            'customer_id' => $customerId,
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created order (for customers only).
     */
    public function store(Request $request)
    {
        // Only customers can create orders
        if (!auth()->user()?->hasRole('Customer')) {
            abort(403);
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'delivery_type' => 'required|in:pickup,delivery',
            'payment_method' => 'required|string|in:cash,credit',
            'credit_term_days' => 'nullable|integer|min:0|max:365',
            'notes' => 'nullable|string',
            'order_items' => 'required|array|min:1',
            'order_items.*.product_id' => 'required|exists:products,id',
            'order_items.*.quantity' => 'required|integer|min:1',
        ]);

        // Verify customer ID matches authenticated user
        $customerId = Customer::where('email', auth()->user()->email)->value('id');
        if (!$customerId || $validated['customer_id'] != $customerId) {
            abort(403, 'Unauthorized to create order for this customer.');
        }

        DB::beginTransaction();
        try {
            // Calculate subtotal amount and validate stock availability
            $subtotalAmount = 0;
            $stockIssues = [];

            foreach ($validated['order_items'] as $index => $item) {
                $product = Product::findOrFail($item['product_id']);
                
                // Check stock availability (just for validation, don't reserve yet)
                if ($item['quantity'] > $product->stock) {
                    $stockIssues[] = "{$product->name}: requested {$item['quantity']}, available {$product->stock}";
                }
                
                $price = $product->selling_price;
                $subtotalAmount += $item['quantity'] * $price;
            }

            if (!empty($stockIssues)) {
                DB::rollBack();
                return redirect()->back()->withErrors([
                    'order_items' => 'Stock availability issues: ' . implode('; ', $stockIssues)
                ])->withInput();
            }

            // VAT is already included in product prices
            // Calculate VAT amount for display: VAT = subtotal * (vat_rate / (100 + vat_rate))
            $vatRate = 12.00;
            $vatAmount = $subtotalAmount * ($vatRate / (100 + $vatRate));
            
            // Total amount equals subtotal (VAT already included)
            // Delivery fee will be added when delivery is created
            $totalAmount = $subtotalAmount;

            // Validate minimum order amount for delivery
            $minimumDeliveryAmount = 500.00; // 500 pesos minimum for delivery orders
            if ($validated['delivery_type'] === 'delivery' && $totalAmount < $minimumDeliveryAmount) {
                DB::rollBack();
                return redirect()->back()->withErrors([
                    'delivery_type' => "Minimum order amount for delivery is ₱{$minimumDeliveryAmount}. Your current order total is ₱" . number_format($totalAmount, 2) . "."
                ])->withInput();
            }

            // Create order with status 'pending'
            $order = Order::create([
                'customer_id' => $validated['customer_id'],
                'subtotal_amount' => $subtotalAmount,
                'vat_amount' => $vatAmount,
                'vat_rate' => $vatRate,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'delivery_type' => $validated['delivery_type'],
                'payment_method' => $validated['payment_method'],
                'credit_term_days' => $validated['credit_term_days'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create order items
            foreach ($validated['order_items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $price = $product->selling_price;
                
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'total' => $item['quantity'] * $price,
                ]);
            }

            DB::commit();

            // Dispatch OrderCreated event to create notifications and broadcast
            event(new OrderCreated($order));
            return redirect()->route('orders.index')->with('success', 'Order created successfully. Waiting for staff approval.');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        // Customers can only view their own orders
        if (auth()->user()?->hasRole('Customer')) {
            $customerId = Customer::where('email', auth()->user()->email)->value('id');
            if (!$customerId || $order->customer_id !== $customerId) {
                abort(403);
            }
        }

        $order->load([
            'customer.media',
            'approvedBy',
            'invoice.invoiceItems.product',
            'orderItems.product',
            'comments.user'
        ]);

        // Check if there are any stock issues
        $hasStockIssues = false;
        foreach ($order->orderItems as $orderItem) {
            if ($orderItem->quantity > $orderItem->product->stock) {
                $hasStockIssues = true;
                break;
            }
        }

        return inertia('orders/Show', [
            'order' => $order,
            'hasStockIssues' => $hasStockIssues,
        ]);
    }

    /**
     * Approve an order and create invoice (for staff only).
     */
    public function approve(Order $order, Request $request)
    {
        // Only Admin|Staff can approve orders
        if (!auth()->user() || !(auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Staff'))) {
            abort(403);
        }

        if ($order->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending orders can be approved.');
        }

        DB::beginTransaction();
        try {
            // Check product availability and reserve stock
            $stockIssues = [];
            $orderItems = $order->orderItems()->with('product')->get();

            foreach ($orderItems as $orderItem) {
                $product = $orderItem->product;
                if ($orderItem->quantity > $product->stock) {
                    $stockIssues[] = "{$product->name}: requested {$orderItem->quantity}, available {$product->stock}";
                }
            }

            if (!empty($stockIssues)) {
                DB::rollBack();
                return redirect()->back()->withErrors([
                    'stock' => 'Stock availability issues: ' . implode('; ', $stockIssues)
                ])->withInput();
            }

            // All products available - proceed with approval and invoice creation
            // Deduct stock and create stock movements
            foreach ($orderItems as $orderItem) {
                $product = $orderItem->product;
                $beforeStock = (int) $product->stock;
                $product->stock = $beforeStock - (int) $orderItem->quantity;
                $product->save();

                // Record stock movement
                StockMovement::create([
                    'product_id' => $product->id,
                    'invoice_id' => null, // Will be updated after invoice creation
                    'user_id' => auth()->id(),
                    'type' => 'sale',
                    'quantity' => -1 * (int) $orderItem->quantity, // negative for outbound
                    'quantity_before' => $beforeStock,
                    'quantity_after' => $product->stock,
                    'notes' => "Order {$order->reference_number} approved",
                ]);
            }

            // Create invoice
            $invoice = Invoice::create([
                'customer_id' => $order->customer_id,
                'user_id' => auth()->id(),
                'subtotal_amount' => $order->subtotal_amount,
                'vat_amount' => $order->vat_amount,
                'vat_rate' => $order->vat_rate,
                'total_amount' => $order->total_amount,
                'status' => 'pending',
                'payment_method' => $order->payment_method ?? 'cash',
                'invoice_type' => $order->delivery_type === 'delivery' ? 'delivery' : 'walk_in',
                'notes' => $order->notes,
            ]);

            // Create invoice items
            foreach ($orderItems as $orderItem) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $orderItem->product_id,
                    'quantity' => $orderItem->quantity,
                    'price' => $orderItem->price,
                    'total' => $orderItem->total,
                ]);
            }

            // Update stock movements with invoice_id
            $productIds = $orderItems->pluck('product_id')->toArray();
            StockMovement::whereIn('product_id', $productIds)
                ->whereNull('invoice_id')
                ->where('type', 'sale')
                ->where('notes', "Order {$order->reference_number} approved")
                ->update(['invoice_id' => $invoice->id]);

            // Reload order to get fresh data
            $order->refresh();
            
            // Update order
            $order->update([
                'status' => 'approved',
                'invoice_id' => $invoice->id,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Reload relationships
            $order->load(['approvedBy', 'invoice']);

            DB::commit();
            
            // Broadcast order update for real-time updates
            broadcast(new OrderUpdated($order))->toOthers();
            
            // If order is delivery type, redirect to delivery form
            if ($order->delivery_type === 'delivery') {
                return redirect()->route('deliveries.create', ['invoice_id' => $invoice->id])
                    ->with('success', 'Order approved and invoice created. Please fill in the delivery details.');
            }
            
            return redirect()->route('orders.show', $order)->with('success', 'Order approved and invoice created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject an order (for staff only).
     */
    public function reject(Order $order, Request $request)
    {
        // Only Admin|Staff can reject orders
        if (!auth()->user() || !(auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Staff'))) {
            abort(403);
        }

        if ($order->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending orders can be rejected.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $order->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        // Reload order
        $order->refresh();

        // Broadcast order update for real-time updates
        broadcast(new OrderUpdated($order))->toOthers();

        return redirect()->route('orders.show', $order)->with('success', 'Order rejected successfully.');
    }

    /**
     * Cancel an order (for customers - their own orders only).
     */
    public function cancel(Order $order, Request $request)
    {
        // Customers can only cancel their own pending orders
        if (auth()->user()?->hasRole('Customer')) {
            $customerId = Customer::where('email', auth()->user()->email)->value('id');
            if (!$customerId || $order->customer_id !== $customerId) {
                abort(403);
            }
            
            // Customers cannot cancel approved/accepted orders
            if ($order->status === 'approved') {
                return redirect()->back()->with('error', 'Cannot cancel approved orders. Please contact support.');
            }
            
            // Only pending orders can be cancelled by customers
            if ($order->status !== 'pending') {
                return redirect()->back()->with('error', 'Only pending orders can be cancelled.');
            }
        } else {
            // Staff can cancel any order (except approved ones with invoices)
            if ($order->status === 'approved' && $order->invoice_id) {
                return redirect()->back()->with('error', 'Cannot cancel approved orders with invoices. Please handle through invoice system.');
            }
        }

        $order->update([
            'status' => 'cancelled',
        ]);

        // Reload order
        $order->refresh();

        // Broadcast order update for real-time updates
        broadcast(new OrderUpdated($order))->toOthers();

        return redirect()->route('orders.show', $order)->with('success', 'Order cancelled successfully.');
    }

    /**
     * Add a comment to an order.
     */
    public function addComment(Order $order, Request $request)
    {
        // Customers can only comment on their own orders
        if (auth()->user()?->hasRole('Customer')) {
            $customerId = Customer::where('email', auth()->user()->email)->value('id');
            if (!$customerId || $order->customer_id !== $customerId) {
                abort(403);
            }
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:2000',
        ]);

        $isStaff = auth()->user() && (auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Staff'));

        $comment = OrderComment::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'comment' => $validated['comment'],
            'is_staff_comment' => $isStaff,
        ]);

        // Reload the comment with user relationship for broadcast
        $comment->load('user');

        // Broadcast the event for real-time updates
        // Use broadcastSync for immediate broadcast (not queued)
        broadcast(new OrderCommentAdded($comment))->toOthers();

        // Log the broadcast for debugging
        \Log::info('Broadcasting comment', [
            'comment_id' => $comment->id,
            'order_id' => $comment->order_id,
            'channel' => 'order.' . $comment->order_id,
            'event' => 'comment.added'
        ]);

        return redirect()->back()->with('success', 'Comment added successfully.');
    }
}
