<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow duration-200">
    <div class="flex items-center justify-between">
      <div class="flex-1 min-w-0">
        <!-- Loading State -->
        <div v-if="loading" class="animate-pulse">
          <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-24 mb-2"></div>
          <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-16 mb-2"></div>
          <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-32"></div>
        </div>
        
        <!-- Content -->
        <div v-else>
          <p class="text-sm font-medium text-gray-600 dark:text-gray-400 truncate">
            {{ title }}
          </p>
          <p class="text-3xl font-bold text-gray-900 dark:text-white">
            {{ value }}
          </p>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ subtitle }}
          </p>
          
          <!-- Trend Indicator -->
          <div v-if="trend && !loading" class="flex items-center mt-2">
            <svg
              class="w-4 h-4 mr-1"
              :class="trend.isUp ? 'text-green-500' : 'text-red-500'"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                v-if="trend.isUp"
                fill-rule="evenodd"
                d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L10 4.414 4.707 9.707a1 1 0 01-1.414 0z"
                clip-rule="evenodd"
              />
              <path
                v-else
                fill-rule="evenodd"
                d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L10 15.586l5.293-5.293a1 1 0 011.414 0z"
                clip-rule="evenodd"
              />
            </svg>
            <span
              class="text-sm font-medium"
              :class="trend.isUp ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
            >
              {{ Math.abs(trend.value) }}%
            </span>
            <span class="text-sm text-gray-500 dark:text-gray-400 ml-1">
              from last period
            </span>
          </div>
        </div>
      </div>
      
      <!-- Icon -->
      <div class="ml-4 flex-shrink-0">
        <div
          class="w-12 h-12 rounded-lg flex items-center justify-center"
          :class="getIconBackgroundClass(color)"
        >
          <div v-if="loading" class="animate-pulse">
            <div class="w-6 h-6 bg-gray-300 dark:bg-gray-600 rounded"></div>
          </div>
          <svg
            v-else
            class="w-6 h-6"
            :class="getIconTextClass(color)"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              :d="getIconPath(icon)"
            />
          </svg>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  title: string
  value: string | number
  subtitle: string
  icon: string
  color: 'success' | 'info' | 'warning' | 'danger' | 'primary'
  trend?: {
    value: number
    isUp: boolean
  }
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const getIconBackgroundClass = (color: string) => {
  const classes = {
    success: 'bg-green-100 dark:bg-green-900/30',
    info: 'bg-blue-100 dark:bg-blue-900/30',
    warning: 'bg-yellow-100 dark:bg-yellow-900/30',
    danger: 'bg-red-100 dark:bg-red-900/30',
    primary: 'bg-emerald-100 dark:bg-emerald-900/30'
  }
  return classes[color] || classes.primary
}

const getIconTextClass = (color: string) => {
  const classes = {
    success: 'text-green-600 dark:text-green-400',
    info: 'text-blue-600 dark:text-blue-400',
    warning: 'text-yellow-600 dark:text-yellow-400',
    danger: 'text-red-600 dark:text-red-400',
    primary: 'text-emerald-600 dark:text-emerald-400'
  }
  return classes[color] || classes.primary
}

const getIconPath = (icon: string) => {
  const icons = {
    'check-circle': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    'chart-bar': 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    'calendar': 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    'shield-check': 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    'users': 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
    'clock': 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
  }
  return icons[icon] || icons['check-circle']
}
</script>