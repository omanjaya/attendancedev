<template>
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    role="dialog"
    aria-modal="true"
    :aria-labelledby="modalTitleId"
  >
    <div
      ref="modalContent"
      class="mx-4 max-h-[90vh] w-full max-w-md overflow-y-auto rounded-xl bg-white dark:bg-gray-800"
    >
      <!-- Header -->
      <div
        class="flex items-center justify-between border-b border-gray-200 p-6 dark:border-gray-600"
      >
        <h3 :id="modalTitleId" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          {{ schedule ? 'Edit Jadwal' : 'Tambah Jadwal' }}
        </h3>
        <button
          class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
          aria-label="Close modal"
          @click="$emit('close')"
        >
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>

      <!-- Form -->
      <form class="space-y-4 p-6" @submit.prevent="handleSubmit">
        <!-- Subject Selection -->
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            Mata Pelajaran *
          </label>
          <select
            v-model="validation.fields.subject_id.value.value"
            required
            class="w-full rounded-lg border bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700"
            :class="{
              'border-red-500 focus:ring-red-500': validation.fields.subject_id.error.value,
              'border-gray-300 dark:border-gray-600': !validation.fields.subject_id.error.value,
            }"
            aria-label="Select subject"
            :aria-describedby="
              validation.fields.subject_id.error.value ? 'subject-error' : 'subject-help'
            "
            :aria-invalid="!!validation.fields.subject_id.error.value"
            @change="loadAvailableTeachers"
            @blur="
              validation.fields.subject_id.isTouched.value = true
              validation.fields.subject_id.validate()
            "
          >
            <option value="">
              -- Pilih Mata Pelajaran --
            </option>
            <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
              {{ subject.display_name }}
            </option>
          </select>
          <!-- Validation Error -->
          <div
            v-if="validation.fields.subject_id.error.value"
            id="subject-error"
            class="mt-1 text-sm text-red-600 dark:text-red-400"
            role="alert"
          >
            {{ validation.fields.subject_id.error.value }}
          </div>
        </div>

        <!-- Teacher Selection -->
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            Guru *
          </label>
          <select
            v-model="validation.fields.employee_id.value.value"
            :disabled="!availableTeachers.length"
            required
            class="w-full rounded-lg border bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:opacity-50 dark:bg-gray-700"
            :class="{
              'border-red-500 focus:ring-red-500': validation.fields.employee_id.error.value,
              'border-gray-300 dark:border-gray-600': !validation.fields.employee_id.error.value,
            }"
            aria-label="Select teacher"
            :aria-describedby="
              validation.fields.employee_id.error.value
                ? 'teacher-validation-error'
                : !availableTeachers.length
                  ? 'teacher-error'
                  : null
            "
            :aria-invalid="!!validation.fields.employee_id.error.value"
            @blur="
              validation.fields.employee_id.isTouched.value = true
              validation.fields.employee_id.validate()
            "
          >
            <option value="">
              -- Pilih Guru --
            </option>
            <option v-for="teacher in availableTeachers" :key="teacher.id" :value="teacher.id">
              {{ teacher.name }} ({{ teacher.employee_id }})
            </option>
          </select>
          <!-- Validation Error -->
          <div
            v-if="validation.fields.employee_id.error.value"
            id="teacher-validation-error"
            class="mt-1 text-sm text-red-600 dark:text-red-400"
            role="alert"
          >
            {{ validation.fields.employee_id.error.value }}
          </div>
          <!-- Availability Error -->
          <p
            v-if="validation.fields.subject_id.value.value && !availableTeachers.length"
            id="teacher-error"
            class="mt-1 text-sm text-red-600 dark:text-red-400"
            role="alert"
          >
            Tidak ada guru yang tersedia untuk mata pelajaran ini pada waktu yang dipilih
          </p>
        </div>

        <!-- Time Slot (Read-only if editing) -->
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            Waktu
          </label>
          <div
            class="w-full rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-gray-700 dark:border-gray-600 dark:bg-gray-600 dark:text-gray-300"
          >
            {{ selectedTimeSlotName }} - {{ selectedDayName }}
          </div>
        </div>

        <!-- Room -->
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            Ruangan
          </label>
          <input
            v-model="validation.fields.room.value.value"
            type="text"
            placeholder="Masukkan ruangan (opsional)"
            class="w-full rounded-lg border bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700"
            :class="{
              'border-red-500 focus:ring-red-500': validation.fields.room.error.value,
              'border-gray-300 dark:border-gray-600': !validation.fields.room.error.value,
            }"
            aria-label="Room (optional)"
            :aria-describedby="validation.fields.room.error.value ? 'room-error' : null"
            :aria-invalid="!!validation.fields.room.error.value"
            @blur="
              validation.fields.room.isTouched.value = true
              validation.fields.room.validate()
            "
          >
          <!-- Validation Error -->
          <div
            v-if="validation.fields.room.error.value"
            id="room-error"
            class="mt-1 text-sm text-red-600 dark:text-red-400"
            role="alert"
          >
            {{ validation.fields.room.error.value }}
          </div>
        </div>

        <!-- Effective Date -->
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            Berlaku Mulai
          </label>
          <input
            v-model="validation.fields.effective_from.value.value"
            type="date"
            required
            class="w-full rounded-lg border bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700"
            :class="{
              'border-red-500 focus:ring-red-500': validation.fields.effective_from.error.value,
              'border-gray-300 dark:border-gray-600': !validation.fields.effective_from.error.value,
            }"
            aria-label="Effective from date"
            :aria-describedby="
              validation.fields.effective_from.error.value ? 'effective-date-error' : null
            "
            :aria-invalid="!!validation.fields.effective_from.error.value"
            @blur="
              validation.fields.effective_from.isTouched.value = true
              validation.fields.effective_from.validate()
            "
          >
          <!-- Validation Error -->
          <div
            v-if="validation.fields.effective_from.error.value"
            id="effective-date-error"
            class="mt-1 text-sm text-red-600 dark:text-red-400"
            role="alert"
          >
            {{ validation.fields.effective_from.error.value }}
          </div>
        </div>

        <!-- Reason (for editing) -->
        <div v-if="schedule">
          <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            Alasan Perubahan
          </label>
          <textarea
            v-model="validation.fields.reason.value.value"
            rows="3"
            placeholder="Masukkan alasan perubahan..."
            class="w-full rounded-lg border bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-gray-700"
            :class="{
              'border-red-500 focus:ring-red-500': validation.fields.reason.error.value,
              'border-gray-300 dark:border-gray-600': !validation.fields.reason.error.value,
            }"
            aria-label="Reason for change"
            :aria-describedby="validation.fields.reason.error.value ? 'reason-error' : null"
            :aria-invalid="!!validation.fields.reason.error.value"
            @blur="
              validation.fields.reason.isTouched.value = true
              validation.fields.reason.validate()
            "
          />
          <!-- Validation Error -->
          <div
            v-if="validation.fields.reason.error.value"
            id="reason-error"
            class="mt-1 text-sm text-red-600 dark:text-red-400"
            role="alert"
          >
            {{ validation.fields.reason.error.value }}
          </div>
        </div>

        <!-- Validation Warnings -->
        <div
          v-if="validationWarnings.length > 0"
          class="rounded-lg border border-yellow-200 bg-yellow-50 p-3 dark:border-yellow-700/50 dark:bg-yellow-900/30"
          role="alert"
          aria-live="polite"
        >
          <div class="flex items-start">
            <ExclamationTriangleIcon
              class="mr-2 mt-0.5 h-5 w-5 flex-shrink-0 text-yellow-600 dark:text-yellow-400"
            />
            <div>
              <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                Peringatan:
              </h4>
              <ul class="mt-1 list-inside list-disc text-sm text-yellow-700 dark:text-yellow-300">
                <li v-for="warning in validationWarnings" :key="warning">
                  {{ warning }}
                </li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Error Messages -->
        <div
          v-if="errorMessage"
          class="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-700/50 dark:bg-red-900/30"
          role="alert"
          aria-live="assertive"
        >
          <div class="flex items-start">
            <ExclamationTriangleIcon
              class="mr-2 mt-0.5 h-5 w-5 flex-shrink-0 text-red-600 dark:text-red-400"
            />
            <div>
              <h4 class="text-sm font-medium text-red-800 dark:text-red-200">
                Error:
              </h4>
              <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                {{ errorMessage }}
              </p>
            </div>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 border-t border-gray-200 pt-4 dark:border-gray-600">
          <button
            type="button"
            class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
            aria-label="Cancel schedule creation"
            @click="$emit('close')"
          >
            Batal
          </button>
          <button
            type="submit"
            :disabled="
              saving ||
                !validation.isValid.value ||
                !validation.fields.subject_id.value.value ||
                !validation.fields.employee_id.value.value
            "
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
            :aria-label="
              saving ? 'Saving schedule...' : schedule ? 'Update schedule' : 'Save new schedule'
            "
          >
            {{ saving ? 'Menyimpan...' : schedule ? 'Update' : 'Simpan' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { XMarkIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { useValidation, ValidationSchemas } from '@/composables/useValidation'
import { useErrorTrackingForValidation } from '@/composables/useErrorTracking'
import { useCachedRequest, useCacheKey } from '@/composables/useRequestCache'
import { useModalNavigation } from '@/composables/useKeyboardNavigation'

// Props
const props = defineProps({
  schedule: {
    type: Object,
    default: null,
  },
  academicClassId: {
    type: String,
    required: true,
  },
  dayOfWeek: {
    type: String,
    required: true,
  },
  timeSlotId: {
    type: String,
    required: true,
  },
  subjects: {
    type: Array,
    default: () => [],
  },
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
const modalTitleId = 'modal-title-' + Date.now()
const modalContent = ref<HTMLElement | null>(null)

// Keyboard navigation
const { focusInitialElement } = useModalNavigation(modalContent, {
  onEscape: () => emit('close'),
  initialFocus: 'select, input, textarea, button',
})

// Form validation setup
const initialFormData = {
  academic_class_id: props.academicClassId,
  subject_id: '',
  employee_id: '',
  time_slot_id: props.timeSlotId,
  day_of_week: props.dayOfWeek,
  room: '',
  effective_from: new Date().toISOString().split('T')[0],
  reason: '',
}

const validation = useValidation(ValidationSchemas.scheduleCreation, initialFormData, {
  validateOnChange: true,
  validateOnBlur: true,
  debounceMs: 300,
})

// Error tracking
const errorTracking = useErrorTrackingForValidation()

// Cache key generation
const cacheKey = useCacheKey()

// Cached request for available teachers
const teachersRequest = useCachedRequest(
  () =>
    cacheKey.generateKey('available-teachers', {
      subject_id: validation.fields.subject_id.value.value,
      day_of_week: validation.fields.day_of_week.value.value,
      time_slot_id: validation.fields.time_slot_id.value.value,
    }),
  () => fetchAvailableTeachers(),
  {
    ttl: 2 * 60 * 1000, // 2 minutes cache
    enabled: computed(() => !!validation.fields.subject_id.value.value),
    watchSource: () =>
      [
        validation.fields.subject_id.value.value,
        validation.fields.day_of_week.value.value,
        validation.fields.time_slot_id.value.value,
      ].join('|'),
    onSuccess: (teachers) => {
      availableTeachers.value = teachers

      // If editing and current teacher is not in available list, add them
      if (props.schedule && props.schedule.employee_id) {
        const currentTeacher = teachers.find((t) => t.id === props.schedule.employee_id)
        if (!currentTeacher) {
          availableTeachers.value.unshift({
            id: props.schedule.employee_id,
            name: props.schedule.employee.full_name,
            employee_id: props.schedule.employee.employee_id,
          })
        }
      }
    },
    onError: (error) => {
      errorTracking.captureError(error, {
        action: 'load_teachers_cache_failed',
        metadata: {
          subjectId: validation.fields.subject_id.value.value,
          scheduleId: props.schedule?.id,
        },
      })
      availableTeachers.value = []
    },
  }
)

// Use cached data
watch(
  () => teachersRequest.data.value,
  (teachers) => {
    if (teachers) {
      availableTeachers.value = teachers
    }
  },
  { immediate: true }
)

// Form reactive reference for easy access
const form = computed(() => {
  const data: any = {}
  for (const [key, field] of Object.entries(validation.fields)) {
    data[key] = field.value.value
  }
  return data
})

// Days mapping
const DAYS_OF_WEEK = {
  monday: 'Senin',
  tuesday: 'Selasa',
  wednesday: 'Rabu',
  thursday: 'Kamis',
  friday: 'Jumat',
  saturday: 'Sabtu',
}

// Computed
const selectedTimeSlotName = computed(() => timeSlotName.value)
const selectedDayName = computed(() => DAYS_OF_WEEK[props.dayOfWeek] || props.dayOfWeek)

// Methods
// Extract the actual fetch logic for use with cache
const fetchAvailableTeachers = async () => {
  const subjectId = validation.fields.subject_id.value.value

  errorTracking.addBreadcrumb('Fetching available teachers', 'api', {
    subjectId,
    dayOfWeek: validation.fields.day_of_week.value.value,
    timeSlotId: validation.fields.time_slot_id.value.value,
  })

  const response = await fetch('/api/academic-schedules/available-teachers', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
    },
    body: JSON.stringify({
      subject_id: subjectId,
      day_of_week: validation.fields.day_of_week.value.value,
      time_slot_id: validation.fields.time_slot_id.value.value,
    }),
  })

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}: ${response.statusText}`)
  }

  const data = await response.json()

  if (data.success) {
    errorTracking.addBreadcrumb('Available teachers fetched', 'api', {
      teachersCount: data.data.length,
    })
    return data.data
  } else {
    throw new Error(data.message || 'Failed to load available teachers')
  }
}

// Legacy method for manual refresh (now uses cache)
const loadAvailableTeachers = async () => {
  await teachersRequest.refresh()
}

const handleSubmit = async () => {
  saving.value = true
  errorMessage.value = ''
  validationWarnings.value = []

  // Validate all fields before submission
  const validationResult = validation.validateAll()

  if (!validationResult.isValid) {
    errorMessage.value = 'Please fix the validation errors before submitting'
    saving.value = false
    errorTracking.captureMessage('Validation failed before submission', 'warning', {
      action: 'submit_validation_failed',
      metadata: {
        errors: validationResult.errors,
        isEditing: !!props.schedule,
      },
    })
    return
  }

  return errorTracking
    .trackAsyncOperation('save_schedule', async () => {
      errorTracking.addBreadcrumb('Saving schedule', 'form', {
        isEditing: !!props.schedule,
        academicClassId: props.academicClassId,
        subjectId: validation.fields.subject_id.value.value,
      })

      // Get sanitized data
      const sanitizedData = validation.getSanitizedData()

      const url = props.schedule
        ? `/api/academic-schedules/${props.schedule.id}`
        : '/api/academic-schedules'

      const method = props.schedule ? 'PUT' : 'POST'

      const response = await fetch(url, {
        method,
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify(sanitizedData),
      })

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`)
      }

      const data = await response.json()

      if (data.success) {
        errorTracking.addBreadcrumb('Schedule saved successfully', 'form', {
          scheduleId: data.data?.id,
          hasConflicts: !!(data.conflicts && data.conflicts.length > 0),
        })

        // Show warnings if any
        if (data.conflicts && data.conflicts.length > 0) {
          const warnings = data.conflicts.map((c) => c.description)
          validationWarnings.value = warnings

          errorTracking.captureMessage('Schedule saved with conflicts', 'warning', {
            action: 'schedule_conflicts',
            metadata: {
              conflicts: data.conflicts,
              scheduleId: data.data?.id,
            },
          })

          // Still allow saving but show warnings
          setTimeout(() => {
            emit('saved', data.data)
          }, 2000)
        } else {
          emit('saved', data.data)
        }
      } else {
        const errorMsg = data.errors
          ? Object.values(data.errors).flat().join(', ')
          : data.message || 'Gagal menyimpan jadwal'

        errorMessage.value = errorMsg
        throw new Error(errorMsg)
      }
    })
    .catch((error: Error) => {
      errorTracking.captureError(error, {
        action: 'save_schedule_failed',
        metadata: {
          isEditing: !!props.schedule,
          scheduleId: props.schedule?.id,
          formData: validation.getSanitizedData(),
          responseError: error.message.includes('HTTP') ? error.message : null,
        },
      })

      if (!errorMessage.value) {
        errorMessage.value = 'Terjadi kesalahan saat menyimpan jadwal'
      }
      console.error('Error saving schedule:', error)
      throw error
    })
    .finally(() => {
      saving.value = false
    })
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
watch(
  () => validation.fields.subject_id.value.value,
  () => {
    validation.setFieldValue('employee_id', '')
    // The cache will automatically refresh due to watchSource
  }
)

// Lifecycle
onMounted(() => {
  // Initialize form with existing schedule data if editing
  if (props.schedule) {
    validation.setFieldValue('subject_id', props.schedule.subject_id)
    validation.setFieldValue('employee_id', props.schedule.employee_id)
    validation.setFieldValue('room', props.schedule.room || '')
    validation.setFieldValue(
      'effective_from',
      props.schedule.effective_from || initialFormData.effective_from
    )
  }

  loadTimeSlotInfo()

  // Load available teachers if subject is already selected
  if (validation.fields.subject_id.value.value) {
    loadAvailableTeachers()
  }

  // Focus the initial element with keyboard navigation
  await focusInitialElement()
})

onUnmounted(() => {
  // Cleanup handled by useModalNavigation
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
