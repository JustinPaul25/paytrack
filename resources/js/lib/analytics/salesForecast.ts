import * as tf from '@tensorflow/tfjs'

export interface SalesHistoryPoint {
  date: string
  sales: number
}

export interface SalesForecastPoint {
  date: string
  sales: number
}

export interface SalesForecastResult {
  points: SalesForecastPoint[]
  trainingLoss: number
}

const DEFAULT_LOOKAHEAD_DAYS = 7
const MAX_EPOCHS = 150
const MIN_DATAPOINTS = 3

function getFutureDate(base: string, daysToAdd: number): string {
  const baseDate = new Date(base)
  baseDate.setDate(baseDate.getDate() + daysToAdd)
  return baseDate.toISOString().slice(0, 10)
}

export async function generateSalesForecast(
  history: SalesHistoryPoint[],
  lookaheadDays: number = DEFAULT_LOOKAHEAD_DAYS
): Promise<SalesForecastResult> {
  if (typeof window === 'undefined' || history.length < MIN_DATAPOINTS) {
    return { points: [], trainingLoss: 0 }
  }

  await tf.ready()

  return tf.tidy(() => {
    const sortedHistory = [...history].sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())
    const lastHistoryItem = sortedHistory[sortedHistory.length - 1]

    const xsData = sortedHistory.map((_, index) => index)
    const ysData = sortedHistory.map((point) => point.sales)

    const xsTensor = tf.tensor2d(xsData, [xsData.length, 1])
    const ysTensor = tf.tensor2d(ysData, [ysData.length, 1])

    const model = tf.sequential()
    model.add(
      tf.layers.dense({
        units: 8,
        activation: 'relu',
        inputShape: [1],
      })
    )
    model.add(
      tf.layers.dense({
        units: 4,
        activation: 'relu',
      })
    )
    model.add(
      tf.layers.dense({
        units: 1,
      })
    )

    model.compile({
      optimizer: tf.train.adam(0.05),
      loss: 'meanSquaredError',
    })

    const epochs = Math.min(MAX_EPOCHS, sortedHistory.length * 30)

    return model
      .fit(xsTensor, ysTensor, {
        epochs,
        shuffle: true,
        verbose: 0,
      })
      .then((historyResult) => {
        const futureIndices = Array.from({ length: lookaheadDays }, (_, index) => xsData.length - 1 + (index + 1))
        const futureTensor = tf.tensor2d(futureIndices, [futureIndices.length, 1])
        const predictionsTensor = model.predict(futureTensor) as tf.Tensor
        const predictions = Array.from(predictionsTensor.dataSync())

        tf.dispose([xsTensor, ysTensor, futureTensor, predictionsTensor])

        const points = predictions.map((prediction, index) => ({
          date: getFutureDate(lastHistoryItem.date, index + 1),
          sales: Number.parseFloat(prediction.toFixed(2)),
        }))

        return {
          points,
          trainingLoss: historyResult.history.loss?.at(-1) ?? 0,
        }
      })
      .finally(() => {
        model.dispose()
      })
  })
}

