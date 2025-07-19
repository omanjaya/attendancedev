<template>
  <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
    <!-- Loading State -->
    <div v-if="loading" class="animate-pulse">
      <div class="flex items-center space-x-3">
        <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
        <div class="flex-1 min-w-0">
          <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-2"></div>
          <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
        </div>
        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
      </div>
    </div>

    <!-- Activity Content -->
    <div v-else class="flex items-start space-x-3">
      <!-- Avatar/Icon -->
      <div class="flex-shrink-0">
        <div
          v-if="activity.user?.avatar"
          class="w-10 h-10 rounded-full overflow-hidden border-2 border-white dark:border-gray-800 shadow-sm"
        >
          <img
            :src="activity.user.avatar"
            :alt="activity.user.name"
            class="w-full h-full object-cover"
          />
        </div>
        <div
          v-else
          class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-medium shadow-sm"
          :class="getAvatarBackgroundClass(activity.type)"
        >
          <svg
            v-if="getActivityIcon(activity.type)"
            class="w-5 h-5"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path
              fill-rule="evenodd"
              :d="getActivityIcon(activity.type)"
              clip-rule="evenodd"
            />
          </svg>
          <span v-else>
            {{ getInitials(activity.user?.name || 'U') }}
          </span>
        </div>
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between">
          <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-900 dark:text-white">
              <span class="font-medium">{{ activity.user?.name || 'Unknown User' }}</span>
              <span class="text-gray-600 dark:text-gray-400">{{ activity.description }}</span>
            </p>
            
            <!-- Additional Info -->
            <div v-if="activity.details" class="mt-1">
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ activity.details }}
              </p>
            </div>
            
            <!-- Location/Device Info -->
            <div v-if="activity.location || activity.device" class="mt-1 flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
              <span v-if="activity.location" class="flex items-center">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                </svg>
                {{ activity.location }}
              </span>
              <span v-if="activity.device" class="flex items-center">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v8a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 1v6h10V5H5z" clip-rule="evenodd"/>
                  <path d="M6 17h8v-1H6v1z"/>
                </svg>
                {{ activity.device }}
              </span>
            </div>
          </div>

          <!-- Status Badge -->
          <div v-if="activity.status" class="ml-2 flex-shrink-0">
            <span
              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
              :class="getStatusBadgeClass(activity.status)"
            >
              {{ activity.status }}
            </span>
          </div>
        </div>

        <!-- Timestamp -->
        <div class="mt-2 flex items-center justify-between">
          <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ formatTime(activity.timestamp) }}
          </p>
          
          <!-- Confidence Score (for face recognition activities) -->
          <div v-if="activity.confidence" class="flex items-center space-x-1">
            <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span class="text-xs text-gray-500 dark:text-gray-400">
              {{ Math.round(activity.confidence * 100) }}%
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Activity {
  id: string
  type: 'check-in' | 'check-out' | 'leave-request' | 'leave-approved' | 'leave-rejected' | 'system'
  user?: {
    name: string
    avatar?: string
  }
  description: string
  details?: string
  location?: string
  device?: string
  status?: string
  confidence?: number
  timestamp: string
}

interface Props {
  activity: Activity
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const getAvatarBackgroundClass = (type: string) => {
  const classes = {
    'check-in': 'bg-green-500',
    'check-out': 'bg-blue-500',
    'leave-request': 'bg-yellow-500',
    'leave-approved': 'bg-emerald-500',
    'leave-rejected': 'bg-red-500',
    'system': 'bg-gray-500'
  }
  return classes[type] || 'bg-gray-500'
}

const getActivityIcon = (type: string) => {
  const icons = {
    'check-in': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    'check-out': 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
    'leave-request': 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    'leave-approved': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    'leave-rejected': 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
    'system': 'M13 10V3L4 14h7v7l9-11h-7z'
  }
  return icons[type] || null
}

const getInitials = (name: string) => {
  return name
    .split(' ')
    .map(word => word.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const getStatusBadgeClass = (status: string) => {
  const classes = {
    'success': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    'pending': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    'failed': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    'processing': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
  }
  return classes[status.toLowerCase()] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
}

const formatTime = (timestamp: string) => {
  const date = new Date(timestamp)
  const now = new Date()
  const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60))
  
  if (diffInMinutes < 1) {
    return 'Just now'
  } else if (diffInMinutes < 60) {
    return `${diffInMinutes}m ago`
  } else if (diffInMinutes < 1440) {
    const hours = Math.floor(diffInMinutes / 60)
    return `${hours}h ago`
  } else {
    const days = Math.floor(diffInMinutes / 1440)
    return `${days}d ago`
  }
}
</script>