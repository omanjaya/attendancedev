<template>
  <div class="space-y-6">
    <!-- Security Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <!-- Active Sessions -->
      <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <UsersIcon class="h-6 w-6 text-blue-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                  Active Sessions
                </dt>
                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                  {{ metrics.active_sessions || 0 }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 px-5 py-3">
          <div class="text-sm">
            <span class="text-green-600 dark:text-green-400">
              {{ metrics.sessions_today || 0 }} today
            </span>
          </div>
        </div>
      </div>

      <!-- 2FA Enabled Users -->
      <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ShieldCheckIcon class="h-6 w-6 text-green-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                  2FA Enabled
                </dt>
                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                  {{ metrics.two_fa_enabled_users || 0 }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 px-5 py-3">
          <div class="text-sm">
            <span :class="metrics.two_fa_percentage >= 80 ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400'">
              {{ metrics.two_fa_percentage || 0 }}% coverage
            </span>
          </div>
        </div>
      </div>

      <!-- Security Events -->
      <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <ExclamationTriangleIcon class="h-6 w-6 text-yellow-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                  Security Events
                </dt>
                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                  {{ metrics.security_events_today || 0 }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 px-5 py-3">
          <div class="text-sm">
            <span :class="metrics.high_risk_events > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
              {{ metrics.high_risk_events || 0 }} high risk
            </span>
          </div>
        </div>
      </div>

      <!-- Trusted Devices -->
      <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <DevicePhoneMobileIcon class="h-6 w-6 text-purple-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                  Trusted Devices
                </dt>
                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                  {{ metrics.trusted_devices || 0 }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 px-5 py-3">
          <div class="text-sm">
            <span class="text-blue-600 dark:text-blue-400">
              {{ metrics.new_devices_today || 0 }} new today
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Security Alerts & Recent Events -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Security Alerts -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
              Security Alerts
            </h3>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
              {{ alerts.filter(alert => !alert.acknowledged_at).length }} active
            </span>
          </div>

          <div class="space-y-3">
            <div v-if="loading.alerts" class="text-center py-4">
              <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600 mx-auto"></div>
            </div>

            <div v-else-if="alerts.length === 0" class="text-center py-4">
              <ShieldCheckIcon class="h-8 w-8 text-green-400 mx-auto mb-2" />
              <p class="text-sm text-gray-500 dark:text-gray-400">No security alerts</p>
            </div>

            <div v-else class="max-h-60 overflow-y-auto">
              <div
                v-for="alert in alerts.slice(0, 5)"
                :key="alert.id"
                class="flex items-start space-x-3 p-3 rounded-lg border"
                :class="[
                  alert.acknowledged_at 
                    ? 'border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700' 
                    : 'border-red-200 dark:border-red-600 bg-red-50 dark:bg-red-900/20'
                ]"
              >
                <div class="flex-shrink-0">
                  <ExclamationTriangleIcon 
                    class="h-5 w-5"
                    :class="alert.severity === 'high' ? 'text-red-500' : 'text-yellow-500'"
                  />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ alert.title }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ alert.description }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ formatDate(alert.created_at) }}
                  </p>
                </div>
                <button
                  v-if="!alert.acknowledged_at"
                  @click="acknowledgeAlert(alert)"
                  class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-200"
                >
                  Acknowledge
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Security Events -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            Recent Security Events
          </h3>

          <div class="space-y-3">
            <div v-if="loading.events" class="text-center py-4">
              <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600 mx-auto"></div>
            </div>

            <div v-else-if="events.length === 0" class="text-center py-4">
              <ClockIcon class="h-8 w-8 text-gray-400 mx-auto mb-2" />
              <p class="text-sm text-gray-500 dark:text-gray-400">No recent events</p>
            </div>

            <div v-else class="max-h-60 overflow-y-auto">
              <div
                v-for="event in events.slice(0, 8)"
                :key="event.id"
                class="flex items-start space-x-3 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded"
              >
                <div class="flex-shrink-0">
                  <component
                    :is="getEventIcon(event.event_type)"
                    class="h-4 w-4"
                    :class="getEventIconColor(event.risk_level)"
                  />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm text-gray-900 dark:text-white">
                    {{ event.description }}
                  </p>
                  <div class="flex items-center space-x-2 mt-1">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      {{ event.user?.name || 'System' }}
                    </p>
                    <span class="text-gray-300 dark:text-gray-600">•</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      {{ formatDate(event.created_at) }}
                    </p>
                  </div>
                </div>
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                  :class="getRiskLevelClasses(event.risk_level)"
                >
                  {{ event.risk_level }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 2FA Adoption Chart -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
          2FA Adoption Rate
        </h3>
        
        <div class="space-y-4">
          <!-- Progress Bar -->
          <div>
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-300 mb-2">
              <span>Users with 2FA enabled</span>
              <span>{{ metrics.two_fa_enabled_users || 0 }} / {{ metrics.total_users || 0 }}</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div
                class="h-2 rounded-full transition-all duration-300"
                :class="metrics.two_fa_percentage >= 80 ? 'bg-green-500' : metrics.two_fa_percentage >= 60 ? 'bg-yellow-500' : 'bg-red-500'"
                :style="{ width: `${metrics.two_fa_percentage || 0}%` }"
              ></div>
            </div>
          </div>

          <!-- Recommendation -->
          <div v-if="metrics.two_fa_percentage < 80" class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md">
            <div class="flex">
              <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400 flex-shrink-0" />
              <div class="ml-3">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                  <strong>Security Recommendation:</strong> Enable 2FA for all users to improve security. 
                  Current adoption rate is {{ metrics.two_fa_percentage || 0 }}%.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { formatDistanceToNow } from 'date-fns'

// Icons
const UsersIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
    </svg>
  `
}

const ShieldCheckIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
    </svg>
  `
}

const ExclamationTriangleIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
    </svg>
  `
}

const DevicePhoneMobileIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a1 1 0 001-1V4a1 1 0 00-1-1H8a1 1 0 00-1 1v16a1 1 0 001 1z"/>
    </svg>
  `
}

const ClockIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
  `
}

const UserPlusIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
    </svg>
  `
}

const LockClosedIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a1 1 0 001-1v-6a1 1 0 00-1-1H6a1 1 0 00-1 1v6a1 1 0 001 1zM12 9V7a3 3 0 00-6 0v2"/>
    </svg>
  `
}

// Reactive data
const metrics = ref({})
const alerts = ref([])
const events = ref([])
const loading = ref({
  metrics: true,
  alerts: true,
  events: true
})

// Methods
const loadSecurityMetrics = async () => {
  try {
    loading.value.metrics = true
    const response = await fetch('/api/security/metrics')
    if (response.ok) {
      metrics.value = await response.json()
    }
  } catch (error) {
    console.error('Failed to load security metrics:', error)
  } finally {
    loading.value.metrics = false
  }
}

const loadSecurityAlerts = async () => {
  try {
    loading.value.alerts = true
    const response = await fetch('/api/security/alerts')
    if (response.ok) {
      alerts.value = await response.json()
    }
  } catch (error) {
    console.error('Failed to load security alerts:', error)
  } finally {
    loading.value.alerts = false
  }
}

const loadSecurityEvents = async () => {
  try {
    loading.value.events = true
    const response = await fetch('/api/security/events')
    if (response.ok) {
      events.value = await response.json()
    }
  } catch (error) {
    console.error('Failed to load security events:', error)
  } finally {
    loading.value.events = false
  }
}

const acknowledgeAlert = async (alert) => {
  try {
    const response = await fetch(`/api/security/alerts/${alert.id}/acknowledge`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      }
    })

    if (response.ok) {
      alert.acknowledged_at = new Date()
      // Reload metrics to update count
      loadSecurityMetrics()
    }
  } catch (error) {
    console.error('Failed to acknowledge alert:', error)
  }
}

const getEventIcon = (eventType) => {
  if (eventType?.includes('login') || eventType?.includes('session')) {
    return UserPlusIcon
  } else if (eventType?.includes('2fa') || eventType?.includes('auth')) {
    return LockClosedIcon
  } else if (eventType?.includes('device')) {
    return DevicePhoneMobileIcon
  }
  return ShieldCheckIcon
}

const getEventIconColor = (riskLevel) => {
  switch (riskLevel) {
    case 'high':
      return 'text-red-500'
    case 'medium':
      return 'text-yellow-500'
    case 'low':
      return 'text-green-500'
    default:
      return 'text-gray-400'
  }
}

const getRiskLevelClasses = (riskLevel) => {
  switch (riskLevel) {
    case 'high':
      return 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'
    case 'medium':
      return 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200'
    case 'low':
      return 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'
    default:
      return 'bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200'
  }
}

const formatDate = (dateString) => {
  return formatDistanceToNow(new Date(dateString), { addSuffix: true })
}

// Auto-refresh data
let refreshInterval

// Lifecycle
onMounted(() => {
  loadSecurityMetrics()
  loadSecurityAlerts()
  loadSecurityEvents()

  // Auto-refresh every 30 seconds
  refreshInterval = setInterval(() => {
    loadSecurityMetrics()
    loadSecurityAlerts()
    loadSecurityEvents()
  }, 30000)
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})
</script>