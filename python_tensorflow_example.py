"""
Python TensorFlow Sales Prediction Service
This can be run as a separate service and called from your Laravel application
"""

import numpy as np
import pandas as pd
import tensorflow as tf
from tensorflow import keras
from tensorflow.keras import layers
from sklearn.preprocessing import MinMaxScaler
from sklearn.metrics import mean_squared_error, mean_absolute_error
import json
from datetime import datetime, timedelta
from flask import Flask, request, jsonify
import warnings
warnings.filterwarnings('ignore')

app = Flask(__name__)

class SalesPredictionModel:
    def __init__(self):
        self.model = None
        self.scaler = MinMaxScaler()
        self.sequence_length = 30  # Use 30 days to predict next day
        self.is_trained = False
        
    def create_model(self, input_shape):
        """Create LSTM model for time series prediction"""
        model = keras.Sequential([
            layers.LSTM(128, return_sequences=True, input_shape=input_shape),
            layers.Dropout(0.2),
            layers.LSTM(64, return_sequences=False),
            layers.Dropout(0.2),
            layers.Dense(32, activation='relu'),
            layers.Dense(1, activation='linear')
        ])
        
        model.compile(
            optimizer=keras.optimizers.Adam(learning_rate=0.001),
            loss='mse',
            metrics=['mae']
        )
        
        return model
    
    def prepare_data(self, sales_data, sequence_length=30):
        """Prepare data for LSTM model"""
        # Convert to numpy array
        data = np.array(sales_data)
        
        # Normalize the data
        data_normalized = self.scaler.fit_transform(data.reshape(-1, 1))
        
        X, y = [], []
        for i in range(sequence_length, len(data_normalized)):
            X.append(data_normalized[i-sequence_length:i])
            y.append(data_normalized[i])
        
        return np.array(X), np.array(y)
    
    def train(self, sales_data, epochs=100, batch_size=32):
        """Train the model"""
        if len(sales_data) < self.sequence_length + 10:
            raise ValueError(f"Need at least {self.sequence_length + 10} data points")
        
        # Prepare data
        X, y = self.prepare_data(sales_data, self.sequence_length)
        
        # Split data
        split = int(len(X) * 0.8)
        X_train, X_test = X[:split], X[split:]
        y_train, y_test = y[:split], y[split:]
        
        # Create and train model
        self.model = self.create_model((X.shape[1], X.shape[2]))
        
        history = self.model.fit(
            X_train, y_train,
            epochs=epochs,
            batch_size=batch_size,
            validation_data=(X_test, y_test),
            verbose=0
        )
        
        self.is_trained = True
        
        # Calculate model performance
        y_pred = self.model.predict(X_test)
        mse = mean_squared_error(y_test, y_pred)
        mae = mean_absolute_error(y_test, y_pred)
        
        return {
            'mse': float(mse),
            'mae': float(mae),
            'final_loss': float(history.history['loss'][-1]),
            'final_val_loss': float(history.history['val_loss'][-1])
        }
    
    def predict(self, sales_data, days_ahead=30):
        """Predict future sales"""
        if not self.is_trained:
            raise ValueError("Model not trained. Call train() first.")
        
        # Get the last sequence_length days
        recent_data = sales_data[-self.sequence_length:]
        predictions = []
        
        current_sequence = recent_data.copy()
        
        for _ in range(days_ahead):
            # Normalize current sequence
            current_normalized = self.scaler.transform(current_sequence.reshape(-1, 1))
            
            # Reshape for prediction
            X = current_normalized[-self.sequence_length:].reshape(1, self.sequence_length, 1)
            
            # Predict next value
            pred_normalized = self.model.predict(X, verbose=0)
            
            # Denormalize prediction
            pred_denormalized = self.scaler.inverse_transform(pred_normalized)[0][0]
            predictions.append(max(0, pred_denormalized))  # Ensure non-negative
            
            # Update sequence for next prediction
            current_sequence = np.append(current_sequence[1:], pred_denormalized)
        
        return predictions
    
    def calculate_confidence(self, sales_data):
        """Calculate prediction confidence based on data consistency"""
        if len(sales_data) < 2:
            return 0.5
        
        # Calculate coefficient of variation
        mean_sales = np.mean(sales_data)
        std_sales = np.std(sales_data)
        cv = std_sales / mean_sales if mean_sales > 0 else 1
        
        # Higher CV means lower confidence
        confidence = max(0.1, min(0.95, 1 - cv))
        return confidence

# Global model instance
model = SalesPredictionModel()

@app.route('/predict', methods=['POST'])
def predict_sales():
    """API endpoint for sales prediction"""
    try:
        data = request.json
        sales_data = data.get('sales_data', [])
        prediction_days = data.get('prediction_days', 30)
        
        if not sales_data:
            return jsonify({'error': 'No sales data provided'}), 400
        
        # Extract sales values
        sales_values = [item['sales'] for item in sales_data]
        
        # Train model if not trained or retrain if requested
        if not model.is_trained or data.get('retrain', False):
            if len(sales_values) < model.sequence_length + 10:
                return jsonify({'error': f'Need at least {model.sequence_length + 10} data points'}), 400
            
            training_result = model.train(sales_values)
        else:
            training_result = None
        
        # Make predictions
        predictions = model.predict(sales_values, prediction_days)
        
        # Calculate confidence
        confidence = model.calculate_confidence(sales_values)
        
        # Generate dates for predictions
        last_date = datetime.strptime(sales_data[-1]['date'], '%Y-%m-%d')
        prediction_dates = []
        for i in range(1, prediction_days + 1):
            pred_date = last_date + timedelta(days=i)
            prediction_dates.append(pred_date.strftime('%Y-%m-%d'))
        
        # Format response
        response = {
            'predictions': [
                {
                    'date': date,
                    'predicted_sales': float(pred),
                    'confidence': confidence
                }
                for date, pred in zip(prediction_dates, predictions)
            ],
            'summary': {
                'total_predicted_sales': float(sum(predictions)),
                'average_daily_prediction': float(np.mean(predictions)),
                'confidence': confidence,
                'model_performance': training_result
            },
            'model_info': {
                'is_trained': model.is_trained,
                'data_points_used': len(sales_values),
                'prediction_horizon': prediction_days,
                'last_training_date': datetime.now().isoformat()
            }
        }
        
        return jsonify(response)
        
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'model_trained': model.is_trained,
        'timestamp': datetime.now().isoformat()
    })

if __name__ == '__main__':
    print("Starting TensorFlow Sales Prediction Service...")
    print("API Endpoints:")
    print("- POST /predict - Get sales predictions")
    print("- GET /health - Health check")
    print("\nExample usage:")
    print("curl -X POST http://localhost:5000/predict \\")
    print("  -H 'Content-Type: application/json' \\")
    print("  -d '{\"sales_data\": [{\"date\": \"2024-01-01\", \"sales\": 1000}], \"prediction_days\": 30}'")
    
    app.run(host='0.0.0.0', port=5000, debug=False) 