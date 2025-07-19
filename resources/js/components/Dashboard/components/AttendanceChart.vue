<template>
  <div class="relative">
    <!-- Loading State -->
    <div v-if="loading" class="h-80 flex items-center justify-center">
      <div class="animate-pulse flex flex-col items-center">
        <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full mb-4"></div>
        <div class="text-sm text-gray-500 dark:text-gray-400">Loading chart data...</div>
      </div>
    </div>

    <!-- Chart Container -->
    <div v-else ref="chartContainer" class="h-80">
      <canvas ref="chartCanvas"></canvas>
    </div>

    <!-- No Data State -->
    <div v-if="!loading && !hasData" class="h-80 flex items-center justify-center">
      <div class="text-center">
        <svg class="w-12 h-12 text-gray-400 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <p class="text-sm text-gray-500 dark:text-gray-400">No attendance data available</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, computed, nextTick } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

interface Props {
  data: {
    labels: string[]
    datasets: Array<{
      label: string
      data: number[]
      borderColor?: string
      backgroundColor?: string
      tension?: number
    }>
  }
  loading?: boolean
  period: 'week' | 'month' | 'quarter'
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const chartCanvas = ref<HTMLCanvasElement>()
const chartContainer = ref<HTMLDivElement>()
let chartInstance: Chart | null = null

const hasData = computed(() => {
  return props.data?.labels?.length > 0 && props.data?.datasets?.length > 0
})

const isDarkMode = computed(() => {
  return document.documentElement.classList.contains('dark')
})

const getChartOptions = () => {
  const isDark = isDarkMode.value
  
  return {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      intersect: false,
      mode: 'index' as const,
    },
    plugins: {
      legend: {
        display: true,
        position: 'top' as const,
        labels: {
          usePointStyle: true,
          pointStyle: 'circle',
          color: isDark ? '#E5E7EB' : '#374151',
          font: {
            size: 12,
            family: 'Inter, sans-serif'
          },
          padding: 20
        }
      },
      tooltip: {
        backgroundColor: isDark ? '#1F2937' : '#FFFFFF',
        titleColor: isDark ? '#F9FAFB' : '#111827',
        bodyColor: isDark ? '#E5E7EB' : '#374151',
        borderColor: isDark ? '#374151' : '#E5E7EB',
        borderWidth: 1,
        cornerRadius: 8,
        padding: 12,
        displayColors: true,
        titleFont: {
          size: 14,
          weight: 'bold'
        },
        bodyFont: {
          size: 13
        }
      }
    },
    scales: {
      x: {
        border: {
          display: false
        },
        grid: {
          display: true,
          color: isDark ? '#374151' : '#F3F4F6',
          drawBorder: false
        },
        ticks: {
          color: isDark ? '#9CA3AF' : '#6B7280',
          font: {
            size: 11,
            family: 'Inter, sans-serif'
          },
          padding: 8
        }
      },
      y: {
        border: {
          display: false
        },
        grid: {
          display: true,
          color: isDark ? '#374151' : '#F3F4F6',
          drawBorder: false
        },
        ticks: {
          color: isDark ? '#9CA3AF' : '#6B7280',
          font: {
            size: 11,
            family: 'Inter, sans-serif'
          },
          padding: 8,
          callback: function(value: any) {
            return value + '%'
          }
        },
        beginAtZero: true,
        max: 100
      }
    },
    elements: {
      point: {
        radius: 4,
        hoverRadius: 6,
        borderWidth: 2,
        hoverBorderWidth: 3
      },
      line: {
        borderWidth: 3,
        tension: 0.4
      }
    },
    animation: {
      duration: 750,
      easing: 'easeInOutQuart'
    }
  }
}

const getChartData = () => {
  if (!hasData.value) return { labels: [], datasets: [] }
  
  const datasets = props.data.datasets.map((dataset, index) => {
    const colors = [
      { border: '#059669', background: 'rgba(5, 150, 105, 0.1)' }, // emerald
      { border: '#3B82F6', background: 'rgba(59, 130, 246, 0.1)' }, // blue
      { border: '#8B5CF6', background: 'rgba(139, 92, 246, 0.1)' }, // violet
      { border: '#F59E0B', background: 'rgba(245, 158, 11, 0.1)' }, // amber
    ]
    
    const colorSet = colors[index % colors.length]
    
    return {
      ...dataset,
      borderColor: dataset.borderColor || colorSet.border,
      backgroundColor: dataset.backgroundColor || colorSet.background,
      tension: dataset.tension || 0.4,
      fill: true,
      pointBackgroundColor: dataset.borderColor || colorSet.border,
      pointBorderColor: '#FFFFFF',
      pointHoverBackgroundColor: dataset.borderColor || colorSet.border,
      pointHoverBorderColor: '#FFFFFF'
    }
  })
  
  return {
    labels: props.data.labels,
    datasets
  }
}

const createChart = async () => {
  if (!chartCanvas.value || !hasData.value) return
  
  await nextTick()
  
  const ctx = chartCanvas.value.getContext('2d')
  if (!ctx) return
  
  // Destroy existing chart
  if (chartInstance) {
    chartInstance.destroy()
  }
  
  chartInstance = new Chart(ctx, {
    type: 'line',
    data: getChartData(),
    options: getChartOptions()
  })
}

const updateChart = () => {
  if (!chartInstance || !hasData.value) return
  
  chartInstance.data = getChartData()
  chartInstance.options = getChartOptions()
  chartInstance.update('active')
}

// Watch for data changes
watch(() => props.data, () => {
  if (chartInstance && hasData.value) {
    updateChart()
  } else if (hasData.value) {
    createChart()
  }
}, { deep: true })

// Watch for theme changes
watch(isDarkMode, () => {
  if (chartInstance) {
    updateChart()
  }
})

// Watch for loading state
watch(() => props.loading, (isLoading) => {
  if (!isLoading && hasData.value) {
    nextTick(() => {
      createChart()
    })
  }
})

onMounted(() => {
  if (hasData.value && !props.loading) {
    createChart()
  }
  
  // Listen for theme changes
  const observer = new MutationObserver(() => {
    if (chartInstance) {
      updateChart()
    }
  })
  
  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['class']
  })
  
  onUnmounted(() => {
    observer.disconnect()
  })
})

onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy()
  }
})
</script>