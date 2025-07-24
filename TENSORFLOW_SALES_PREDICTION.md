# TensorFlow Sales Prediction for PayTrack

This document explains how to use TensorFlow for sales prediction in your PayTrack Vue application.

## Overview

The sales prediction feature uses machine learning to forecast future sales based on historical data. It includes:

1. **Frontend TensorFlow.js** - Real-time predictions in the browser
2. **Backend PHP Predictions** - Server-side predictions with seasonality detection
3. **Python TensorFlow Service** - Advanced LSTM models (optional)

## Features

### 🎯 **Smart Predictions**
- Daily, weekly, and monthly sales forecasts
- Confidence scoring for each prediction
- Seasonality and trend detection
- Holiday and weekend adjustments

### 📊 **Visual Analytics**
- Interactive prediction charts
- Confidence indicators
- Trend analysis
- Performance metrics

### 🤖 **AI-Powered**
- Neural network models
- Automatic model training
- Real-time predictions
- Model persistence

## Installation

### 1. Install TensorFlow.js Dependencies

```bash
npm install @tensorflow/tfjs @tensorflow/tfjs-vis
```

### 2. Optional: Python TensorFlow Service

For advanced predictions, you can run the Python service:

```bash
# Install Python dependencies
pip install tensorflow numpy pandas scikit-learn flask

# Run the service
python python_tensorflow_example.py
```

## Usage

### Frontend (TensorFlow.js)

The sales prediction widget is automatically integrated into your dashboard. It will:

1. **Load Historical Data** - Use your existing sales data
2. **Train Model** - Automatically train when you have 30+ days of data
3. **Show Predictions** - Display tomorrow's forecast and weekly trends
4. **Save Model** - Persist the trained model in localStorage

### Backend API

Access predictions via the API endpoint:

```bash
GET /sales/predictions?period=month&days_ahead=30
```

Response:
```json
{
  "predictions": [
    {
      "date": "2024-02-01",
      "predicted_sales": 3500.50,
      "confidence": 0.85,
      "factors": {
        "seasonal_factor": 1.2,
        "trend_factor": 1.05,
        "day_of_week": 4,
        "is_weekend": false
      }
    }
  ],
  "summary": {
    "total_predicted_sales": 105000.00,
    "average_daily_prediction": 3500.00,
    "confidence_range": {
      "min": 0.75,
      "max": 0.90,
      "average": 0.85
    },
    "trend_direction": "increasing",
    "trend_strength": 0.05
  }
}
```

### Python Service Integration

To use the advanced Python TensorFlow service:

1. **Start the service:**
   ```bash
   python python_tensorflow_example.py
   ```

2. **Configure Laravel to call the service:**
   Add to your `.env` file:
   ```
   TENSORFLOW_SERVICE_URL=http://localhost:5000
   ```

3. **Update the controller** to use the Python service:
   ```php
   // In SalesPredictionController.php
   private function callTensorFlowAPI($salesData)
   {
       $response = Http::post(config('services.tensorflow.url') . '/predict', [
           'sales_data' => $salesData,
           'prediction_days' => 30,
       ]);
       
       return $response->json();
   }
   ```

## How It Works

### 1. **Data Preparation**
- Extracts daily sales from your invoices
- Normalizes data for machine learning
- Adds features like day of week, holidays, etc.

### 2. **Model Training**
- Uses neural networks to learn patterns
- Considers seasonality, trends, and randomness
- Validates model performance

### 3. **Prediction Generation**
- Forecasts future sales based on learned patterns
- Calculates confidence scores
- Provides trend analysis

### 4. **Visualization**
- Shows predictions in beautiful charts
- Displays confidence indicators
- Compares with historical data

## Model Types

### 1. **TensorFlow.js (Frontend)**
- **Type**: Dense Neural Network
- **Features**: Day of week, month, holidays, previous sales
- **Advantages**: Real-time, no server needed
- **Best for**: Quick predictions, demonstrations

### 2. **PHP Backend**
- **Type**: Statistical Analysis + Seasonality Detection
- **Features**: Trend analysis, seasonal factors, confidence scoring
- **Advantages**: Server-side, consistent results
- **Best for**: Production use, reliable predictions

### 3. **Python TensorFlow (Advanced)**
- **Type**: LSTM Neural Network
- **Features**: Time series analysis, sequence learning
- **Advantages**: Most accurate, handles complex patterns
- **Best for**: Advanced analytics, high accuracy requirements

## Configuration

### Frontend Configuration

```typescript
// In salesPrediction.ts
const modelConfig = {
  epochs: 100,
  batchSize: 32,
  learningRate: 0.001,
  sequenceLength: 30
};
```

### Backend Configuration

```php
// In config/services.php
'tensorflow' => [
    'url' => env('TENSORFLOW_SERVICE_URL', 'http://localhost:5000'),
    'timeout' => 30,
],
```

## Performance Tips

### 1. **Data Quality**
- Ensure consistent daily data
- Remove outliers and anomalies
- Use at least 30 days of data

### 2. **Model Training**
- Train during off-peak hours
- Use validation data to prevent overfitting
- Monitor model performance metrics

### 3. **Caching**
- Cache predictions for 24 hours
- Store trained models locally
- Use Redis for production caching

## Troubleshooting

### Common Issues

1. **"Need at least 30 days of data"**
   - Solution: Use dummy data or wait for more data
   - Alternative: Reduce minimum data requirement

2. **"Model training failed"**
   - Check browser console for errors
   - Ensure TensorFlow.js is loaded
   - Try refreshing the page

3. **"Low confidence predictions"**
   - More data usually improves confidence
   - Check for data inconsistencies
   - Consider seasonal adjustments

### Debug Mode

Enable debug logging:

```typescript
// In salesPrediction.ts
const debug = true;
if (debug) {
    console.log('Training data:', trainingData);
    console.log('Model performance:', performance);
}
```

## Advanced Features

### 1. **Custom Models**
You can create custom prediction models:

```typescript
class CustomSalesPredictor extends SalesPredictor {
    async customPrediction(data) {
        // Your custom logic here
        return predictions;
    }
}
```

### 2. **External Data Integration**
Integrate external factors:

```typescript
// Add weather, events, or economic data
const externalFactors = {
    weather: await getWeatherData(),
    events: await getLocalEvents(),
    economic: await getEconomicIndicators()
};
```

### 3. **A/B Testing**
Compare different models:

```typescript
const modelA = new SalesPredictor();
const modelB = new AdvancedSalesPredictor();

const resultsA = await modelA.predict(data);
const resultsB = await modelB.predict(data);

// Compare accuracy and choose best model
```

## Security Considerations

1. **Data Privacy**: Sales data stays in your system
2. **Model Security**: Models are stored locally or in your infrastructure
3. **API Security**: Use authentication for external services
4. **Input Validation**: Validate all prediction inputs

## Future Enhancements

1. **Real-time Learning**: Update models with new data automatically
2. **Multi-variable Analysis**: Include inventory, marketing, etc.
3. **Anomaly Detection**: Identify unusual sales patterns
4. **Scenario Planning**: "What-if" analysis for business decisions

## Support

For issues or questions:
1. Check the browser console for errors
2. Review the TensorFlow.js documentation
3. Test with dummy data first
4. Ensure all dependencies are installed

---

**Note**: This feature requires sufficient historical data for accurate predictions. Start with dummy data for testing and demonstrations. 