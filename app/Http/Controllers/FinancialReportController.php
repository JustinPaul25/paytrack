<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FinancialReportController extends Controller
{
    private const DEFAULT_MONTH_SPAN = 6;
    private const MAX_MONTH_SPAN = 24;
    private const SUPPORTED_INVOICE_STATUSES = ['paid', 'completed'];

    public function index(Request $request): InertiaResponse
    {
        $filters = $this->resolveFilters($request);

        $reportRows = $this->buildReportRows($filters['start'], $filters['end']);

        $totals = $this->summarizeTotals($reportRows);

        return Inertia::render('finance/Reports', [
            'filters' => [
                'start_month' => $filters['start']->format('Y-m'),
                'end_month' => $filters['end']->format('Y-m'),
            ],
            'rows' => $reportRows,
            'totals' => $totals,
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->resolveFilters($request);

        $reportRows = $this->buildReportRows($filters['start'], $filters['end']);
        $totals = $this->summarizeTotals($reportRows);

        $filename = sprintf(
            'financial-report-%s-to-%s.csv',
            $filters['start']->format('Y-m'),
            $filters['end']->format('Y-m')
        );

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = static function () use ($reportRows, $totals) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Month', 'Income (PHP)', 'Expenses (PHP)', 'Net (PHP)']);

            foreach ($reportRows as $row) {
                fputcsv($handle, [
                    $row['label'],
                    number_format($row['income'], 2, '.', ''),
                    number_format($row['expenses'], 2, '.', ''),
                    number_format($row['net'], 2, '.', ''),
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'Totals',
                number_format($totals['income'], 2, '.', ''),
                number_format($totals['expenses'], 2, '.', ''),
                number_format($totals['net'], 2, '.', ''),
            ]);

            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    private function resolveFilters(Request $request): array
    {
        $now = Carbon::now()->startOfMonth();

        $startMonth = $request->input('start_month');
        $endMonth = $request->input('end_month');

        $end = $endMonth ? Carbon::createFromFormat('Y-m', $endMonth)->startOfMonth() : $now->copy();
        $start = $startMonth ? Carbon::createFromFormat('Y-m', $startMonth)->startOfMonth() : $end->copy()->subMonths(self::DEFAULT_MONTH_SPAN - 1);

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        $monthsBetween = $start->diffInMonths($end) + 1;
        if ($monthsBetween > self::MAX_MONTH_SPAN) {
            $start = $end->copy()->subMonths(self::MAX_MONTH_SPAN - 1);
        }

        return [
            'start' => $start,
            'end' => $end->copy()->endOfMonth(),
        ];
    }

    /**
     * @return list<array{month: string,label: string,income: float,expenses: float,net: float}>
     */
    private function buildReportRows(Carbon $start, Carbon $end): array
    {
        $rows = [];
        $cursor = $start->copy()->startOfMonth();
        $endOfPeriod = $end->copy()->startOfMonth();

        while ($cursor->lte($endOfPeriod)) {
            $monthStart = $cursor->copy();
            $monthEnd = $cursor->copy()->endOfMonth();

            $income = $this->sumInvoices($monthStart, $monthEnd);
            $expenses = $this->sumExpenses($monthStart, $monthEnd);
            $net = $income - $expenses;

            $rows[] = [
                'month' => $monthStart->format('Y-m-01'),
                'label' => $monthStart->format('F Y'),
                'income' => $this->roundCurrency($income),
                'expenses' => $this->roundCurrency($expenses),
                'net' => $this->roundCurrency($net),
            ];

            $cursor->addMonth();
        }

        return $rows;
    }

    /**
     * @param list<array{income: float, expenses: float, net: float}> $rows
     * @return array{income: float, expenses: float, net: float}
     */
    private function summarizeTotals(array $rows): array
    {
        $collection = collect($rows);

        return [
            'income' => $this->roundCurrency($collection->sum('income')),
            'expenses' => $this->roundCurrency($collection->sum('expenses')),
            'net' => $this->roundCurrency($collection->sum('net')),
        ];
    }

    private function sumInvoices(Carbon $start, Carbon $end): float
    {
        $sum = Invoice::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', self::SUPPORTED_INVOICE_STATUSES)
            ->sum('total_amount');

        return $sum / 100;
    }

    private function sumExpenses(Carbon $start, Carbon $end): float
    {
        return (float) Expense::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');
    }

    private function roundCurrency(float $amount): float
    {
        return round($amount, 2);
    }
}

