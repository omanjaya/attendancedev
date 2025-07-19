<template>
  <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
    <!-- Loading State -->
    <div v-if="loading" class="animate-pulse">
      <div class="flex items-center space-x-3">
        <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg"></div>
        <div class="flex-1 min-w-0">
          <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-2"></div>
          <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
        </div>
        <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-16"></div>
      </div>
    </div>

    <!-- Schedule Content -->
    <div v-else class="flex items-center space-x-4">
      <!-- Time Block -->
      <div class="flex-shrink-0">
        <div
          class="w-12 h-12 rounded-lg flex flex-col items-center justify-center text-xs font-medium"
          :class="getTimeBlockClass(item.status)"
        >
          <span class="leading-none">{{ formatTime(item.start_time) }}</span>
          <span class="leading-none opacity-75">{{ formatTime(item.end_time) }}</span>
        </div>
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between">
          <div class="flex-1 min-w-0">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
              {{ item.title }}
            </h4>
            
            <div class="mt-1 flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
              <!-- Location -->
              <span v-if="item.location" class="flex items-center">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                </svg>
                {{ item.location }}
              </span>
              
              <!-- Participants -->
              <span v-if="item.participants" class="flex items-center">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/>
                </svg>
                {{ item.participants }} participants
              </span>

              <!-- Type -->
              <span v-if="item.type" class="flex items-center">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                </svg>
                {{ item.type }}
              </span>
            </div>

            <!-- Description -->
            <p v-if="item.description" class="mt-1 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
              {{ item.description }}
            </p>
          </div>

          <!-- Status Badge -->
          <div class="ml-4 flex-shrink-0">
            <span
              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
              :class="getStatusBadgeClass(item.status)"
            >
              <div
                class="w-1.5 h-1.5 rounded-full mr-1"
                :class="getStatusDotClass(item.status)"
              ></div>
              {{ getStatusLabel(item.status) }}
            </span>
          </div>
        </div>

        <!-- Progress Bar (for ongoing events) -->
        <div v-if="item.status === 'ongoing' && item.progress" class="mt-3">
          <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
            <span>Progress</span>
            <span>{{ Math.round(item.progress) }}%</span>
          </div>
          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
            <div
              class="bg-blue-600 h-1.5 rounded-full transition-all duration-300"
              :style="{ width: `${item.progress}%` }"
            ></div>
          </div>
        </div>

        <!-- Actions -->
        <div v-if="item.actions && item.actions.length > 0" class="mt-3 flex items-center space-x-2">
          <button
            v-for="action in item.actions"
            :key="action.id"
            @click="handleAction(action)"
            class="inline-flex items-center px-2 py-1 border border-gray-300 dark:border-gray-600 shadow-sm text-xs leading-4 font-medium rounded text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors duration-150"
          >
            <svg v-if="action.icon" class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" :d="getActionIcon(action.icon)" clip-rule="evenodd"/>
            </svg>
            {{ action.label }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface ScheduleAction {
  id: string
  label: string
  icon?: string
  type: 'primary' | 'secondary' | 'danger'
}

interface ScheduleItem {
  id: string
  title: string
  description?: string
  start_time: string
  end_time: string
  location?: string
  participants?: number
  type?: string
  status: 'upcoming' | 'ongoing' | 'completed' | 'cancelled'
  progress?: number
  actions?: ScheduleAction[]
}

interface Props {
  item: ScheduleItem
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const emit = defineEmits<{
  action: [action: ScheduleAction, item: ScheduleItem]
}>()

const getTimeBlockClass = (status: string) => {
  const classes = {
    'upcoming': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    'ongoing': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    'completed': 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    'cancelled': 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'
  }
  return classes[status] || classes.upcoming
}

const getStatusBadgeClass = (status: string) => {
  const classes = {
    'upcoming': 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800',
    'ongoing': 'bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800',
    'completed': 'bg-gray-50 text-gray-600 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600',
    'cancelled': 'bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800'
  }
  return classes[status] || classes.upcoming
}

const getStatusDotClass = (status: string) => {
  const classes = {
    'upcoming': 'bg-blue-400',
    'ongoing': 'bg-green-400',
    'completed': 'bg-gray-400',
    'cancelled': 'bg-red-400'
  }
  return classes[status] || classes.upcoming
}

const getStatusLabel = (status: string) => {
  const labels = {
    'upcoming': 'Upcoming',
    'ongoing': 'In Progress',
    'completed': 'Completed',
    'cancelled': 'Cancelled'
  }
  return labels[status] || status
}

const formatTime = (time: string) => {
  return new Date(`2000-01-01T${time}`).toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: false
  }).slice(0, 5)
}

const getActionIcon = (icon: string) => {
  const icons = {
    'play': 'M8 5v14l11-7z',
    'stop': 'M5 5a2 2 0 012-2h6a2 2 0 012 2v10a2 2 0 01-2 2H7a2 2 0 01-2-2V5z',
    'edit': 'M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z',
    'cancel': 'M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z',
    'view': 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'
  }
  return icons[icon] || icons.view
}

const handleAction = (action: ScheduleAction) => {
  emit('action', action, props.item)
}
</script>

<style scoped>
.line-clamp-2 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}
</style>