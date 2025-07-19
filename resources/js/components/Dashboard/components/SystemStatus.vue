<template>
  <div>
    <!-- Overall Status -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center space-x-2">
        <div
          class="w-3 h-3 rounded-full"
          :class="getOverallStatusClass(status.overall)"
        ></div>
        <span class="text-sm font-medium text-gray-900 dark:text-white">
          System Status
        </span>
      </div>
      <span
        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
        :class="getOverallStatusBadgeClass(status.overall)"
      >
        {{ getOverallStatusLabel(status.overall) }}
      </span>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="animate-pulse">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <div class="w-2 h-2 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24"></div>
          </div>
          <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
        </div>
      </div>
    </div>

    <!-- Services List -->
    <div v-else class="space-y-3">
      <div
        v-for="service in status.services"
        :key="service.id"
        class="flex items-center justify-between"
      >
        <div class="flex items-center space-x-3">
          <div
            class="w-2 h-2 rounded-full"
            :class="getServiceStatusClass(service.status)"
          ></div>
          <span class="text-sm text-gray-700 dark:text-gray-300">
            {{ service.name }}
          </span>
          
          <!-- Additional Info -->
          <div v-if="service.responseTime" class="text-xs text-gray-500 dark:text-gray-400">
            {{ service.responseTime }}ms
          </div>
        </div>

        <div class="flex items-center space-x-2">
          <!-- Uptime -->
          <span v-if="service.uptime" class="text-xs text-gray-500 dark:text-gray-400">
            {{ service.uptime }}%
          </span>
          
          <!-- Status Badge -->
          <span
            class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium"
            :class="getServiceStatusBadgeClass(service.status)"
          >
            {{ getServiceStatusLabel(service.status) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Last Updated -->
    <div v-if="!loading && status.lastUpdated" class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
      <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
        Last updated {{ formatLastUpdated(status.lastUpdated) }}
      </p>
    </div>

    <!-- Actions -->
    <div v-if="!loading" class="mt-4 flex items-center justify-center space-x-3">
      <button
        @click="refreshStatus"
        class="inline-flex items-center px-2 py-1 border border-gray-300 dark:border-gray-600 shadow-sm text-xs leading-4 font-medium rounded text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors duration-150"
      >
        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
        </svg>
        Refresh
      </button>
      
      <button
        @click="viewDetails"
        class="inline-flex items-center px-2 py-1 text-xs leading-4 font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors duration-150"
      >
        View Details
        <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface SystemService {
  id: string
  name: string
  status: 'operational' | 'degraded' | 'down' | 'maintenance'
  uptime?: number
  responseTime?: number
  lastChecked?: string
}

interface SystemStatus {
  overall: 'healthy' | 'degraded' | 'down'
  services: SystemService[]
  lastUpdated?: string
}

interface Props {
  status: SystemStatus
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits<{
  refresh: []
  viewDetails: []
}>()

const getOverallStatusClass = (status: string) => {
  const classes = {
    'healthy': 'bg-green-400',
    'degraded': 'bg-yellow-400',
    'down': 'bg-red-400'
  }
  return classes[status] || classes.healthy
}

const getOverallStatusBadgeClass = (status: string) => {
  const classes = {
    'healthy': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    'degraded': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    'down': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
  }
  return classes[status] || classes.healthy
}

const getOverallStatusLabel = (status: string) => {
  const labels = {
    'healthy': 'All Systems Operational',
    'degraded': 'Some Issues',
    'down': 'Major Outage'
  }
  return labels[status] || 'Unknown'
}

const getServiceStatusClass = (status: string) => {
  const classes = {
    'operational': 'bg-green-400',
    'degraded': 'bg-yellow-400',
    'down': 'bg-red-400',
    'maintenance': 'bg-blue-400'
  }
  return classes[status] || classes.operational
}

const getServiceStatusBadgeClass = (status: string) => {
  const classes = {
    'operational': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    'degraded': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    'down': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    'maintenance': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
  }
  return classes[status] || classes.operational
}

const getServiceStatusLabel = (status: string) => {
  const labels = {
    'operational': 'Up',
    'degraded': 'Issues',
    'down': 'Down',
    'maintenance': 'Maintenance'
  }
  return labels[status] || status
}

const formatLastUpdated = (timestamp: string) => {
  const date = new Date(timestamp)
  const now = new Date()
  const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60))
  
  if (diffInMinutes < 1) {
    return 'just now'
  } else if (diffInMinutes < 60) {
    return `${diffInMinutes} minute${diffInMinutes === 1 ? '' : 's'} ago`
  } else {
    const hours = Math.floor(diffInMinutes / 60)
    return `${hours} hour${hours === 1 ? '' : 's'} ago`
  }
}

const refreshStatus = () => {
  emit('refresh')
}

const viewDetails = () => {
  emit('viewDetails')
}
</script>