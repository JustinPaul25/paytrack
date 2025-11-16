<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SalesPredictionController extends Controller
{
    /**
     * Get advanced sales predictions using external AI service
     */
    public function getPredictions(Request $request)
    {
        $period = $request->get('period', 'month');
        $daysAhead = $request->get('days_ahead', 30);
        
        // Get historical sales data
        $salesData = $this->getHistoricalSalesData($period);
        
        // Generate predictions using statistical analysis
        // Frontend uses TensorFlow.js for neural network predictions
        $predictions = $this->generateAdvancedPredictions($salesData, $daysAhead);
        
        return response()->json($predictions);
    }

    /**
     * Get historical sales data for prediction
     */
    private function getHistoricalSalesData($period)
    {
        $endDate = Carbon::now()->endOfDay();
        
        switch ($period) {
            case 'week':
                $startDate = Carbon::now()->subWeek()->startOfDay();
                break;
            case 'quarter':
                $startDate = Carbon::now()->subQuarter()->startOfDay();
                break;
            case 'year':
                $startDate = Carbon::now()->subYear()->startOfDay();
                break;
            default: // month
                $startDate = Carbon::now()->subMonth()->startOfDay();
                break;
        }

        return Invoice::select(
                'created_at',
                'total_amount',
                'status'
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->orderBy('created_at')
            ->get()
            ->groupBy(function ($invoice) {
                return $invoice->created_at->format('Y-m-d');
            })
            ->map(function ($dayInvoices) {
                return [
                    'date' => $dayInvoices->first()->created_at->format('Y-m-d'),
                    'sales' => $dayInvoices->sum('total_amount'),
                    'invoices' => $dayInvoices->count(),
                ];
            })
            ->values();
    }

    /**
     * Generate advanced predictions with seasonality and trends
     */
    private function generateAdvancedPredictions($salesData, $daysAhead)
    {
        if ($salesData->isEmpty()) {
            return $this->getDummyPredictions($daysAhead);
        }

        $predictions = [];
        $lastDate = Carbon::parse($salesData->last()['date']);
        
        // Calculate base metrics
        $avgSales = $salesData->avg('sales');
        $salesStdDev = $this->calculateStandardDeviation($salesData->pluck('sales')->toArray());
        
        // Detect seasonality patterns
        $seasonality = $this->detectSeasonality($salesData);
        
        // Detect trend
        $trend = $this->detectTrend($salesData);

        for ($i = 1; $i <= $daysAhead; $i++) {
            $predictionDate = $lastDate->copy()->addDays($i);
            $dayOfWeek = $predictionDate->dayOfWeek;
            $dayOfMonth = $predictionDate->day;
            $month = $predictionDate->month;
            
            // Base prediction
            $basePrediction = $avgSales;
            
            // Apply seasonality
            $seasonalFactor = $this->getSeasonalFactor($dayOfWeek, $dayOfMonth, $month, $seasonality);
            $basePrediction *= $seasonalFactor;
            
            // Apply trend
            $trendFactor = 1 + ($trend * $i / 30); // Trend over 30 days
            $basePrediction *= $trendFactor;
            
            // Add some randomness
            $randomFactor = 1 + (rand(-15, 15) / 100); // ±15% randomness
            $basePrediction *= $randomFactor;
            
            // Ensure positive values
            $basePrediction = max(0, $basePrediction);
            
            $predictions[] = [
                'date' => $predictionDate->format('Y-m-d'),
                'predicted_sales' => round($basePrediction, 2),
                'confidence' => $this->calculateConfidence($salesData->count(), $salesStdDev, $avgSales),
                'factors' => [
                    'seasonal_factor' => round($seasonalFactor, 3),
                    'trend_factor' => round($trendFactor, 3),
                    'day_of_week' => $dayOfWeek,
                    'is_weekend' => in_array($dayOfWeek, [0, 6]),
                ]
            ];
        }

        return [
            'predictions' => $predictions,
            'summary' => [
                'total_predicted_sales' => array_sum(array_column($predictions, 'predicted_sales')),
                'average_daily_prediction' => array_sum(array_column($predictions, 'predicted_sales')) / count($predictions),
                'confidence_range' => [
                    'min' => min(array_column($predictions, 'confidence')),
                    'max' => max(array_column($predictions, 'confidence')),
                    'average' => array_sum(array_column($predictions, 'confidence')) / count($predictions),
                ],
                'trend_direction' => $trend > 0 ? 'increasing' : ($trend < 0 ? 'decreasing' : 'stable'),
                'trend_strength' => abs($trend),
            ],
            'model_info' => [
                'data_points_used' => $salesData->count(),
                'prediction_horizon' => $daysAhead,
                'last_training_date' => now()->format('Y-m-d H:i:s'),
            ]
        ];
    }

    /**
     * Calculate standard deviation
     */
    private function calculateStandardDeviation($values)
    {
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(function ($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values)) / count($values);
        
        return sqrt($variance);
    }

    /**
     * Detect seasonality patterns
     */
    private function detectSeasonality($salesData)
    {
        $seasonality = [
            'weekly' => [],
            'monthly' => [],
        ];

        // Weekly seasonality (day of week patterns)
        $weeklyData = [];
        for ($i = 0; $i < 7; $i++) {
            $weeklyData[$i] = [];
        }

        foreach ($salesData as $data) {
            $dayOfWeek = Carbon::parse($data['date'])->dayOfWeek;
            $weeklyData[$dayOfWeek][] = $data['sales'];
        }

        for ($i = 0; $i < 7; $i++) {
            $seasonality['weekly'][$i] = !empty($weeklyData[$i]) ? 
                array_sum($weeklyData[$i]) / count($weeklyData[$i]) : 1;
        }

        // Normalize weekly factors
        $avgWeekly = array_sum($seasonality['weekly']) / 7;
        for ($i = 0; $i < 7; $i++) {
            $seasonality['weekly'][$i] /= $avgWeekly;
        }

        return $seasonality;
    }

    /**
     * Detect trend in sales data
     */
    private function detectTrend($salesData)
    {
        if ($salesData->count() < 2) {
            return 0;
        }

        $x = range(0, $salesData->count() - 1);
        $y = $salesData->pluck('sales')->toArray();

        // Simple linear regression
        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $avgSales = $sumY / $n;

        // Return trend as percentage change per day
        return $avgSales > 0 ? $slope / $avgSales : 0;
    }

    /**
     * Get seasonal factor for a specific date
     */
    private function getSeasonalFactor($dayOfWeek, $dayOfMonth, $month, $seasonality)
    {
        $weeklyFactor = $seasonality['weekly'][$dayOfWeek] ?? 1;
        
        // Monthly seasonality (simplified)
        $monthlyFactor = 1;
        if ($month == 12) { // December (holiday season)
            $monthlyFactor = 1.2;
        } elseif ($month == 1) { // January (post-holiday)
            $monthlyFactor = 0.8;
        }
        
        return $weeklyFactor * $monthlyFactor;
    }

    /**
     * Calculate prediction confidence
     */
    private function calculateConfidence($dataPoints, $stdDev, $avgSales)
    {
        if ($avgSales == 0) return 0.5;
        
        // More data points = higher confidence
        $dataConfidence = min(1, $dataPoints / 100);
        
        // Lower variance = higher confidence
        $varianceConfidence = max(0.1, 1 - ($stdDev / $avgSales));
        
        return ($dataConfidence + $varianceConfidence) / 2;
    }

    /**
     * Generate dummy predictions for testing
     */
    private function getDummyPredictions($daysAhead)
    {
        $predictions = [];
        $baseSales = 3000;
        
        for ($i = 1; $i <= $daysAhead; $i++) {
            $date = Carbon::now()->addDays($i);
            $dayOfWeek = $date->dayOfWeek;
            
            // Weekend boost
            $sales = $baseSales;
            if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                $sales *= 1.3;
            }
            
            // Add some randomness
            $sales *= (1 + (rand(-10, 15) / 100));
            
            $predictions[] = [
                'date' => $date->format('Y-m-d'),
                'predicted_sales' => round($sales, 2),
                'confidence' => 0.7 + (rand(-10, 10) / 100),
                'factors' => [
                    'seasonal_factor' => $dayOfWeek == 0 || $dayOfWeek == 6 ? 1.3 : 1.0,
                    'trend_factor' => 1.0,
                    'day_of_week' => $dayOfWeek,
                    'is_weekend' => in_array($dayOfWeek, [0, 6]),
                ]
            ];
        }

        return [
            'predictions' => $predictions,
            'summary' => [
                'total_predicted_sales' => array_sum(array_column($predictions, 'predicted_sales')),
                'average_daily_prediction' => array_sum(array_column($predictions, 'predicted_sales')) / count($predictions),
                'confidence_range' => [
                    'min' => 0.6,
                    'max' => 0.8,
                    'average' => 0.7,
                ],
                'trend_direction' => 'stable',
                'trend_strength' => 0,
            ],
            'model_info' => [
                'data_points_used' => 0,
                'prediction_horizon' => $daysAhead,
                'last_training_date' => now()->format('Y-m-d H:i:s'),
                'note' => 'Using dummy data - no historical data available',
            ]
        ];
    }

} 