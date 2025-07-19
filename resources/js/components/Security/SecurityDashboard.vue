<template>
  <div class="security-dashboard space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">
          Security Monitoring
        </h2>
        <p class="text-gray-600">
          Real-time security analytics and threat detection
        </p>
      </div>
      <div class="flex items-center space-x-4">
        <button
          :disabled="loading"
          class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-50"
          @click="refreshData"
        >
          <svg
            class="mr-2 h-4 w-4"
            :class="{ 'animate-spin': loading }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
          Refresh
        </button>
        <select
          v-model="timeRange"
          class="rounded-md border border-gray-300 px-3 py-2 text-sm"
          @change="refreshData"
        >
          <option value="1h">
            Last Hour
          </option>
          <option value="24h">
            Last 24 Hours
          </option>
          <option value="7d">
            Last 7 Days
          </option>
          <option value="30d">
            Last 30 Days
          </option>
        </select>
      </div>
    </div>

    <!-- Alert Banner -->
    <div v-if="criticalAlerts.length > 0" class="rounded-lg border border-red-200 bg-red-50 p-4">
      <div class="flex items-start">
        <svg class="mr-3 mt-0.5 h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
            clip-rule="evenodd"
          />
        </svg>
        <div class="flex-1">
          <h3 class="text-sm font-medium text-red-800">
            Critical Security Alerts
          </h3>
          <div class="mt-2 space-y-1">
            <p
              v-for="alert in criticalAlerts.slice(0, 3)"
              :key="alert.id"
              class="text-sm text-red-700"
            >
              {{ alert.message }}
            </p>
          </div>
          <div v-if="criticalAlerts.length > 3" class="mt-2">
            <button
              class="text-sm font-medium text-red-800 hover:text-red-900"
              @click="showAllAlerts = true"
            >
              View all {{ criticalAlerts.length }} alerts
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Security Metrics Overview -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
      <div
        v-for="metric in securityMetrics"
        :key="metric.label"
        class="rounded-lg border bg-white p-6 shadow"
      >
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <component :is="metric.icon" :class="metric.iconColor" class="h-8 w-8" />
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">
              {{ metric.label }}
            </p>
            <p class="text-2xl font-bold text-gray-900">
              {{ formatNumber(metric.value) }}
            </p>
            <p v-if="metric.change" :class="metric.changeColor" class="text-sm">
              {{ metric.change > 0 ? '+' : '' }}{{ metric.change }}% from last period
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Real-time Activity and Charts -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <!-- 2FA Activity Chart -->
      <div class="rounded-lg border bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">
          2FA Authentication Activity
        </h3>
        <div class="h-64">
          <canvas ref="twoFactorChart" />
        </div>
      </div>

      <!-- Security Events -->
      <div class="rounded-lg border bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">
          Recent Security Events
        </h3>
        <div class="max-h-64 space-y-3 overflow-y-auto">
          <div
            v-for="event in recentEvents"
            :key="event.id"
            class="flex items-start space-x-3 rounded-lg p-3 hover:bg-gray-50"
          >
            <div class="flex-shrink-0">
              <div
                :class="getEventStatusColor(event.risk_level)"
                class="mt-2 h-2 w-2 rounded-full"
              />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-gray-900">
                {{ event.action }}
              </p>
              <p class="truncate text-sm text-gray-500">
                {{ event.description }}
              </p>
              <p class="text-xs text-gray-400">
                {{ formatTime(event.created_at) }}
              </p>
            </div>
            <div class="flex-shrink-0">
              <span
                :class="getRiskBadgeColor(event.risk_level)"
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
              >
                {{ event.risk_level }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Failed Login Attempts Map and Top IPs -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- Failed Login Trends -->
      <div class="rounded-lg border bg-white p-6 shadow lg:col-span-2">
        <h3 class="mb-4 text-lg font-medium text-gray-900">
          Failed Login Trends
        </h3>
        <div class="h-64">
          <canvas ref="failedLoginsChart" />
        </div>
      </div>

      <!-- Top Risk IPs -->
      <div class="rounded-lg border bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">
          Top Risk IP Addresses
        </h3>
        <div class="space-y-3">
          <div
            v-for="ip in topRiskIPs"
            :key="ip.address"
            class="flex items-center justify-between rounded-lg bg-gray-50 p-3"
          >
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-900">
                {{ ip.address }}
              </p>
              <p class="text-xs text-gray-500">
                {{ ip.location || 'Unknown location' }}
              </p>
            </div>
            <div class="text-right">
              <p class="text-sm font-bold text-red-600">
                {{ ip.failures }}
              </p>
              <p class="text-xs text-gray-500">
                failures
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 2FA Security Report -->
    <div class="rounded-lg border bg-white p-6 shadow">
      <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">
          2FA Security Analysis
        </h3>
        <button
          class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          @click="downloadReport"
        >
          <svg
            class="mr-2 h-4 w-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>
          Download Report
        </button>
      </div>

      <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div class="text-center">
          <p class="text-2xl font-bold text-green-600">
            {{ twoFactorReport.success_rate }}%
          </p>
          <p class="text-sm text-gray-500">
            Success Rate
          </p>
        </div>
        <div class="text-center">
          <p class="text-2xl font-bold text-yellow-600">
            {{ twoFactorReport.locked_accounts }}
          </p>
          <p class="text-sm text-gray-500">
            Locked Accounts
          </p>
        </div>
        <div class="text-center">
          <p class="text-2xl font-bold text-red-600">
            {{ twoFactorReport.threats_detected }}
          </p>
          <p class="text-sm text-gray-500">
            Threats Detected
          </p>
        </div>
      </div>

      <!-- Recommendations -->
      <div v-if="recommendations.length > 0" class="mt-6">
        <h4 class="mb-3 text-sm font-medium text-gray-900">
          Security Recommendations
        </h4>
        <div class="space-y-2">
          <div
            v-for="rec in recommendations"
            :key="rec.id"
            class="flex items-start space-x-3 rounded-lg border p-3"
            :class="getRecommendationBorder(rec.type)"
          >
            <div class="flex-shrink-0">
              <component
                :is="getRecommendationIcon(rec.type)"
                :class="getRecommendationIconColor(rec.type)"
                class="mt-0.5 h-5 w-5"
              />
            </div>
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-900">
                {{ rec.title }}
              </p>
              <p class="text-sm text-gray-600">
                {{ rec.description }}
              </p>
              <p class="mt-1 text-xs text-gray-500">
                {{ rec.action }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- All Alerts Modal -->
    <div
      v-if="showAllAlerts"
      class="fixed inset-0 z-50 flex items-center justify-center bg-gray-600 bg-opacity-50"
      @click="showAllAlerts = false"
    >
      <div
        class="mx-4 max-h-96 w-full max-w-4xl overflow-hidden rounded-lg bg-white shadow-xl"
        @click.stop
      >
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
          <h3 class="text-lg font-medium text-gray-900">
            All Critical Security Alerts
          </h3>
          <button class="text-gray-400 hover:text-gray-600" @click="showAllAlerts = false">
            <svg
              class="h-6 w-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
        <div class="max-h-80 overflow-y-auto px-6 py-4">
          <div class="space-y-3">
            <div
              v-for="alert in criticalAlerts"
              :key="alert.id"
              class="rounded-lg border border-red-200 p-3"
            >
              <p class="text-sm font-medium text-red-800">
                {{ alert.message }}
              </p>
              <p class="mt-1 text-xs text-red-600">
                {{ formatTime(alert.timestamp) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

// Reactive state
const loading = ref(false)
const timeRange = ref('24h')
const showAllAlerts = ref(false)

// Charts refs
const twoFactorChart = ref(null)
const failedLoginsChart = ref(null)
let twoFactorChartInstance = null
let failedLoginsChartInstance = null

// Data
const securityMetrics = ref([
  {
    label: 'Failed Logins',
    value: 0,
    change: 0,
    icon: 'ShieldExclamationIcon',
    iconColor: 'text-red-500',
    changeColor: 'text-red-600',
  },
  {
    label: '2FA Verifications',
    value: 0,
    change: 0,
    icon: 'KeyIcon',
    iconColor: 'text-blue-500',
    changeColor: 'text-green-600',
  },
  {
    label: 'Locked Accounts',
    value: 0,
    change: 0,
    icon: 'LockClosedIcon',
    iconColor: 'text-yellow-500',
    changeColor: 'text-red-600',
  },
  {
    label: 'Active Sessions',
    value: 0,
    change: 0,
    icon: 'UserGroupIcon',
    iconColor: 'text-green-500',
    changeColor: 'text-green-600',
  },
])

const recentEvents = ref([])
const criticalAlerts = ref([])
const topRiskIPs = ref([])
const twoFactorReport = ref({
  success_rate: 0,
  locked_accounts: 0,
  threats_detected: 0,
})
const recommendations = ref([])

// Auto-refresh interval
let refreshInterval = null

onMounted(async () => {
  await refreshData()
  // Refresh every 30 seconds
  refreshInterval = setInterval(refreshData, 30000)
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
  if (twoFactorChartInstance) {
    twoFactorChartInstance.destroy()
  }
  if (failedLoginsChartInstance) {
    failedLoginsChartInstance.destroy()
  }
})

async function refreshData() {
  loading.value = true
  try {
    const [metricsResponse, eventsResponse, reportsResponse] = await Promise.all([
      fetch(`/api/security/metrics?range=${timeRange.value}`),
      fetch(`/api/security/events?range=${timeRange.value}`),
      fetch(`/api/security/2fa-report?range=${timeRange.value}`),
    ])

    const metricsData = await metricsResponse.json()
    const eventsData = await eventsResponse.json()
    const reportsData = await reportsResponse.json()

    updateMetrics(metricsData)
    updateEvents(eventsData)
    updateReports(reportsData)
    updateCharts(metricsData, eventsData)
  } catch (error) {
    console.error('Failed to refresh security data:', error)
  } finally {
    loading.value = false
  }
}

function updateMetrics(data) {
  securityMetrics.value[0].value = data.failed_logins || 0
  securityMetrics.value[0].change = data.failed_logins_change || 0

  securityMetrics.value[1].value = data.two_factor_verifications || 0
  securityMetrics.value[1].change = data.two_factor_change || 0

  securityMetrics.value[2].value = data.locked_accounts || 0
  securityMetrics.value[2].change = data.locked_accounts_change || 0

  securityMetrics.value[3].value = data.active_sessions || 0
  securityMetrics.value[3].change = data.active_sessions_change || 0
}

function updateEvents(data) {
  recentEvents.value = data.recent_events || []
  criticalAlerts.value = data.critical_alerts || []
  topRiskIPs.value = data.top_risk_ips || []
}

function updateReports(data) {
  twoFactorReport.value = {
    success_rate: data.summary?.success_rate || 0,
    locked_accounts: data.summary?.locked_accounts || 0,
    threats_detected: data.threats?.length || 0,
  }
  recommendations.value = data.recommendations || []
}

function updateCharts(metricsData, eventsData) {
  // Update 2FA chart
  if (twoFactorChart.value) {
    if (twoFactorChartInstance) {
      twoFactorChartInstance.destroy()
    }

    const ctx = twoFactorChart.value.getContext('2d')
    twoFactorChartInstance = new Chart(ctx, {
      type: 'line',
      data: {
        labels: metricsData.chart_labels || [],
        datasets: [
          {
            label: 'Successful',
            data: metricsData.two_factor_success || [],
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            tension: 0.1,
          },
          {
            label: 'Failed',
            data: metricsData.two_factor_failed || [],
            borderColor: 'rgb(239, 68, 68)',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            tension: 0.1,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
          },
        },
      },
    })
  }

  // Update failed logins chart
  if (failedLoginsChart.value) {
    if (failedLoginsChartInstance) {
      failedLoginsChartInstance.destroy()
    }

    const ctx = failedLoginsChart.value.getContext('2d')
    failedLoginsChartInstance = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: metricsData.chart_labels || [],
        datasets: [
          {
            label: 'Failed Logins',
            data: metricsData.failed_logins_trend || [],
            backgroundColor: 'rgba(239, 68, 68, 0.8)',
            borderColor: 'rgb(239, 68, 68)',
            borderWidth: 1,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
          },
        },
      },
    })
  }
}

function formatNumber(value) {
  if (typeof value !== 'number') {return '0'}
  return new Intl.NumberFormat().format(value)
}

function formatTime(timestamp) {
  return new Date(timestamp).toLocaleString()
}

function getEventStatusColor(riskLevel) {
  const colors = {
    critical: 'bg-red-500',
    high: 'bg-orange-500',
    medium: 'bg-yellow-500',
    low: 'bg-green-500',
  }
  return colors[riskLevel] || 'bg-gray-500'
}

function getRiskBadgeColor(riskLevel) {
  const colors = {
    critical: 'bg-red-100 text-red-800',
    high: 'bg-orange-100 text-orange-800',
    medium: 'bg-yellow-100 text-yellow-800',
    low: 'bg-green-100 text-green-800',
  }
  return colors[riskLevel] || 'bg-gray-100 text-gray-800'
}

function getRecommendationBorder(type) {
  const borders = {
    critical: 'border-red-200 bg-red-50',
    warning: 'border-yellow-200 bg-yellow-50',
    info: 'border-blue-200 bg-blue-50',
  }
  return borders[type] || 'border-gray-200 bg-gray-50'
}

function getRecommendationIcon(type) {
  const icons = {
    critical: 'ExclamationTriangleIcon',
    warning: 'ExclamationCircleIcon',
    info: 'InformationCircleIcon',
  }
  return icons[type] || 'InformationCircleIcon'
}

function getRecommendationIconColor(type) {
  const colors = {
    critical: 'text-red-500',
    warning: 'text-yellow-500',
    info: 'text-blue-500',
  }
  return colors[type] || 'text-gray-500'
}

async function downloadReport() {
  try {
    const response = await fetch(`/api/security/report/download?range=${timeRange.value}`)
    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `security-report-${timeRange.value}-${new Date().toISOString().split('T')[0]}.pdf`
    document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    document.body.removeChild(a)
  } catch (error) {
    console.error('Failed to download report:', error)
  }
}

// Mock icon components (you would import these from @heroicons/vue)
const ShieldExclamationIcon = {
  template:
    '<svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016zM12 9v2m0 4h.01" /></svg>',
}
const KeyIcon = {
  template:
    '<svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>',
}
const LockClosedIcon = {
  template:
    '<svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 0h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>',
}
const UserGroupIcon = {
  template:
    '<svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>',
}
const ExclamationTriangleIcon = {
  template:
    '<svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>',
}
const ExclamationCircleIcon = {
  template:
    '<svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
}
const InformationCircleIcon = {
  template:
    '<svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
}
</script>

<style scoped>
.security-dashboard {
  padding: 1.5rem;
}

@media (max-width: 768px) {
  .security-dashboard {
    padding: 1rem;
  }
}
</style>
