<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white dark:bg-gray-800 rounded-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-600">
        <div>
          <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
            Konflik Jadwal Terdeteksi
          </h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            {{ conflicts.length }} konflik perlu diselesaikan
          </p>
        </div>
        <button
          @click="$emit('close')"
          class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <!-- Conflict Summary -->
      <div class="p-6 bg-red-50 dark:bg-red-900/20 border-b border-gray-200 dark:border-gray-600">
        <div class="flex items-start space-x-4">
          <div class="flex-shrink-0">
            <ExclamationTriangleIcon class="w-8 h-8 text-red-600" />
          </div>
          <div class="flex-1">
            <h4 class="text-lg font-medium text-red-900 dark:text-red-200 mb-2">
              Ringkasan Konflik
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-red-200 dark:border-red-700">
                <div class="text-2xl font-bold text-red-600">{{ conflicts.length }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Total Konflik</div>
              </div>
              <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-red-200 dark:border-red-700">
                <div class="text-2xl font-bold text-red-600">{{ uniqueTeachers.length }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Guru Terlibat</div>
              </div>
              <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-red-200 dark:border-red-700">
                <div class="text-2xl font-bold text-red-600">{{ affectedSlots.length }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Slot Terpengaruh</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Conflict List -->
      <div class="overflow-y-auto max-h-96 p-6">
        <div class="space-y-6">
          <div
            v-for="(conflict, index) in conflicts"
            :key="index"
            class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600"
          >
            <!-- Conflict Header -->
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center">
                  <span class="text-white font-bold text-sm">{{ conflict.teacher_code }}</span>
                </div>
                <div>
                  <h5 class="font-medium text-gray-900 dark:text-gray-100">
                    {{ conflict.teacher }}
                  </h5>
                  <p class="text-sm text-gray-600 dark:text-gray-400">
                    Mengajar di {{ conflict.classes.length }} kelas bersamaan
                  </p>
                </div>
              </div>
              <div class="flex space-x-2">
                <button
                  @click="autoResolveConflict(conflict)"
                  class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
                >
                  Auto Resolve
                </button>
                <button
                  @click="manualResolveConflict(conflict)"
                  class="px-3 py-1 text-xs bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors"
                >
                  Manual
                </button>
              </div>
            </div>

            <!-- Conflict Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div
                v-for="(classData, classIndex) in conflict.classes"
                :key="classIndex"
                class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-600"
              >
                <div class="flex items-center justify-between mb-2">
                  <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Kelas {{ getClassName(classData.class_id) }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ getTimeSlotName(classData.time_slot_id) }}
                  </div>
                </div>
                
                <div class="text-sm text-gray-600 dark:text-gray-400">
                  {{ classData.subject_name }}
                </div>

                <!-- Resolution Actions -->
                <div class="mt-3 flex space-x-2">
                  <button
                    @click="keepThisSchedule(classData)"
                    class="flex-1 px-2 py-1 text-xs bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded hover:bg-green-100 dark:hover:bg-green-900/50"
                  >
                    Pertahankan
                  </button>
                  <button
                    @click="removeThisSchedule(classData)"
                    class="flex-1 px-2 py-1 text-xs bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded hover:bg-red-100 dark:hover:bg-red-900/50"
                  >
                    Hapus
                  </button>
                  <button
                    @click="rescheduleThis(classData)"
                    class="flex-1 px-2 py-1 text-xs bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded hover:bg-blue-100 dark:hover:bg-blue-900/50"
                  >
                    Jadwal Ulang
                  </button>
                </div>
              </div>
            </div>

            <!-- Suggested Solutions -->
            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-700">
              <h6 class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-2">
                Saran Penyelesaian:
              </h6>
              <div class="space-y-2">
                <div 
                  v-for="suggestion in getSuggestions(conflict)"
                  :key="suggestion.id"
                  class="flex items-center justify-between p-2 bg-white dark:bg-gray-800 rounded border border-blue-200 dark:border-blue-600"
                >
                  <div class="text-sm text-gray-700 dark:text-gray-300">
                    {{ suggestion.description }}
                  </div>
                  <button
                    @click="applySuggestion(suggestion, conflict)"
                    class="px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700"
                  >
                    Terapkan
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer Actions -->
      <div class="p-6 border-t border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-600 dark:text-gray-400">
            <span class="font-medium">Tips:</span> Gunakan Auto Resolve untuk solusi otomatis, atau Manual untuk kontrol penuh
          </div>
          <div class="flex space-x-3">
            <button
              @click="resolveAllConflicts"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              Resolve Semua
            </button>
            <button
              @click="$emit('close')"
              class="px-4 py-2 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { 
  XMarkIcon, 
  ExclamationTriangleIcon 
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  conflicts: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['close', 'resolve'])

// Computed
const uniqueTeachers = computed(() => {
  const teachers = new Set()
  props.conflicts.forEach(conflict => {
    teachers.add(conflict.teacher_code)
  })
  return Array.from(teachers)
})

const affectedSlots = computed(() => {
  const slots = new Set()
  props.conflicts.forEach(conflict => {
    conflict.classes.forEach(cls => {
      slots.add(`${cls.time_slot_id}-${cls.class_id}`)
    })
  })
  return Array.from(slots)
})

// Methods
const getClassName = (classId) => {
  // This would need to be passed from parent or fetched
  return `Class ${classId}`
}

const getTimeSlotName = (timeSlotId) => {
  // This would need to be passed from parent or fetched
  return `Slot ${timeSlotId}`
}

const getSuggestions = (conflict) => {
  const suggestions = []
  
  // Suggest alternative teachers
  suggestions.push({
    id: 'alt_teacher',
    description: `Cari guru pengganti untuk salah satu kelas`,
    type: 'alternative_teacher',
    action: 'find_alternative'
  })
  
  // Suggest time slot changes
  suggestions.push({
    id: 'reschedule',
    description: `Pindahkan ke slot waktu yang tersedia`,
    type: 'reschedule',
    action: 'move_timeslot'
  })
  
  // Suggest combining classes
  if (conflict.classes.length === 2) {
    suggestions.push({
      id: 'combine',
      description: `Gabungkan kedua kelas dalam satu sesi`,
      type: 'combine_classes',
      action: 'merge_classes'
    })
  }
  
  return suggestions
}

const autoResolveConflict = (conflict) => {
  // Implement automatic conflict resolution logic
  // This could use priority rules, teacher availability, etc.
  
  const resolution = {
    type: 'auto',
    conflict,
    action: 'keep_first_remove_others',
    data: {
      keep: conflict.classes[0],
      remove: conflict.classes.slice(1)
    }
  }
  
  emit('resolve', resolution)
}

const manualResolveConflict = (conflict) => {
  // Open manual resolution dialog
  // This could show a detailed form for user input
  console.log('Manual resolution for:', conflict)
}

const keepThisSchedule = (classData) => {
  const resolution = {
    type: 'manual',
    action: 'keep_specific',
    data: {
      keep: classData,
      removeOthers: true
    }
  }
  
  emit('resolve', resolution)
}

const removeThisSchedule = (classData) => {
  const resolution = {
    type: 'manual',
    action: 'remove_specific',
    data: {
      remove: classData
    }
  }
  
  emit('resolve', resolution)
}

const rescheduleThis = (classData) => {
  // This would open a rescheduling dialog
  console.log('Reschedule:', classData)
}

const applySuggestion = (suggestion, conflict) => {
  const resolution = {
    type: 'suggestion',
    suggestion,
    conflict,
    action: suggestion.action
  }
  
  emit('resolve', resolution)
}

const resolveAllConflicts = () => {
  // Auto-resolve all conflicts using default rules
  props.conflicts.forEach(conflict => {
    autoResolveConflict(conflict)
  })
}
</script>

<style scoped>
/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Smooth transitions */
button {
  transition: all 0.2s ease;
}
</style>