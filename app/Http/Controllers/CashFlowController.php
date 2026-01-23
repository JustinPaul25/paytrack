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
        $historicalNet = array_map(static fn (array $entry) => $entry['net'], $historical);

        // Use time series forecasting with confidence intervals
        $incomeForecast = $this->forecastTimeSeries($historicalIncome, $forecastMonths);
        $expenseForecast = $this->forecastTimeSeries($historicalExpenses, $forecastMonths);
        $netForecast = $this->forecastTimeSeries($historicalNet, $forecastMonths);

        $lastHistorical = !empty($historical) ? end($historical) : null;

        $projections = $this->buildProjectionSeries(
            $historyEnd->copy()->startOfMonth(),
            $lastHistorical['running_balance'] ?? 0.0,
            $incomeForecast,
            $expenseForecast,
            $netForecast
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
     * Time series forecasting with confidence intervals
     * Uses exponential smoothing with trend and seasonality detection
     * 
     * @param list<float> $historicalValues
     * @param int $futurePeriods
     * @return list<array{forecast: float, lower: float, upper: float}>
     */
    private function forecastTimeSeries(array $historicalValues, int $futurePeriods): array
    {
        $periods = max(0, $futurePeriods);
        $count = count($historicalValues);

        if ($periods === 0 || $count === 0) {
            return array_fill(0, $periods, ['forecast' => 0.0, 'lower' => 0.0, 'upper' => 0.0]);
        }

        // Fallback for single data point
        if ($count === 1) {
            $value = $historicalValues[0];
            return array_fill(0, $periods, [
                'forecast' => $this->roundCurrency($value),
                'lower' => $this->roundCurrency(max(0, $value * 0.7)),
                'upper' => $this->roundCurrency($value * 1.3),
            ]);
        }

        // Calculate basic statistics
        $mean = array_sum($historicalValues) / $count;
        $variance = 0.0;
        foreach ($historicalValues as $value) {
            $variance += pow($value - $mean, 2);
        }
        $variance /= $count;
        $stdDev = sqrt($variance);
        $coefficientOfVariation = $mean > 0 ? $stdDev / $mean : 0.0;

        // Detect trend using linear regression
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

        // Detect seasonality (monthly patterns)
        $seasonalFactors = $this->detectSeasonality($historicalValues, min(12, $count));

        // Exponential smoothing parameters
        $alpha = 0.3; // Smoothing factor
        $beta = 0.1;  // Trend factor
        $gamma = 0.2; // Seasonality factor

        // Initialize smoothed values
        $level = $historicalValues[0];
        $trend = $slope;
        $lastSeasonal = $seasonalFactors[0] ?? 1.0;

        // Apply exponential smoothing to historical data
        for ($i = 1; $i < $count; $i++) {
            $prevLevel = $level;
            $seasonalIndex = $i % count($seasonalFactors);
            $currentSeasonal = $seasonalFactors[$seasonalIndex] ?? 1.0;
            
            $level = $alpha * ($historicalValues[$i] / $currentSeasonal) + (1 - $alpha) * ($prevLevel + $trend);
            $trend = $beta * ($level - $prevLevel) + (1 - $beta) * $trend;
            $lastSeasonal = $gamma * ($historicalValues[$i] / $prevLevel) + (1 - $gamma) * $currentSeasonal;
        }

        // Generate forecasts with confidence intervals
        $forecasts = [];
        for ($futureIndex = 0; $futureIndex < $periods; $futureIndex++) {
            $seasonalIndex = ($count + $futureIndex) % count($seasonalFactors);
            $seasonalFactor = $seasonalFactors[$seasonalIndex] ?? 1.0;
            
            // Forecast value
            $forecastValue = ($level + ($trend * ($futureIndex + 1))) * $seasonalFactor;
            
            // Calculate confidence intervals
            // Uncertainty increases with forecast horizon (wider intervals for further forecasts)
            $horizonMultiplier = 1 + ($futureIndex * 0.15); // 15% increase per period
            $uncertainty = $stdDev * $horizonMultiplier;
            
            // Use t-distribution approximation (95% confidence interval, ~1.96 standard deviations)
            $confidenceMultiplier = 1.96; // 95% confidence
            $lowerBound = $forecastValue - ($confidenceMultiplier * $uncertainty);
            $upperBound = $forecastValue + ($confidenceMultiplier * $uncertainty);
            
            // Ensure reasonable bounds
            // For positive values, ensure lower bound is at least 10% of forecast or 0
            if ($forecastValue > 0) {
                $lowerBound = max(0, $lowerBound, $forecastValue * 0.1);
            } else {
                // For negative values (net cash flow), allow negative bounds
                $lowerBound = min($lowerBound, $forecastValue * 1.5);
            }
            
            $forecasts[] = [
                'forecast' => $this->roundCurrency($forecastValue),
                'lower' => $this->roundCurrency($lowerBound),
                'upper' => $this->roundCurrency($upperBound),
            ];
        }

        return $forecasts;
    }

    /**
     * Detect seasonal patterns in the data
     * 
     * @param list<float> $values
     * @param int $seasonLength
     * @return list<float>
     */
    private function detectSeasonality(array $values, int $seasonLength): array
    {
        $count = count($values);
        if ($count < $seasonLength * 2) {
            // Not enough data for seasonality, return neutral factors
            return array_fill(0, $seasonLength, 1.0);
        }

        // Calculate average for each seasonal position
        $seasonalSums = array_fill(0, $seasonLength, 0.0);
        $seasonalCounts = array_fill(0, $seasonLength, 0);

        for ($i = 0; $i < $count; $i++) {
            $position = $i % $seasonLength;
            $seasonalSums[$position] += $values[$i];
            $seasonalCounts[$position]++;
        }

        // Calculate seasonal averages
        $seasonalAverages = [];
        for ($i = 0; $i < $seasonLength; $i++) {
            $seasonalAverages[$i] = $seasonalCounts[$i] > 0 
                ? $seasonalSums[$i] / $seasonalCounts[$i] 
                : 1.0;
        }

        // Calculate overall average
        $overallAverage = array_sum($seasonalAverages) / $seasonLength;
        if ($overallAverage == 0) {
            return array_fill(0, $seasonLength, 1.0);
        }

        // Normalize to get seasonal factors
        $seasonalFactors = [];
        for ($i = 0; $i < $seasonLength; $i++) {
            $seasonalFactors[$i] = $overallAverage > 0 
                ? $seasonalAverages[$i] / $overallAverage 
                : 1.0;
        }

        return $seasonalFactors;
    }

    /**
     * @param list<array{forecast: float, lower: float, upper: float}> $incomeForecast
     * @param list<array{forecast: float, lower: float, upper: float}> $expenseForecast
     * @param list<array{forecast: float, lower: float, upper: float}> $netForecast
     * @return list<array{
     *     month: string,
     *     label: string,
     *     income: float,
     *     expenses: float,
     *     net: float,
     *     running_balance: float,
     *     income_lower: float,
     *     income_upper: float,
     *     expenses_lower: float,
     *     expenses_upper: float,
     *     net_lower: float,
     *     net_upper: float,
     *     running_balance_lower: float,
     *     running_balance_upper: float
     * }>
     */
    private function buildProjectionSeries(
        Carbon $lastHistoricalMonth, 
        float $startingBalance, 
        array $incomeForecast, 
        array $expenseForecast,
        array $netForecast
    ): array {
        $series = [];
        $balance = $startingBalance;
        $balanceLower = $startingBalance;
        $balanceUpper = $startingBalance;
        $monthsToProject = max(count($incomeForecast), count($expenseForecast), count($netForecast));

        for ($index = 0; $index < $monthsToProject; $index += 1) {
            $month = $lastHistoricalMonth->copy()->addMonths($index + 1);
            
            $incomeData = $incomeForecast[$index] ?? ['forecast' => 0.0, 'lower' => 0.0, 'upper' => 0.0];
            $expenseData = $expenseForecast[$index] ?? ['forecast' => 0.0, 'lower' => 0.0, 'upper' => 0.0];
            $netData = $netForecast[$index] ?? ['forecast' => 0.0, 'lower' => 0.0, 'upper' => 0.0];
            
            // Ensure income and expenses are non-negative
            $income = max(0, $incomeData['forecast']);
            $expenses = max(0, $expenseData['forecast']);
            // Use the direct net forecast (which can be negative)
            $net = $netData['forecast'];
            
            // Calculate running balance for forecast
            $balance += $net;
            
            // Calculate running balance bounds (worst case: lower income, upper expenses)
            $balanceLower += $netData['lower'];
            $balanceUpper += $netData['upper'];

            $series[] = [
                'month' => $month->format('Y-m-01'),
                'label' => $month->format('F Y'),
                'income' => $this->roundCurrency($income),
                'expenses' => $this->roundCurrency($expenses),
                'net' => $this->roundCurrency($net),
                'running_balance' => $this->roundCurrency($balance),
                'income_lower' => $this->roundCurrency($incomeData['lower']),
                'income_upper' => $this->roundCurrency($incomeData['upper']),
                'expenses_lower' => $this->roundCurrency($expenseData['lower']),
                'expenses_upper' => $this->roundCurrency($expenseData['upper']),
                'net_lower' => $this->roundCurrency($netData['lower']),
                'net_upper' => $this->roundCurrency($netData['upper']),
                'running_balance_lower' => $this->roundCurrency($balanceLower),
                'running_balance_upper' => $this->roundCurrency($balanceUpper),
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

