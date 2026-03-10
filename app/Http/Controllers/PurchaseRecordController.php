<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRecordRequest;
use App\Models\PurchaseRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseRecordController extends Controller
{
    public function index(Request $request): Response
    {
        $search    = $request->input('search', '');
        $startDate = $request->input('start_date', '');
        $endDate   = $request->input('end_date', '');
        $payment   = $request->input('payment_type', '');

        $records = PurchaseRecord::query()
            ->with(['user:id,name', 'items'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('supplier_name', 'like', "%{$search}%")
                          ->orWhere('reference_number', 'like', "%{$search}%")
                          ->orWhere('receipt_number', 'like', "%{$search}%")
                          ->orWhere('buyer_name', 'like', "%{$search}%");
                });
            })
            ->when($startDate, fn ($q) => $q->whereDate('date', '>=', $startDate))
            ->when($endDate,   fn ($q) => $q->whereDate('date', '<=', $endDate))
            ->when($payment,   fn ($q) => $q->where('payment_type', $payment))
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'totalRecords' => PurchaseRecord::count(),
            'totalAmount'  => PurchaseRecord::sum('total_amount_due'),
            'thisMonth'    => PurchaseRecord::whereMonth('date', now()->month)
                                ->whereYear('date', now()->year)
                                ->sum('total_amount_due'),
        ];

        return Inertia::render('purchase-records/Index', [
            'records' => $records,
            'filters' => [
                'search'       => $search,
                'start_date'   => $startDate,
                'end_date'     => $endDate,
                'payment_type' => $payment,
            ],
            'stats'   => $stats,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('purchase-records/Create');
    }

    public function store(StorePurchaseRecordRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $items     = $validated['items'];
        unset($validated['items']);

        $totalDue       = collect($items)->sum('amount');
        $withholdingTax = $validated['withholding_tax'] ?? 0;

        $validated['total_due']        = $totalDue;
        $validated['total_amount_due'] = $totalDue - $withholdingTax;
        $validated['user_id']          = auth()->id();

        $record = PurchaseRecord::create($validated);
        $record->items()->createMany($items);

        return redirect()->route('purchase-records.show', $record)
            ->with('success', 'Purchase record saved successfully.');
    }

    public function show(PurchaseRecord $purchaseRecord): Response
    {
        $purchaseRecord->load(['user:id,name', 'items']);

        return Inertia::render('purchase-records/Show', [
            'record' => $purchaseRecord,
        ]);
    }

    public function edit(PurchaseRecord $purchaseRecord): Response
    {
        $purchaseRecord->load('items');

        return Inertia::render('purchase-records/Edit', [
            'record' => $purchaseRecord,
        ]);
    }

    public function update(StorePurchaseRecordRequest $request, PurchaseRecord $purchaseRecord): RedirectResponse
    {
        $validated = $request->validated();
        $items     = $validated['items'];
        unset($validated['items']);

        $totalDue       = collect($items)->sum('amount');
        $withholdingTax = $validated['withholding_tax'] ?? 0;

        $validated['total_due']        = $totalDue;
        $validated['total_amount_due'] = $totalDue - $withholdingTax;

        $purchaseRecord->update($validated);

        $purchaseRecord->items()->delete();
        $purchaseRecord->items()->createMany($items);

        return redirect()->route('purchase-records.show', $purchaseRecord)
            ->with('success', 'Purchase record updated successfully.');
    }

    public function destroy(PurchaseRecord $purchaseRecord): RedirectResponse
    {
        $purchaseRecord->items()->delete();
        $purchaseRecord->delete();

        return redirect()->route('purchase-records.index')
            ->with('success', 'Purchase record deleted successfully.');
    }
}
