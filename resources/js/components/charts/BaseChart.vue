<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from 'chart.js'
import { Line, Bar, Doughnut } from 'vue-chartjs'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

interface Props {
  type: 'line' | 'bar' | 'doughnut'
  data: any
  options?: any
  height?: string
  width?: string
}

const props = withDefaults(defineProps<Props>(), {
  options: () => ({}),
  height: '300px',
  width: '100%'
})

const chartRef = ref()
const chartInstance = ref()

const defaultOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top' as const,
    },
  },
}

const chartOptions = computed(() => ({
  ...defaultOptions,
  ...props.options,
}))

onMounted(() => {
  if (chartRef.value) {
    chartInstance.value = chartRef.value
  }
})

onUnmounted(() => {
  if (chartInstance.value && chartInstance.value.chart) {
    chartInstance.value.chart.destroy()
  }
})

watch(() => props.data, () => {
  if (chartInstance.value && chartInstance.value.chart) {
    chartInstance.value.chart.update()
  }
}, { deep: true })
</script>

<template>
  <div :style="{ height: height, width: width }">
    <Line
      v-if="type === 'line'"
      ref="chartRef"
      :data="data"
      :options="chartOptions"
    />
    <Bar
      v-else-if="type === 'bar'"
      ref="chartRef"
      :data="data"
      :options="chartOptions"
    />
    <Doughnut
      v-else-if="type === 'doughnut'"
      ref="chartRef"
      :data="data"
      :options="chartOptions"
    />
  </div>
</template> 