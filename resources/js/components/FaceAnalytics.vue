<template>
  <div class="face-analytics-container">
    <!-- Header -->
    <div class="mb-8">
      <h2 class="mb-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
        Analytics Face Recognition
      </h2>
      <p class="text-gray-600 dark:text-gray-300">
        Monitoring kinerja dan analisis sistem pengenalan wajah
      </p>
    </div>

    <!-- Time Range Selector -->
    <div class="mb-6">
      <div
        class="flex flex-wrap items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
      >
        <div class="flex items-center space-x-2">
          <CalendarIcon class="h-5 w-5 text-gray-500 dark:text-gray-400" />
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Periode:</span>
        </div>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="range in timeRanges"
            :key="range.value"
            :class="[
              'rounded-lg px-3 py-1.5 text-sm transition-colors',
              selectedTimeRange === range.value
                ? 'bg-emerald-500 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600',
            ]"
            @click="selectedTimeRange = range.value"
          >
            {{ range.label }}
          </button>
        </div>
        <div class="flex items-center space-x-2">
          <input
            v-model="customDateRange.start"
            type="date"
            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
          >
          <span class="text-gray-500 dark:text-gray-400">-</span>
          <input
            v-model="customDateRange.end"
            type="date"
            class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
          >
        </div>
      </div>
    </div>

    <!-- Key Metrics -->
    <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
      <div
        class="rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-emerald-100 p-6 dark:border-emerald-700/50 dark:from-emerald-900/30 dark:to-emerald-800/20"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
              Total Deteksi
            </p>
            <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">
              {{ formatNumber(metrics.totalDetections) }}
            </p>
            <div class="mt-1 flex items-center">
              <ArrowUpIcon class="mr-1 h-4 w-4 text-emerald-500" />
              <span class="text-sm text-emerald-600 dark:text-emerald-400">+{{ metrics.detectionGrowth }}%</span>
            </div>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500">
            <FaceSmileIcon class="h-6 w-6 text-white" />
          </div>
        </div>
      </div>

      <div
        class="rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50 to-blue-100 p-6 dark:border-blue-700/50 dark:from-blue-900/30 dark:to-blue-800/20"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-blue-600 dark:text-blue-400">
              Akurasi Rata-rata
            </p>
            <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
              {{ metrics.averageAccuracy }}%
            </p>
            <div class="mt-1 flex items-center">
              <ArrowUpIcon class="mr-1 h-4 w-4 text-blue-500" />
              <span class="text-sm text-blue-600 dark:text-blue-400">+{{ metrics.accuracyImprovement }}%</span>
            </div>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-500">
            <ChartBarIcon class="h-6 w-6 text-white" />
          </div>
        </div>
      </div>

      <div
        class="rounded-xl border border-purple-200 bg-gradient-to-br from-purple-50 to-purple-100 p-6 dark:border-purple-700/50 dark:from-purple-900/30 dark:to-purple-800/20"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-purple-600 dark:text-purple-400">
              Liveness Score
            </p>
            <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">
              {{ metrics.averageLiveness }}%
            </p>
            <div class="mt-1 flex items-center">
              <ArrowUpIcon class="mr-1 h-4 w-4 text-purple-500" />
              <span class="text-sm text-purple-600 dark:text-purple-400">+{{ metrics.livenessImprovement }}%</span>
            </div>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-500">
            <ShieldCheckIcon class="h-6 w-6 text-white" />
          </div>
        </div>
      </div>

      <div
        class="rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-amber-100 p-6 dark:border-amber-700/50 dark:from-amber-900/30 dark:to-amber-800/20"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-amber-600 dark:text-amber-400">
              Waktu Proses
            </p>
            <p class="text-2xl font-bold text-amber-900 dark:text-amber-100">
              {{ metrics.averageProcessingTime }}ms
            </p>
            <div class="mt-1 flex items-center">
              <ArrowDownIcon class="mr-1 h-4 w-4 text-amber-500" />
              <span class="text-sm text-amber-600 dark:text-amber-400">-{{ metrics.processingImprovement }}%</span>
            </div>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500">
            <ClockIcon class="h-6 w-6 text-white" />
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
      <!-- Detection Trends Chart -->
      <div
        class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
      >
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Tren Deteksi Wajah
          </h3>
          <div class="flex items-center space-x-2">
            <div class="h-3 w-3 rounded-full bg-emerald-500" />
            <span class="text-sm text-gray-600 dark:text-gray-400">Berhasil</span>
            <div class="h-3 w-3 rounded-full bg-red-500" />
            <span class="text-sm text-gray-600 dark:text-gray-400">Gagal</span>
          </div>
        </div>
        <div class="h-64">
          <canvas ref="detectionChart" />
        </div>
      </div>

      <!-- Accuracy Distribution -->
      <div
        class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
      >
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Distribusi Akurasi
          </h3>
          <select
            v-model="selectedAlgorithm"
            class="rounded-lg border border-gray-300 bg-gray-100 px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
          >
            <option value="all">
              Semua Algoritma
            </option>
            <option value="face-api">
              Face-API.js
            </option>
            <option value="mediapipe">
              MediaPipe
            </option>
          </select>
        </div>
        <div class="h-64">
          <canvas ref="accuracyChart" />
        </div>
      </div>
    </div>

    <!-- Performance Metrics -->
    <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- Algorithm Performance -->
      <div
        class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
      >
        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
          Performa Algoritma
        </h3>
        <div class="space-y-4">
          <div v-for="algo in algorithmPerformance" :key="algo.name" class="space-y-2">
            <div class="flex justify-between text-sm">
              <span class="font-medium text-gray-700 dark:text-gray-300">{{ algo.name }}</span>
              <span class="text-gray-500 dark:text-gray-400">{{ algo.accuracy }}%</span>
            </div>
            <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
              <div
                :class="[
                  'h-2 rounded-full transition-all duration-500',
                  algo.accuracy >= 90
                    ? 'bg-emerald-500'
                    : algo.accuracy >= 70
                      ? 'bg-amber-500'
                      : 'bg-red-500',
                ]"
                :style="{ width: `${algo.accuracy}%` }"
              />
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
              {{ algo.totalDetections }} deteksi • {{ algo.averageTime }}ms
            </div>
          </div>
        </div>
      </div>

      <!-- Device Performance -->
      <div
        class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
      >
        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
          Performa Perangkat
        </h3>
        <div class="space-y-4">
          <div v-for="device in devicePerformance" :key="device.type" class="space-y-2">
            <div class="flex justify-between text-sm">
              <span class="font-medium text-gray-700 dark:text-gray-300">{{ device.type }}</span>
              <span class="text-gray-500 dark:text-gray-400">{{ device.usage }}%</span>
            </div>
            <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
              <div
                :class="[
                  'h-2 rounded-full transition-all duration-500',
                  device.usage <= 50
                    ? 'bg-emerald-500'
                    : device.usage <= 80
                      ? 'bg-amber-500'
                      : 'bg-red-500',
                ]"
                :style="{ width: `${device.usage}%` }"
              />
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
              {{ device.status }} • {{ device.temperature }}°C
            </div>
          </div>
        </div>
      </div>

      <!-- Error Analysis -->
      <div
        class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
      >
        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
          Analisis Error
        </h3>
        <div class="space-y-3">
          <div
            v-for="error in errorAnalysis"
            :key="error.type"
            class="flex items-center justify-between"
          >
            <div class="flex items-center space-x-2">
              <div
                :class="[
                  'h-3 w-3 rounded-full',
                  error.severity === 'high'
                    ? 'bg-red-500'
                    : error.severity === 'medium'
                      ? 'bg-amber-500'
                      : 'bg-blue-500',
                ]"
              />
              <span class="text-sm text-gray-700 dark:text-gray-300">{{ error.type }}</span>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
              {{ error.count }}
            </div>
          </div>
        </div>
        <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-600">
          <div class="text-sm text-gray-600 dark:text-gray-400">
            Total Error: {{ totalErrors }}
          </div>
          <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Error Rate: {{ errorRate }}%
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div
      class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
    >
      <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          Aktivitas Terbaru
        </h3>
        <button
          :disabled="loadingActivity"
          class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
          @click="refreshActivity"
        >
          <ArrowPathIcon :class="['mr-1 h-4 w-4', { 'animate-spin': loadingActivity }]" />
          Refresh
        </button>
      </div>

      <div class="space-y-3">
        <div
          v-for="activity in recentActivity"
          :key="activity.id"
          class="flex items-center space-x-4 rounded-lg p-3 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          <div class="flex-shrink-0">
            <div
              :class="[
                'flex h-10 w-10 items-center justify-center rounded-full',
                activity.type === 'success'
                  ? 'bg-emerald-100 dark:bg-emerald-800'
                  : activity.type === 'error'
                    ? 'bg-red-100 dark:bg-red-800'
                    : 'bg-amber-100 dark:bg-amber-800',
              ]"
            >
              <FaceSmileIcon
                v-if="activity.type === 'success'"
                class="h-5 w-5 text-emerald-600 dark:text-emerald-400"
              />
              <ExclamationTriangleIcon
                v-else-if="activity.type === 'error'"
                class="h-5 w-5 text-red-600 dark:text-red-400"
              />
              <ClockIcon v-else class="h-5 w-5 text-amber-600 dark:text-amber-400" />
            </div>
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between">
              <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ activity.employee }}
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ formatTime(activity.timestamp) }}
              </p>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-300">
              {{ activity.action }}
            </p>
            <div class="mt-1 flex items-center space-x-4">
              <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ activity.algorithm }}
              </span>
              <span class="text-xs text-gray-500 dark:text-gray-400">
                Akurasi: {{ activity.confidence }}%
              </span>
              <span class="text-xs text-gray-500 dark:text-gray-400">
                Liveness: {{ activity.liveness }}%
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import {
  CalendarIcon,
  FaceSmileIcon,
  ChartBarIcon,
  ShieldCheckIcon,
  ClockIcon,
  ArrowUpIcon,
  ArrowDownIcon,
  ArrowPathIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'

// Reactive state
const selectedTimeRange = ref('today')
const selectedAlgorithm = ref('all')
const loadingActivity = ref(false)

const customDateRange = reactive({
  start: new Date().toISOString().split('T')[0],
  end: new Date().toISOString().split('T')[0],
})

const timeRanges = ref([
  { value: 'today', label: 'Hari Ini' },
  { value: 'yesterday', label: 'Kemarin' },
  { value: 'week', label: '7 Hari' },
  { value: 'month', label: '30 Hari' },
  { value: 'custom', label: 'Custom' },
])

const metrics = reactive({
  totalDetections: 2847,
  detectionGrowth: 12.5,
  averageAccuracy: 94.2,
  accuracyImprovement: 3.1,
  averageLiveness: 87.8,
  livenessImprovement: 5.2,
  averageProcessingTime: 234,
  processingImprovement: 8.7,
})

const algorithmPerformance = ref([
  {
    name: 'Face-API.js',
    accuracy: 94.2,
    totalDetections: 1523,
    averageTime: 245,
  },
  {
    name: 'MediaPipe',
    accuracy: 91.8,
    totalDetections: 1324,
    averageTime: 189,
  },
])

const devicePerformance = ref([
  {
    type: 'CPU',
    usage: 45,
    status: 'Normal',
    temperature: 67,
  },
  {
    type: 'GPU',
    usage: 23,
    status: 'Normal',
    temperature: 58,
  },
  {
    type: 'Memory',
    usage: 67,
    status: 'High',
    temperature: 45,
  },
])

const errorAnalysis = ref([
  {
    type: 'Poor Lighting',
    count: 23,
    severity: 'medium',
  },
  {
    type: 'Face Not Detected',
    count: 18,
    severity: 'high',
  },
  {
    type: 'Low Confidence',
    count: 31,
    severity: 'low',
  },
  {
    type: 'Liveness Failed',
    count: 12,
    severity: 'high',
  },
  {
    type: 'Camera Error',
    count: 5,
    severity: 'high',
  },
])

const recentActivity = ref([
  {
    id: 1,
    employee: 'John Doe',
    action: 'Check-in berhasil',
    type: 'success',
    algorithm: 'Face-API.js',
    confidence: 94,
    liveness: 89,
    timestamp: new Date(Date.now() - 300000),
  },
  {
    id: 2,
    employee: 'Jane Smith',
    action: 'Liveness verification gagal',
    type: 'error',
    algorithm: 'MediaPipe',
    confidence: 78,
    liveness: 45,
    timestamp: new Date(Date.now() - 600000),
  },
  {
    id: 3,
    employee: 'Mike Johnson',
    action: 'Check-out berhasil',
    type: 'success',
    algorithm: 'Face-API.js',
    confidence: 96,
    liveness: 92,
    timestamp: new Date(Date.now() - 900000),
  },
  {
    id: 4,
    employee: 'Sarah Wilson',
    action: 'Deteksi wajah timeout',
    type: 'warning',
    algorithm: 'MediaPipe',
    confidence: 0,
    liveness: 0,
    timestamp: new Date(Date.now() - 1200000),
  },
])

// Computed
const totalErrors = computed(() => {
  return errorAnalysis.value.reduce((sum, error) => sum + error.count, 0)
})

const errorRate = computed(() => {
  const total = metrics.totalDetections
  return total > 0 ? ((totalErrors.value / total) * 100).toFixed(1) : 0
})

// Chart refs
const detectionChart = ref(null)
const accuracyChart = ref(null)

// Methods
const formatNumber = (num) => {
  return new Intl.NumberFormat('id-ID').format(num)
}

const formatTime = (date) => {
  return new Intl.DateTimeFormat('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  }).format(date)
}

const refreshActivity = async () => {
  loadingActivity.value = true

  try {
    // Simulate API call
    await new Promise((resolve) => setTimeout(resolve, 1000))

    // Update activity with new data
    recentActivity.value.unshift({
      id: Date.now(),
      employee: 'New Employee',
      action: 'Face enrollment completed',
      type: 'success',
      algorithm: 'Face-API.js',
      confidence: 92,
      liveness: 88,
      timestamp: new Date(),
    })

    // Keep only last 20 activities
    if (recentActivity.value.length > 20) {
      recentActivity.value = recentActivity.value.slice(0, 20)
    }
  } catch (error) {
    console.error('Failed to refresh activity:', error)
  } finally {
    loadingActivity.value = false
  }
}

const initializeCharts = () => {
  // Initialize detection trends chart
  if (detectionChart.value) {
    const ctx = detectionChart.value.getContext('2d')

    // Sample data for demonstration
    const labels = ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00']
    const successData = [12, 19, 45, 78, 65, 34]
    const failureData = [2, 3, 8, 12, 7, 4]

    // Simple chart drawing (in production, use Chart.js or similar)
    drawLineChart(ctx, labels, [
      { label: 'Berhasil', data: successData, color: '#10B981' },
      { label: 'Gagal', data: failureData, color: '#EF4444' },
    ])
  }

  // Initialize accuracy distribution chart
  if (accuracyChart.value) {
    const ctx = accuracyChart.value.getContext('2d')

    // Sample data
    const ranges = ['60-70%', '70-80%', '80-90%', '90-95%', '95-100%']
    const counts = [5, 12, 45, 89, 156]

    drawBarChart(ctx, ranges, counts, '#3B82F6')
  }
}

const drawLineChart = (ctx, labels, datasets) => {
  const canvas = ctx.canvas
  const width = canvas.width
  const height = canvas.height

  ctx.clearRect(0, 0, width, height)

  // Basic line chart implementation
  // In production, use a proper charting library
  ctx.strokeStyle = '#E5E7EB'
  ctx.lineWidth = 1

  // Draw grid
  for (let i = 0; i <= 5; i++) {
    const y = (height / 5) * i
    ctx.beginPath()
    ctx.moveTo(0, y)
    ctx.lineTo(width, y)
    ctx.stroke()
  }

  // Draw data lines
  datasets.forEach((dataset) => {
    ctx.strokeStyle = dataset.color
    ctx.lineWidth = 2
    ctx.beginPath()

    dataset.data.forEach((value, index) => {
      const x = (width / (dataset.data.length - 1)) * index
      const y = height - (value / Math.max(...dataset.data)) * height

      if (index === 0) {
        ctx.moveTo(x, y)
      } else {
        ctx.lineTo(x, y)
      }
    })

    ctx.stroke()
  })
}

const drawBarChart = (ctx, labels, data, color) => {
  const canvas = ctx.canvas
  const width = canvas.width
  const height = canvas.height

  ctx.clearRect(0, 0, width, height)

  const barWidth = width / data.length
  const maxValue = Math.max(...data)

  data.forEach((value, index) => {
    const barHeight = (value / maxValue) * height
    const x = index * barWidth
    const y = height - barHeight

    ctx.fillStyle = color
    ctx.fillRect(x, y, barWidth * 0.8, barHeight)
  })
}

const updateCharts = () => {
  // Update charts based on selected time range and algorithm
  console.log('Updating charts for:', selectedTimeRange.value, selectedAlgorithm.value)
  initializeCharts()
}

// Watchers
watch([selectedTimeRange, selectedAlgorithm], () => {
  updateCharts()
})

// Lifecycle
onMounted(() => {
  initializeCharts()

  // Set up real-time updates
  const interval = setInterval(() => {
    // Simulate real-time data updates
    metrics.totalDetections += Math.floor(Math.random() * 3)
    metrics.averageAccuracy = Math.max(
      85,
      Math.min(100, metrics.averageAccuracy + (Math.random() - 0.5) * 2)
    )
    metrics.averageLiveness = Math.max(
      70,
      Math.min(100, metrics.averageLiveness + (Math.random() - 0.5) * 3)
    )
    metrics.averageProcessingTime = Math.max(
      100,
      Math.min(500, metrics.averageProcessingTime + (Math.random() - 0.5) * 20)
    )
  }, 5000)

  onUnmounted(() => {
    clearInterval(interval)
  })
})
</script>

<style scoped>
.face-analytics-container {
  @apply mx-auto max-w-7xl p-6;
}

/* Chart canvas styling */
canvas {
  width: 100%;
  height: 100%;
}

/* Custom animations */
@keyframes pulse-ring {
  0% {
    transform: scale(1);
    opacity: 1;
  }
  100% {
    transform: scale(1.5);
    opacity: 0;
  }
}

.pulse-ring {
  animation: pulse-ring 2s ease-out infinite;
}
</style>
