import * as tf from '@tensorflow/tfjs';

export interface SalesDataPoint {
    date: string;
    sales: number;
    invoices: number;
    dayOfWeek: number;
    dayOfMonth: number;
    month: number;
    isWeekend: boolean;
    isHoliday: boolean;
}

export interface PredictionResult {
    predictedSales: number;
    confidence: number;
    nextWeekPredictions: number[];
    nextMonthPredictions: number[];
}

export class SalesPredictor {
    private model: tf.Sequential | null = null;
    private isModelTrained = false;
    private trainingData: SalesDataPoint[] = [];

    constructor() {
        // Initialize TensorFlow.js
        tf.setBackend('cpu'); // Use CPU for better compatibility
    }

    /**
     * Prepare training data from sales history
     */
    prepareTrainingData(salesHistory: Array<{ date: string; sales: number; invoices: number }>): SalesDataPoint[] {
        return salesHistory.map(item => {
            const date = new Date(item.date);
            const dayOfWeek = date.getDay();
            const dayOfMonth = date.getDate();
            const month = date.getMonth();
            const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
            
            // Simple holiday detection (you can enhance this)
            const isHoliday = this.isHoliday(date);

            return {
                date: item.date,
                sales: item.sales,
                invoices: item.invoices,
                dayOfWeek,
                dayOfMonth,
                month,
                isWeekend,
                isHoliday,
            };
        });
    }

    /**
     * Create and train the neural network model
     */
    async trainModel(salesData: SalesDataPoint[]): Promise<void> {
        if (salesData.length < 30) {
            throw new Error('Need at least 30 days of data for training');
        }

        this.trainingData = salesData;

        // Create the model
        this.model = tf.sequential({
            layers: [
                tf.layers.dense({
                    inputShape: [6], // 6 features: dayOfWeek, dayOfMonth, month, isWeekend, isHoliday, previousSales
                    units: 64,
                    activation: 'relu',
                }),
                tf.layers.dropout({ rate: 0.2 }),
                tf.layers.dense({
                    units: 32,
                    activation: 'relu',
                }),
                tf.layers.dropout({ rate: 0.2 }),
                tf.layers.dense({
                    units: 16,
                    activation: 'relu',
                }),
                tf.layers.dense({
                    units: 1,
                    activation: 'linear',
                }),
            ],
        });

        // Compile the model
        this.model.compile({
            optimizer: tf.train.adam(0.001),
            loss: 'meanSquaredError',
            metrics: ['mae'],
        });

        // Prepare training data
        const { inputs, outputs } = this.prepareModelData(salesData);

        // Train the model
        await this.model.fit(inputs, outputs, {
            epochs: 100,
            batchSize: 32,
            validationSplit: 0.2,
            callbacks: {
                onEpochEnd: (epoch, logs) => {
                    console.log(`Epoch ${epoch + 1}: loss = ${logs?.loss?.toFixed(4)}, mae = ${logs?.mae?.toFixed(4)}`);
                },
            },
        });

        this.isModelTrained = true;
    }

    /**
     * Prepare data for model training
     */
    private prepareModelData(salesData: SalesDataPoint[]): { inputs: tf.Tensor; outputs: tf.Tensor } {
        const features: number[][] = [];
        const targets: number[] = [];

        // Normalize sales data
        const salesValues = salesData.map(d => d.sales);
        const maxSales = Math.max(...salesValues);
        const minSales = Math.min(...salesValues);

        for (let i = 7; i < salesData.length; i++) {
            const current = salesData[i];
            const previousWeek = salesData.slice(i - 7, i);
            const avgPreviousSales = previousWeek.reduce((sum, d) => sum + d.sales, 0) / previousWeek.length;

            // Normalize features
            const normalizedSales = (avgPreviousSales - minSales) / (maxSales - minSales);
            
            features.push([
                current.dayOfWeek / 6, // Normalize day of week (0-6)
                current.dayOfMonth / 31, // Normalize day of month (1-31)
                current.month / 11, // Normalize month (0-11)
                current.isWeekend ? 1 : 0,
                current.isHoliday ? 1 : 0,
                normalizedSales,
            ]);

            // Normalize target
            targets.push((current.sales - minSales) / (maxSales - minSales));
        }

        return {
            inputs: tf.tensor2d(features),
            outputs: tf.tensor2d(targets, [targets.length, 1]),
        };
    }

    /**
     * Predict sales for the next period
     */
    async predictSales(daysAhead: number = 7): Promise<PredictionResult> {
        if (!this.model || !this.isModelTrained) {
            throw new Error('Model not trained. Call trainModel() first.');
        }

        const predictions: number[] = [];
        const salesValues = this.trainingData.map(d => d.sales);
        const maxSales = Math.max(...salesValues);
        const minSales = Math.min(...salesValues);

        // Get the last 7 days for initial prediction
        const lastWeek = this.trainingData.slice(-7);
        let currentDate = new Date(this.trainingData[this.trainingData.length - 1].date);

        for (let day = 0; day < daysAhead; day++) {
            currentDate.setDate(currentDate.getDate() + 1);
            
            const dayOfWeek = currentDate.getDay();
            const dayOfMonth = currentDate.getDate();
            const month = currentDate.getMonth();
            const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
            const isHoliday = this.isHoliday(currentDate);

            // Use recent average sales for prediction
            const recentSales = lastWeek.map(d => d.sales);
            const avgRecentSales = recentSales.reduce((sum, s) => sum + s, 0) / recentSales.length;
            const normalizedSales = (avgRecentSales - minSales) / (maxSales - minSales);

            const features = tf.tensor2d([[
                dayOfWeek / 6,
                dayOfMonth / 31,
                month / 11,
                isWeekend ? 1 : 0,
                isHoliday ? 1 : 0,
                normalizedSales,
            ]]);

            const prediction = this.model!.predict(features) as tf.Tensor;
            const predictedValue = await prediction.data();
            
            // Denormalize the prediction
            const denormalizedPrediction = predictedValue[0] * (maxSales - minSales) + minSales;
            predictions.push(Math.max(0, denormalizedPrediction));

            // Clean up tensors
            features.dispose();
            prediction.dispose();
        }

        // Calculate confidence based on model performance
        const confidence = this.calculateConfidence();

        return {
            predictedSales: predictions[0], // Next day prediction
            confidence,
            nextWeekPredictions: predictions.slice(0, 7),
            nextMonthPredictions: predictions.slice(0, 30),
        };
    }

    /**
     * Calculate prediction confidence
     */
    private calculateConfidence(): number {
        // Simple confidence calculation based on data consistency
        const salesValues = this.trainingData.map(d => d.sales);
        const mean = salesValues.reduce((sum, val) => sum + val, 0) / salesValues.length;
        const variance = salesValues.reduce((sum, val) => sum + Math.pow(val - mean, 2), 0) / salesValues.length;
        const stdDev = Math.sqrt(variance);
        const coefficientOfVariation = stdDev / mean;

        // Higher CV means lower confidence
        const confidence = Math.max(0.1, Math.min(0.95, 1 - coefficientOfVariation));
        return confidence;
    }

    /**
     * Simple holiday detection (Philippine holidays)
     */
    private isHoliday(date: Date): boolean {
        const month = date.getMonth();
        const day = date.getDate();
        const dayOfWeek = date.getDay();

        // New Year's Day
        if (month === 0 && day === 1) return true;
        
        // Christmas Day
        if (month === 11 && day === 25) return true;
        
        // Independence Day (June 12)
        if (month === 5 && day === 12) return true;
        
        // Labor Day (May 1)
        if (month === 4 && day === 1) return true;
        
        // All Souls' Day (November 2)
        if (month === 10 && day === 2) return true;

        return false;
    }

    /**
     * Get model summary
     */
    getModelSummary(): string {
        if (!this.model) {
            return 'Model not created';
        }
        
        return `Model trained with ${this.trainingData.length} data points`;
    }

    /**
     * Save model to localStorage
     */
    async saveModel(): Promise<void> {
        if (!this.model) {
            throw new Error('No model to save');
        }
        
        await this.model.save('localstorage://sales-prediction-model');
    }

    /**
     * Load model from localStorage
     */
    async loadModel(): Promise<boolean> {
        try {
            this.model = await tf.loadLayersModel('localstorage://sales-prediction-model');
            this.isModelTrained = true;
            return true;
        } catch (error) {
            console.log('No saved model found');
            return false;
        }
    }
}

// Export a singleton instance
export const salesPredictor = new SalesPredictor(); 