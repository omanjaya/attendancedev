<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white dark:bg-gray-800 rounded-xl max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-600">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          {{ schedule ? 'Edit Jadwal' : 'Tambah Jadwal' }}
        </h3>
        <button
          @click="$emit('close')"
          class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
        <!-- Subject Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Mata Pelajaran *
          </label>
          <select
            v-model="form.subject_id"
            @change="loadAvailableTeachers"
            required
            class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
          >
            <option value="">-- Pilih Mata Pelajaran --</option>
            <option 
              v-for="subject in subjects" 
              :key="subject.id" 
              :value="subject.id"
            >
              {{ subject.display_name }}
            </option>
          </select>
        </div>

        <!-- Teacher Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Guru *
          </label>
          <select
            v-model="form.employee_id"
            :disabled="!availableTeachers.length"
            required
            class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 disabled:opacity-50"
          >
            <option value="">-- Pilih Guru --</option>
            <option 
              v-for="teacher in availableTeachers" 
              :key="teacher.id" 
              :value="teacher.id"
            >
              {{ teacher.name }} ({{ teacher.employee_id }})
            </option>
          </select>
          <p v-if="form.subject_id && !availableTeachers.length" class="text-sm text-red-600 dark:text-red-400 mt-1">
            Tidak ada guru yang tersedia untuk mata pelajaran ini pada waktu yang dipilih
          </p>
        </div>

        <!-- Time Slot (Read-only if editing) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Waktu
          </label>
          <div class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-600 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300">
            {{ selectedTimeSlotName }} - {{ selectedDayName }}
          </div>
        </div>

        <!-- Room -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Ruangan
          </label>
          <input
            v-model="form.room"
            type="text"
            placeholder="Masukkan ruangan (opsional)"
            class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
          >
        </div>

        <!-- Effective Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Berlaku Mulai
          </label>
          <input
            v-model="form.effective_from"
            type="date"
            required
            class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
          >
        </div>

        <!-- Reason (for editing) -->
        <div v-if="schedule">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Alasan Perubahan
          </label>
          <textarea
            v-model="form.reason"
            rows="3"
            placeholder="Masukkan alasan perubahan..."
            class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
          ></textarea>
        </div>

        <!-- Validation Warnings -->
        <div v-if="validationWarnings.length > 0" class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-700/50 rounded-lg p-3">
          <div class="flex items-start">
            <ExclamationTriangleIcon class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 mr-2 flex-shrink-0" />
            <div>
              <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Peringatan:</h4>
              <ul class="mt-1 text-sm text-yellow-700 dark:text-yellow-300 list-disc list-inside">
                <li v-for="warning in validationWarnings" :key="warning">{{ warning }}</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Error Messages -->
        <div v-if="errorMessage" class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700/50 rounded-lg p-3">
          <div class="flex items-start">
            <ExclamationTriangleIcon class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 mr-2 flex-shrink-0" />
            <div>
              <h4 class="text-sm font-medium text-red-800 dark:text-red-200">Error:</h4>
              <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ errorMessage }}</p>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-600">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
          >
            Batal
          </button>
          <button
            type="submit"
            :disabled="saving || !form.subject_id || !form.employee_id"
            class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ saving ? 'Menyimpan...' : (schedule ? 'Update' : 'Simpan') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { XMarkIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  schedule: {
    type: Object,
    default: null
  },
  academicClassId: {
    type: String,
    required: true
  },
  dayOfWeek: {
    type: String,
    required: true
  },
  timeSlotId: {
    type: String,
    required: true
  },
  subjects: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['close', 'saved'])

// Reactive state
const saving = ref(false)
const errorMessage = ref('')
const validationWarnings = ref([])
const availableTeachers = ref([])
const timeSlotName = ref('')
const dayName = ref('')

const form = reactive({
  academic_class_id: props.academicClassId,
  subject_id: '',
  employee_id: '',
  time_slot_id: props.timeSlotId,
  day_of_week: props.dayOfWeek,
  room: '',
  effective_from: new Date().toISOString().split('T')[0],
  reason: ''
})

// Days mapping
const DAYS_OF_WEEK = {
  'monday': 'Senin',
  'tuesday': 'Selasa', 
  'wednesday': 'Rabu',
  'thursday': 'Kamis',
  'friday': 'Jumat',
  'saturday': 'Sabtu'
}

// Computed
const selectedTimeSlotName = computed(() => timeSlotName.value)
const selectedDayName = computed(() => DAYS_OF_WEEK[props.dayOfWeek] || props.dayOfWeek)

// Methods
const loadAvailableTeachers = async () => {
  if (!form.subject_id) {
    availableTeachers.value = []
    return
  }

  try {
    const response = await fetch('/api/academic-schedules/available-teachers', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        subject_id: form.subject_id,
        day_of_week: form.day_of_week,
        time_slot_id: form.time_slot_id
      })
    })

    const data = await response.json()

    if (data.success) {
      availableTeachers.value = data.data

      // If editing and current teacher is not in available list, add them
      if (props.schedule && props.schedule.employee_id) {
        const currentTeacher = availableTeachers.value.find(t => t.id === props.schedule.employee_id)
        if (!currentTeacher) {
          availableTeachers.value.unshift({
            id: props.schedule.employee_id,
            name: props.schedule.employee.full_name,
            employee_id: props.schedule.employee.employee_id
          })
        }
      }
    }
  } catch (error) {
    console.error('Error loading available teachers:', error)
    availableTeachers.value = []
  }
}

const handleSubmit = async () => {
  saving.value = true
  errorMessage.value = ''
  validationWarnings.value = []

  try {
    const url = props.schedule 
      ? `/api/academic-schedules/${props.schedule.id}`
      : '/api/academic-schedules'
    
    const method = props.schedule ? 'PUT' : 'POST'
    
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(form)
    })

    const data = await response.json()

    if (data.success) {
      // Show warnings if any
      if (data.conflicts && data.conflicts.length > 0) {
        const warnings = data.conflicts.map(c => c.description)
        validationWarnings.value = warnings
        
        // Still allow saving but show warnings
        setTimeout(() => {
          emit('saved', data.data)
        }, 2000)
      } else {
        emit('saved', data.data)
      }
    } else {
      if (data.errors) {
        // Handle validation errors
        const errors = Object.values(data.errors).flat()
        errorMessage.value = errors.join(', ')
      } else {
        errorMessage.value = data.message || 'Gagal menyimpan jadwal'
      }
    }
  } catch (error) {
    console.error('Error saving schedule:', error)
    errorMessage.value = 'Terjadi kesalahan saat menyimpan jadwal'
  } finally {
    saving.value = false
  }
}

const loadTimeSlotInfo = async () => {
  try {
    // This would typically come from the parent component or API
    // For now, we'll assume it's passed or we can find it in the subjects data
    timeSlotName.value = `Jam ${props.timeSlotId.slice(-1)}` // Temporary
  } catch (error) {
    console.error('Error loading time slot info:', error)
  }
}

// Watchers
watch(() => form.subject_id, () => {
  form.employee_id = ''
  loadAvailableTeachers()
})

// Lifecycle
onMounted(() => {
  // Initialize form with existing schedule data if editing
  if (props.schedule) {
    Object.assign(form, {
      subject_id: props.schedule.subject_id,
      employee_id: props.schedule.employee_id,
      room: props.schedule.room || '',
      effective_from: props.schedule.effective_from || form.effective_from
    })
  }

  loadTimeSlotInfo()
  
  // Load available teachers if subject is already selected
  if (form.subject_id) {
    loadAvailableTeachers()
  }
})
</script>

<style scoped>
/* Custom scrollbar for modal */
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
</style>