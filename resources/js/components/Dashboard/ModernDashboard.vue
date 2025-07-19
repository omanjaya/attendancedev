<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
    <!-- Header Section -->
    <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
      <div class="px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
              Dashboard
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              {{ getGreeting() }}, {{ user?.name }}
            </p>
          </div>
          <div class="flex items-center space-x-3">
            <button
              @click="refreshData"
              :disabled="isLoading"
              class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 disabled:opacity-50 transition-colors duration-200"
            >
              <svg
                class="w-4 h-4 mr-2"
                :class="{ 'animate-spin': isLoading }"
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
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="px-4 sm:px-6 lg:px-8 py-8">
      <!-- Stats Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <StatCard
          v-for="(stat, index) in stats"
          :key="index"
          :title="stat.title"
          :value="stat.value"
          :subtitle="stat.subtitle"
          :icon="stat.icon"
          :color="stat.color"
          :trend="stat.trend"
          :loading="isLoading"
        />
      </div>

      <!-- Main Grid Layout -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column - Charts and Analytics -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Attendance Chart -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                  Attendance Trends
                </h3>
                <div class="flex items-center space-x-2">
                  <select
                    v-model="chartPeriod"
                    @change="updateChart"
                    class="text-sm border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:text-gray-300"
                  >
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="quarter">This Quarter</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="p-6">
              <AttendanceChart
                :data="chartData"
                :loading="chartLoading"
                :period="chartPeriod"
              />
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Quick Actions
              </h3>
            </div>
            <div class="p-6">
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <QuickAction
                  v-for="action in quickActions"
                  :key="action.id"
                  :icon="action.icon"
                  :label="action.label"
                  :href="action.href"
                  :color="action.color"
                  @click="action.onClick"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Activity and Recent Data -->
        <div class="space-y-6">
          <!-- Recent Activity -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                  Recent Activity
                </h3>
                <button
                  @click="showAllActivity"
                  class="text-sm text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300"
                >
                  View All
                </button>
              </div>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
              <ActivityItem
                v-for="activity in recentActivities"
                :key="activity.id"
                :activity="activity"
                :loading="isLoading"
              />
            </div>
          </div>

          <!-- Today's Schedule -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Today's Schedule
              </h3>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
              <ScheduleItem
                v-for="item in todaySchedule"
                :key="item.id"
                :item="item"
                :loading="isLoading"
              />
            </div>
          </div>

          <!-- System Status -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                System Status
              </h3>
            </div>
            <div class="p-6">
              <SystemStatus :status="systemStatus" :loading="isLoading" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast Notifications -->
    <div
      v-if="notifications.length > 0"
      class="fixed top-4 right-4 z-50 space-y-2"
    >
      <Transition
        v-for="notification in notifications"
        :key="notification.id"
        name="slide-fade"
        appear
      >
        <div
          class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-4 min-w-80"
        >
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <div
                class="w-6 h-6 rounded-full flex items-center justify-center"
                :class="getNotificationIconClass(notification.type)"
              >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fill-rule="evenodd"
                    :d="getNotificationIconPath(notification.type)"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
            </div>
            <div class="ml-3 flex-1">
              <p class="text-sm font-medium text-gray-900 dark:text-white">
                {{ notification.title }}
              </p>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ notification.message }}
              </p>
            </div>
            <button
              @click="removeNotification(notification.id)"
              class="ml-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                />
              </svg>
            </button>
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, onUnmounted, computed } from 'vue'
import StatCard from './components/StatCard.vue'
import AttendanceChart from './components/AttendanceChart.vue'
import QuickAction from './components/QuickAction.vue'
import ActivityItem from './components/ActivityItem.vue'
import ScheduleItem from './components/ScheduleItem.vue'
import SystemStatus from './components/SystemStatus.vue'

// Props
interface Props {
  user: {
    name: string
    role: string
    avatar?: string
  }
  initialData?: any
}

const props = defineProps<Props>()

// Reactive state
const isLoading = ref(false)
const chartLoading = ref(false)
const chartPeriod = ref('week')
const notifications = ref<any[]>([])

const stats = ref([
  {
    title: 'Present Today',
    value: '0',
    subtitle: 'of 0 employees',
    icon: 'check-circle',
    color: 'success',
    trend: { value: 0, isUp: true }
  },
  {
    title: 'Attendance Rate',
    value: '0%',
    subtitle: 'This month',
    icon: 'chart-bar',
    color: 'info',
    trend: { value: 0, isUp: true }
  },
  {
    title: 'Leave Requests',
    value: '0',
    subtitle: 'Pending approval',
    icon: 'calendar',
    color: 'warning',
    trend: { value: 0, isUp: false }
  },
  {
    title: 'System Health',
    value: '100%',
    subtitle: 'All systems operational',
    icon: 'shield-check',
    color: 'success',
    trend: { value: 0, isUp: true }
  }
])

const chartData = ref({
  labels: [],
  datasets: []
})

const recentActivities = ref([])
const todaySchedule = ref([])
const systemStatus = ref({
  overall: 'healthy',
  services: []
})

const quickActions = ref([
  {
    id: 'check-in',
    icon: 'finger-print',
    label: 'Check In',
    href: '/attendance/check-in',
    color: 'emerald',
    onClick: () => handleQuickAction('check-in')
  },
  {
    id: 'employees',
    icon: 'users',
    label: 'Employees',
    href: '/employees',
    color: 'blue',
    onClick: () => handleQuickAction('employees')
  },
  {
    id: 'reports',
    icon: 'chart-bar',
    label: 'Reports',
    href: '/reports',
    color: 'purple',
    onClick: () => handleQuickAction('reports')
  },
  {
    id: 'settings',
    icon: 'cog',
    label: 'Settings',
    href: '/settings',
    color: 'gray',
    onClick: () => handleQuickAction('settings')
  }
])

// Computed
const getGreeting = () => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Good morning'
  if (hour < 17) return 'Good afternoon'
  return 'Good evening'
}

// Methods
const loadDashboardData = async () => {
  isLoading.value = true
  try {
    const response = await fetch('/api/dashboard/data')
    const data = await response.json()
    
    // Update stats
    if (data.stats) {
      stats.value = [
        {
          ...stats.value[0],
          value: data.stats.present_today.toString(),
          subtitle: `of ${data.stats.total_employees} employees`,
          trend: { value: data.stats.present_today_change || 0, isUp: (data.stats.present_today_change || 0) >= 0 }
        },
        {
          ...stats.value[1],
          value: `${data.stats.attendance_rate || 0}%`,
          trend: { value: data.stats.attendance_rate_change || 0, isUp: (data.stats.attendance_rate_change || 0) >= 0 }
        },
        {
          ...stats.value[2],
          value: data.stats.pending_leaves.toString(),
          trend: { value: data.stats.pending_leaves_change || 0, isUp: (data.stats.pending_leaves_change || 0) <= 0 }
        },
        {
          ...stats.value[3],
          value: `${data.stats.system_health || 100}%`,
          trend: { value: 0, isUp: true }
        }
      ]
    }

    // Update other data
    recentActivities.value = data.activities || []
    todaySchedule.value = data.schedule || []
    systemStatus.value = data.system_status || systemStatus.value
    
    showNotification('success', 'Data Updated', 'Dashboard data refreshed successfully')
  } catch (error) {
    console.error('Failed to load dashboard data:', error)
    showNotification('error', 'Update Failed', 'Failed to refresh dashboard data')
  } finally {
    isLoading.value = false
  }
}

const updateChart = async () => {
  chartLoading.value = true
  try {
    const response = await fetch(`/api/dashboard/chart-data?period=${chartPeriod.value}`)
    const data = await response.json()
    chartData.value = data
  } catch (error) {
    console.error('Failed to load chart data:', error)
  } finally {
    chartLoading.value = false
  }
}

const refreshData = () => {
  loadDashboardData()
  updateChart()
}

const handleQuickAction = (actionId: string) => {
  console.log(`Quick action: ${actionId}`)
  // Handle quick action logic here
}

const showAllActivity = () => {
  window.location.href = '/activity'
}

const showNotification = (type: string, title: string, message: string) => {
  const notification = {
    id: Date.now(),
    type,
    title,
    message
  }
  notifications.value.push(notification)
  
  // Auto remove after 5 seconds
  setTimeout(() => {
    removeNotification(notification.id)
  }, 5000)
}

const removeNotification = (id: number) => {
  const index = notifications.value.findIndex(n => n.id === id)
  if (index > -1) {
    notifications.value.splice(index, 1)
  }
}

const getNotificationIconClass = (type: string) => {
  const classes = {
    success: 'bg-green-100 text-green-600',
    error: 'bg-red-100 text-red-600',
    warning: 'bg-yellow-100 text-yellow-600',
    info: 'bg-blue-100 text-blue-600'
  }
  return classes[type] || classes.info
}

const getNotificationIconPath = (type: string) => {
  const paths = {
    success: 'M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z',
    error: 'M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z',
    warning: 'M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z',
    info: 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z'
  }
  return paths[type] || paths.info
}

// Lifecycle
onMounted(() => {
  loadDashboardData()
  updateChart()
  
  // Set up auto-refresh every 5 minutes
  const interval = setInterval(refreshData, 5 * 60 * 1000)
  
  onUnmounted(() => {
    clearInterval(interval)
  })
})
</script>

<style scoped>
.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
  transition: all 0.3s cubic-bezier(1.0, 0.5, 0.8, 1.0);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateX(20px);
  opacity: 0;
}
</style>