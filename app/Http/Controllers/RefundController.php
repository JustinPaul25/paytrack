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
        // Redirect to the combined refund page
        return redirect()->route('refundRequests.index', ['refund_status' => $request->get('status', '')]);
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
        
        // Get delivery information if provided (for exchanges or refunds that need delivery)
        $deliveryDate = $request->get('delivery_date');
        $deliveryTime = $request->get('delivery_time');

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

        // Create delivery for exchange products or refunded items if delivery info is provided
        if ($deliveryDate && $deliveryTime) {
            // Load customer relationship if not already loaded
            if (!$invoice->relationLoaded('customer')) {
                $invoice->load('customer');
            }
            $customer = $invoice->customer;

            if ($customer && $invoice) {
                // Build delivery address from customer's address fields
                $addressParts = [];
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
                $deliveryAddress = !empty($addressParts) ? implode(', ', $addressParts) : ($customer->address ?? 'To be confirmed');

                // Normalize phone number to Philippine format
                $contactPhone = $customer->phone ?? '';
                if ($contactPhone) {
                    // Remove all non-digit characters
                    $digits = preg_replace('/[^0-9]/', '', $contactPhone);
                    // Normalize to +63XXXXXXXXXX format if we have valid digits
                    if (strlen($digits) >= 10) {
                        if (substr($digits, 0, 2) === '63') {
                            $contactPhone = '+' . substr($digits, 0, 12);
                        } elseif (substr($digits, 0, 1) === '0') {
                            $contactPhone = '+63' . substr($digits, 1, 10);
                        } else {
                            $contactPhone = '+63' . substr($digits, 0, 10);
                        }
                    }
                }

                // Determine product name and delivery type
                $productName = 'Product';
                $deliveryType = 'Refund';
                $deliveryNotes = "Delivery for refund {$refund->refund_number}";
                
                if ($refund->refund_type === 'exchange' && $refund->exchange_product_id) {
                    $exchangeProduct = \App\Models\Product::find($refund->exchange_product_id);
                    $productName = $exchangeProduct?->name ?? 'Exchange Product';
                    $deliveryType = 'Exchange';
                    $deliveryNotes = "Exchange delivery for refund {$refund->refund_number}. Product: {$productName} (Qty: {$refund->exchange_quantity})";
                } else {
                    $product = $refund->product;
                    $productName = $product?->name ?? 'Product';
                    $deliveryNotes = "Delivery for refund {$refund->refund_number}. Product: {$productName} (Qty: {$refund->quantity_refunded})";
                }

                // Get delivery fee from request (if provided), otherwise default to 0
                // For refund deliveries, we now allow delivery fees
                $deliveryFee = $request->get('delivery_fee', 0);
                
                // Create delivery record
                $delivery = \App\Models\Delivery::create([
                    'customer_id' => $customer->id,
                    'invoice_id' => $invoice->id,
                    'delivery_address' => $deliveryAddress,
                    'contact_person' => $customer->name ?? 'Customer',
                    'contact_phone' => $contactPhone ?: '+63-900-000-0000',
                    'delivery_date' => $deliveryDate,
                    'delivery_time' => $deliveryTime,
                    'status' => 'pending',
                    'notes' => $deliveryNotes,
                    'delivery_fee' => $deliveryFee * 100, // Convert to cents
                ]);
                
                // Update invoice total to include the refund delivery fee
                if ($deliveryFee > 0) {
                    $invoice->refresh();
                    $subtotal = $invoice->subtotal_amount;
                    $vatAmount = $invoice->vat_amount;
                    $withholdingTaxAmount = $invoice->withholding_tax_amount;
                    
                    // Sum all delivery fees (including the one just created)
                    $allDeliveryFees = $invoice->deliveries()
                        ->sum('delivery_fee') / 100;
                    
                    // Recalculate total: Subtotal + VAT - Withholding Tax + Delivery Fees
                    $newTotal = $subtotal + $vatAmount - $withholdingTaxAmount + $allDeliveryFees;
                    $invoice->total_amount = $newTotal;
                    $invoice->save();
                }

                // Create notification for customer about scheduled delivery
                $customerUser = \App\Models\User::where('email', $customer->email)->first();
                if ($customerUser) {
                    $formattedDate = \Carbon\Carbon::parse($deliveryDate)->format('F j, Y');
                    $message = "Your {$deliveryType} for refund {$refund->refund_number} has been completed. ";
                    $message .= "A delivery has been scheduled for {$formattedDate} at {$deliveryTime}.";
                    
                    $notification = \App\Models\Notification::create([
                        'user_id' => $customerUser->id,
                        'type' => 'refund.delivery_scheduled',
                        'notifiable_id' => $refund->id,
                        'notifiable_type' => \App\Models\Refund::class,
                        'title' => "{$deliveryType} Delivery Scheduled",
                        'message' => $message,
                        'action_url' => "/invoices/{$invoice->id}",
                        'read' => false,
                        'data' => [
                            'refund_id' => $refund->id,
                            'refund_number' => $refund->refund_number,
                            'invoice_id' => $invoice->id,
                            'delivery_id' => $delivery->id,
                            'delivery_date' => $deliveryDate,
                            'delivery_time' => $deliveryTime,
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

        // Only fetch approved damaged refunds (view-only)
        // Get damaged refunds from approved refund requests
        $refundsQuery = Refund::with(['invoice', 'product.media', 'user', 'refundRequest.media'])
            ->where('is_damaged', true)
            ->where('status', 'approved')
            ->orderByDesc('created_at');
        
        $refunds = $refundsQuery->paginate(10, ['*'], 'refunds_page')->withQueryString();

        // Transform refunds to include proof images from their refund requests and product images
        $refunds->through(function ($refund) {
            // Get product image URL (prefer product image, fallback to first proof image)
            $productImageUrl = null;
            if ($refund->product) {
                $productImageUrl = $refund->product->getFirstMediaUrl('images');
            }
            
            if ($refund->refundRequest) {
                $mediaCollection = $refund->refundRequest->getMedia('proof_images');
                $proofImages = $mediaCollection->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->getUrl(),
                        'name' => $media->name,
                    ];
                })->toArray();
                $refund->setAttribute('proof_images', $proofImages);
                
                // Use first proof image if no product image available
                if (!$productImageUrl && !empty($proofImages)) {
                    $productImageUrl = $proofImages[0]['url'];
                }
                
                // Also include other refund request details that might be useful
                $refund->setAttribute('refund_request', [
                    'id' => $refund->refundRequest->id,
                    'tracking_number' => $refund->refundRequest->tracking_number,
                    'customer_name' => $refund->refundRequest->customer_name,
                    'reason' => $refund->refundRequest->reason,
                    'damaged_items_terms' => $refund->refundRequest->damaged_items_terms,
                    'notes' => $refund->refundRequest->notes,
                ]);
            }
            
            // Set the display image (product image or first proof image)
            $refund->setAttribute('display_image_url', $productImageUrl);
            
            return $refund;
        });

        // Calculate statistics
        $stats = [
            'total_damaged_refunds' => Refund::where('is_damaged', true)->where('status', 'approved')->count(),
            'total_damaged_value' => Refund::where('is_damaged', true)->where('status', 'approved')->sum('refund_amount') / 100, // Convert from cents
        ];

        return inertia('refunds/DamagedItems', [
            'refunds' => $refunds,
            'stats' => $stats,
        ]);
    }
}


