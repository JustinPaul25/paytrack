<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CashFlowController extends Controller
{
    private const MIN_HISTORY_MONTHS = 3;
    private const MAX_HISTORY_MONTHS = 24;
    private const MIN_FORECAST_MONTHS = 1;
    private const MAX_FORECAST_MONTHS = 12;
    private const SUPPORTED_INVOICE_STATUSES = ['paid', 'completed'];

    public function index(Request $request): Response
    {
        $historyMonths = (int) $request->integer('months', 6);
        $historyMonths = $this->clamp($historyMonths, self::MIN_HISTORY_MONTHS, self::MAX_HISTORY_MONTHS);

        $forecastMonths = (int) $request->integer('forecast_months', 6);
        $forecastMonths = $this->clamp($forecastMonths, self::MIN_FORECAST_MONTHS, self::MAX_FORECAST_MONTHS);

        $now = Carbon::now();
        $historyStart = $now->copy()->startOfMonth()->subMonths($historyMonths - 1);
        $historyEnd = $now->copy()->endOfMonth();

        $historical = $this->buildHistoricalSeries($historyStart, $historyEnd);

        $historicalIncome = array_map(static fn (array $entry) => $entry['income'], $historical);
        $historicalExpenses = array_map(static fn (array $entry) => $entry['expenses'], $historical);

        $incomeForecast = $this->projectSeries($historicalIncome, $forecastMonths);
        $expenseForecast = $this->projectSeries($historicalExpenses, $forecastMonths);

        $lastHistorical = !empty($historical) ? end($historical) : null;

        $projections = $this->buildProjectionSeries(
            $historyEnd->copy()->startOfMonth(),
            $lastHistorical['running_balance'] ?? 0.0,
            $incomeForecast,
            $expenseForecast
        );

        $summaries = $this->buildSummaries($historical, $projections);

        return Inertia::render('finance/CashFlow', [
            'filters' => [
                'months' => $historyMonths,
                'forecast_months' => $forecastMonths,
            ],
            'historical' => $historical,
            'projections' => $projections,
            'summaries' => $summaries,
        ]);
    }

    /**
     * @return list<array{
     *     month: string,
     *     label: string,
     *     income: float,
     *     expenses: float,
     *     net: float,
     *     running_balance: float
     * }>
     */
    private function buildHistoricalSeries(Carbon $start, Carbon $end): array
    {
        $series = [];
        $cursor = $start->copy();
        $runningBalance = 0.0;

        while ($cursor->lte($end)) {
            $monthStart = $cursor->copy();
            $monthEnd = $cursor->copy()->endOfMonth();

            $income = $this->sumPaidInvoices($monthStart, $monthEnd);
            $expenses = $this->sumExpenses($monthStart, $monthEnd);
            $net = $income - $expenses;
            $runningBalance += $net;

            $series[] = [
                'month' => $monthStart->format('Y-m-01'),
                'label' => $monthStart->format('F Y'),
                'income' => $this->roundCurrency($income),
                'expenses' => $this->roundCurrency($expenses),
                'net' => $this->roundCurrency($net),
                'running_balance' => $this->roundCurrency($runningBalance),
            ];

            $cursor->addMonth();
        }

        return $series;
    }

    /**
     * @param list<float> $historicalValues
     * @return list<float>
     */
    private function projectSeries(array $historicalValues, int $futurePeriods): array
    {
        $periods = max(0, $futurePeriods);
        $count = count($historicalValues);

        if ($periods === 0 || $count === 0) {
            return array_fill(0, $periods, 0.0);
        }

        // Fallback for flat series
        if ($count === 1) {
            return array_fill(0, $periods, $this->roundCurrency($historicalValues[0]));
        }

        $xValues = range(0, $count - 1);
        $sumX = array_sum($xValues);
        $sumY = array_sum($historicalValues);
        $sumXY = 0.0;
        $sumX2 = 0.0;

        foreach ($xValues as $index => $x) {
            $sumXY += $x * $historicalValues[$index];
            $sumX2 += $x * $x;
        }

        $denominator = ($count * $sumX2) - ($sumX ** 2);

        $slope = $denominator !== 0.0
            ? (($count * $sumXY) - ($sumX * $sumY)) / $denominator
            : 0.0;

        $intercept = ($sumY - ($slope * $sumX)) / $count;

        $projections = [];
        for ($futureIndex = 0; $futureIndex < $periods; $futureIndex += 1) {
            $x = $count + $futureIndex;
            $value = $intercept + ($slope * $x);
            $projections[] = $this->roundCurrency(max(0.0, $value));
        }

        return $projections;
    }

    /**
     * @param list<float> $incomeForecast
     * @param list<float> $expenseForecast
     * @return list<array{
     *     month: string,
     *     label: string,
     *     income: float,
     *     expenses: float,
     *     net: float,
     *     running_balance: float
     * }>
     */
    private function buildProjectionSeries(Carbon $lastHistoricalMonth, float $startingBalance, array $incomeForecast, array $expenseForecast): array
    {
        $series = [];
        $balance = $startingBalance;
        $monthsToProject = max(count($incomeForecast), count($expenseForecast));

        for ($index = 0; $index < $monthsToProject; $index += 1) {
            $month = $lastHistoricalMonth->copy()->addMonths($index + 1);
            $income = $incomeForecast[$index] ?? 0.0;
            $expenses = $expenseForecast[$index] ?? 0.0;
            $net = $income - $expenses;
            $balance += $net;

            $series[] = [
                'month' => $month->format('Y-m-01'),
                'label' => $month->format('F Y'),
                'income' => $this->roundCurrency($income),
                'expenses' => $this->roundCurrency($expenses),
                'net' => $this->roundCurrency($net),
                'running_balance' => $this->roundCurrency($balance),
            ];
        }

        return $series;
    }

    /**
     * @param list<array<string, mixed>> $historical
     * @param list<array<string, mixed>> $projections
     * @return array<string, mixed>
     */
    private function buildSummaries(array $historical, array $projections): array
    {
        $historicalCollection = collect($historical);
        $projectionCollection = collect($projections);

        $averageIncome = $this->roundCurrency($this->average($historicalCollection->pluck('income')));
        $averageExpenses = $this->roundCurrency($this->average($historicalCollection->pluck('expenses')));
        $averageNet = $this->roundCurrency($this->average($historicalCollection->pluck('net')));
        $latestHistorical = $historicalCollection->last();
        $nextProjection = $projectionCollection->first();

        $combinedBalances = $historicalCollection
            ->pluck('running_balance')
            ->concat($projectionCollection->pluck('running_balance'));

        $runwayMonths = $this->calculateRunwayMonths($combinedBalances);

        return [
            'average_income' => $averageIncome,
            'average_expenses' => $averageExpenses,
            'average_net' => $averageNet,
            'current_cash_position' => $latestHistorical['running_balance'] ?? 0.0,
            'current_month_net' => $latestHistorical['net'] ?? 0.0,
            'next_month_projection' => $nextProjection['net'] ?? 0.0,
            'projected_three_month_net' => $this->roundCurrency(
                $projectionCollection->take(3)->sum('net')
            ),
            'cash_runway_months' => $runwayMonths,
        ];
    }

    private function calculateRunwayMonths(Collection $balances): int
    {
        $values = $balances->values();

        foreach ($values as $index => $balance) {
            if ($balance < 0) {
                return $index;
            }
        }

        return $values->isEmpty() ? 0 : $values->count();
    }

    private function average(Collection $values): float
    {
        $filtered = $values->filter(static fn ($value) => $value !== null);
        $count = $filtered->count();

        if ($count === 0) {
            return 0.0;
        }

        return $filtered->sum() / $count;
    }

    private function sumPaidInvoices(Carbon $start, Carbon $end): float
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

    private function clamp(int $value, int $min, int $max): int
    {
        return max($min, min($value, $max));
    }
}

