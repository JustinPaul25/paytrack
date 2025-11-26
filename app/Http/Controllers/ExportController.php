<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ExportController extends Controller
{
    public function index(): InertiaResponse
    {
        return Inertia::render('admin/Export', []);
    }

    public function exportCategories()
    {
        $categories = Category::orderBy('name')->get();

        $filename = 'categories-export-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $callback = function() use ($categories) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, ['ID', 'Name', 'Description', 'Created At', 'Updated At']);
            
            // Data
            foreach ($categories as $category) {
                fputcsv($file, [
                    $category->id,
                    $category->name,
                    $category->description ?? '',
                    $category->created_at->format('Y-m-d H:i:s'),
                    $category->updated_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function exportProducts()
    {
        $products = Product::with('category')->orderBy('name')->get();

        $filename = 'products-export-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $callback = function() use ($products) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'ID',
                'Name',
                'Description',
                'Category',
                'SKU',
                'Purchase Price (₱)',
                'Selling Price (₱)',
                'Stock',
                'Unit',
                'Created At',
                'Updated At'
            ]);
            
            // Data
            foreach ($products as $product) {
                fputcsv($file, [
                    $product->id,
                    $product->name,
                    $product->description ?? '',
                    $product->category ? $product->category->name : '',
                    $product->SKU ?? '',
                    number_format($product->purchase_price, 2, '.', ''),
                    number_format($product->selling_price, 2, '.', ''),
                    $product->stock ?? 0,
                    $product->unit ?? '',
                    $product->created_at->format('Y-m-d H:i:s'),
                    $product->updated_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function exportInvoices()
    {
        $invoices = Invoice::with(['customer', 'user'])->orderBy('created_at', 'desc')->get();

        $filename = 'invoices-export-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $callback = function() use ($invoices) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'ID',
                'Reference Number',
                'Customer',
                'Created By',
                'Subtotal Amount (₱)',
                'VAT Amount (₱)',
                'VAT Rate (%)',
                'Total Amount (₱)',
                'Status',
                'Payment Method',
                'Payment Status',
                'Payment Reference',
                'Invoice Type',
                'Credit Term Days',
                'Due Date',
                'Notes',
                'Created At',
                'Updated At'
            ]);
            
            // Data
            foreach ($invoices as $invoice) {
                fputcsv($file, [
                    $invoice->id,
                    $invoice->reference_number,
                    $invoice->customer ? $invoice->customer->name : '',
                    $invoice->user ? $invoice->user->name : '',
                    number_format($invoice->subtotal_amount, 2, '.', ''),
                    number_format($invoice->vat_amount, 2, '.', ''),
                    $invoice->vat_rate ?? 0,
                    number_format($invoice->total_amount, 2, '.', ''),
                    $invoice->status ?? '',
                    $invoice->payment_method ?? '',
                    $invoice->payment_status ?? '',
                    $invoice->payment_reference ?? '',
                    $invoice->invoice_type ?? '',
                    $invoice->credit_term_days ?? '',
                    $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '',
                    $invoice->notes ?? '',
                    $invoice->created_at->format('Y-m-d H:i:s'),
                    $invoice->updated_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function exportOrders()
    {
        $orders = Order::with(['customer', 'invoice', 'approvedBy'])->orderBy('created_at', 'desc')->get();

        $filename = 'orders-export-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'ID',
                'Reference Number',
                'Customer',
                'Invoice Reference',
                'Status',
                'Delivery Type',
                'Payment Method',
                'Subtotal Amount (₱)',
                'VAT Amount (₱)',
                'VAT Rate (%)',
                'Total Amount (₱)',
                'Approved By',
                'Approved At',
                'Rejection Reason',
                'Credit Term Days',
                'Due Date',
                'Notes',
                'Created At',
                'Updated At'
            ]);
            
            // Data
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->id,
                    $order->reference_number,
                    $order->customer ? $order->customer->name : '',
                    $order->invoice ? $order->invoice->reference_number : '',
                    $order->status ?? '',
                    $order->delivery_type ?? '',
                    $order->payment_method ?? '',
                    number_format($order->subtotal_amount, 2, '.', ''),
                    number_format($order->vat_amount, 2, '.', ''),
                    $order->vat_rate ?? 0,
                    number_format($order->total_amount, 2, '.', ''),
                    $order->approvedBy ? $order->approvedBy->name : '',
                    $order->approved_at ? $order->approved_at->format('Y-m-d H:i:s') : '',
                    $order->rejection_reason ?? '',
                    $order->credit_term_days ?? '',
                    $order->due_date ? $order->due_date->format('Y-m-d') : '',
                    $order->notes ?? '',
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->updated_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function exportSales()
    {
        $invoices = Invoice::with(['customer', 'user', 'invoiceItems.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'sales-export-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $callback = function() use ($invoices) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'ID',
                'Invoice Number',
                'Transaction Date',
                'Customer Name',
                'Company Name',
                'Product Name',
                'Quantity',
                'Unit Price (₱)',
                'Sale Non-VAT Total (₱)',
                'VAT Amount (₱)',
                'Withholding Tax (₱)',
                'Tax 5% (₱)',
                'Cash Amount (₱)',
                'Total Amount (₱)',
                'Status',
                'Running Balance (₱)',
                'Notes',
                'Created At',
                'Updated At'
            ]);
            
            // Calculate running balance
            $runningBalance = 0;
            
            // Data
            foreach ($invoices as $invoice) {
                // Calculate tax amounts (same logic as SalesTransactionController)
                $saleNonVatTotal = $invoice->subtotal_amount;
                $vatAmount = $invoice->vat_amount;
                $withholdingTax = $saleNonVatTotal * 0.01; // 1% of non-vat total
                $tax5Percent = $saleNonVatTotal * 0.05; // 5% of non-vat total
                $cashAmount = $invoice->total_amount - $withholdingTax - $tax5Percent;
                
                // Get product info
                $items = $invoice->invoiceItems;
                $productName = 'No items';
                $quantity = 0;
                $unitPrice = 0;
                
                if ($items->count() > 0) {
                    if ($items->count() === 1) {
                        $item = $items->first();
                        $productName = $item->product->name ?? 'Unknown Product';
                        $quantity = $item->quantity;
                    } else {
                        $totalQuantity = $items->sum('quantity');
                        $firstProduct = $items->first()->product->name ?? 'Unknown Product';
                        $productName = $firstProduct . ' +' . ($items->count() - 1) . ' more';
                        $quantity = $totalQuantity;
                    }
                    $unitPrice = $quantity > 0 ? $invoice->total_amount / $quantity : 0;
                }
                
                // Format status
                $status = match($invoice->status) {
                    'completed' => 'Completed',
                    'cancelled' => 'Canceled',
                    'draft' => 'Canceled',
                    'pending' => 'Canceled',
                    default => ucfirst($invoice->status ?? '')
                };
                
                // Add to running balance only if completed
                if ($status === 'Completed') {
                    $runningBalance += $invoice->total_amount;
                }
                
                fputcsv($file, [
                    $invoice->id,
                    $invoice->reference_number,
                    $invoice->created_at->format('Y-m-d H:i:s'),
                    $invoice->customer ? $invoice->customer->name : '',
                    $invoice->customer ? ($invoice->customer->company_name ?? '') : '',
                    $productName,
                    $quantity,
                    number_format($unitPrice, 2, '.', ''),
                    number_format($saleNonVatTotal, 2, '.', ''),
                    number_format($vatAmount, 2, '.', ''),
                    number_format($withholdingTax, 2, '.', ''),
                    number_format($tax5Percent, 2, '.', ''),
                    number_format($cashAmount, 2, '.', ''),
                    number_format($invoice->total_amount, 2, '.', ''),
                    $status,
                    number_format($runningBalance, 2, '.', ''),
                    $invoice->notes ?? '',
                    $invoice->created_at->format('Y-m-d H:i:s'),
                    $invoice->updated_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}

