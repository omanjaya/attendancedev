<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div
      class="mx-4 max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white dark:bg-gray-800"
    >
      <!-- Header -->
      <div
        class="flex items-center justify-between border-b border-gray-200 p-6 dark:border-gray-600"
      >
        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
          Detail Jadwal
        </h3>
        <button
          class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
          @click="$emit('close')"
        >
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>

      <!-- Content -->
      <div class="space-y-6 p-6">
        <!-- Schedule Info Card -->
        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700/50">
          <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div
                class="h-4 w-4 rounded"
                :style="{ backgroundColor: schedule.subject?.color || '#3B82F6' }"
              />
              <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ schedule.subject?.name }}
              </h4>
              <span
                class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200"
              >
                {{ schedule.subject?.code }}
              </span>
            </div>

            <!-- Status Indicators -->
            <div class="flex items-center space-x-2">
              <div
                v-if="schedule.is_locked"
                class="flex items-center rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800 dark:bg-red-800 dark:text-red-200"
              >
                <LockClosedIcon class="mr-1 h-3 w-3" />
                Terkunci
              </div>
              <div
                v-if="hasConflicts"
                class="flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200"
              >
                <ExclamationTriangleIcon class="mr-1 h-3 w-3" />
                Konflik
              </div>
            </div>
          </div>

          <!-- Schedule Details Grid -->
          <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
            <div>
              <span class="font-medium text-gray-600 dark:text-gray-400">Kelas:</span>
              <p class="text-gray-900 dark:text-gray-100">
                {{ schedule.academic_class?.full_name }}
              </p>
            </div>
            <div>
              <span class="font-medium text-gray-600 dark:text-gray-400">Guru:</span>
              <p class="text-gray-900 dark:text-gray-100">
                {{ schedule.employee?.full_name }}
              </p>
            </div>
            <div>
              <span class="font-medium text-gray-600 dark:text-gray-400">Hari:</span>
              <p class="text-gray-900 dark:text-gray-100">
                {{ formatDay(schedule.day_of_week) }}
              </p>
            </div>
            <div>
              <span class="font-medium text-gray-600 dark:text-gray-400">Waktu:</span>
              <p class="text-gray-900 dark:text-gray-100">
                {{ schedule.time_slot?.name }} ({{ schedule.time_slot?.start_time }} -
                {{ schedule.time_slot?.end_time }})
              </p>
            </div>
            <div v-if="schedule.room">
              <span class="font-medium text-gray-600 dark:text-gray-400">Ruangan:</span>
              <p class="text-gray-900 dark:text-gray-100">
                {{ schedule.room }}
              </p>
            </div>
            <div>
              <span class="font-medium text-gray-600 dark:text-gray-400">Berlaku:</span>
              <p class="text-gray-900 dark:text-gray-100">
                {{ formatDate(schedule.effective_from) }}
                <span v-if="schedule.effective_until">
                  - {{ formatDate(schedule.effective_until) }}</span>
                <span v-else> - sekarang</span>
              </p>
            </div>
          </div>
        </div>

        <!-- Subject Information -->
        <div
          v-if="schedule.subject"
          class="rounded-lg border border-gray-200 p-4 dark:border-gray-600"
        >
          <h5 class="mb-3 font-semibold text-gray-900 dark:text-gray-100">
            Informasi Mata Pelajaran
          </h5>
          <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
            <div>
              <span class="font-medium text-gray-600 dark:text-gray-400">Kategori:</span>
              <p class="text-gray-900 dark:text-gray-100">
                {{ schedule.subject.category }}
              </p>
            </div>
            <div>
              <span class="font-medium text-gray-600 dark:text-gray-400">Jam per Minggu:</span>
              <p class="text-gray-900 dark:text-gray-100">
                {{ schedule.subject.weekly_hours }} jam
              </p>
            </div>
            <div>
              <span class="font-medium text-gray-600 dark:text-gray-400">Maks. Pertemuan:</span>
              <p class="text-gray-900 dark:text-gray-100">
                {{ schedule.subject.max_meetings_per_week }} kali/minggu
              </p>
            </div>
            <div v-if="schedule.subject.requires_lab">
              <span class="font-medium text-gray-600 dark:text-gray-400">Lab:</span>
              <div class="flex items-center">
                <CheckIcon class="mr-1 h-4 w-4 text-green-500" />
                <span class="text-gray-900 dark:text-gray-100">Memerlukan Laboratorium</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Teacher Information -->
        <div
          v-if="schedule.employee"
          class="rounded-lg border border-gray-200 p-4 dark:border-gray-600"
        >
          <h5 class="mb-3 font-semibold text-gray-900 dark:text-gray-100">
            Informasi Guru
          </h5>
          <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
            <div>
              <span class="font-medium text-gray-600 dark:text-gray-400">ID Pegawai:</span>
              <p class="text-gray-900 dark:text-gray-100">
                {{ schedule.employee.employee_id }}
              </p>
            </div>
            <div>
              <span class="font-medium text-gray-600 dark:text-gray-400">Jenis:</span>
              <p class="capitalize text-gray-900 dark:text-gray-100">
                {{ schedule.employee.employee_type }}
              </p>
            </div>
            <div>
              <span class="font-medium text-gray-600 dark:text-gray-400">Email:</span>
              <p class="text-gray-900 dark:text-gray-100">
                {{ schedule.employee.email || '-' }}
              </p>
            </div>
            <div>
              <span class="font-medium text-gray-600 dark:text-gray-400">Telepon:</span>
              <p class="text-gray-900 dark:text-gray-100">
                {{ schedule.employee.phone_number || '-' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Change History -->
        <div
          v-if="changeHistory.length > 0"
          class="rounded-lg border border-gray-200 p-4 dark:border-gray-600"
        >
          <h5 class="mb-3 font-semibold text-gray-900 dark:text-gray-100">
            Riwayat Perubahan
          </h5>
          <div class="max-h-40 space-y-3 overflow-y-auto">
            <div
              v-for="change in changeHistory"
              :key="change.id"
              class="flex items-start justify-between border-b border-gray-100 py-2 last:border-b-0 dark:border-gray-600"
            >
              <div class="flex-1">
                <p class="text-sm text-gray-900 dark:text-gray-100">
                  {{ change.reason || change.action_type }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  {{ formatDateTime(change.created_at) }} oleh
                  {{ change.changed_by?.name || 'System' }}
                </p>
              </div>
              <span
                :class="[
                  'rounded-full px-2 py-1 text-xs font-medium',
                  change.action_type === 'create'
                    ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200'
                    : change.action_type === 'update'
                      ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200'
                      : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200',
                ]"
              >
                {{ change.action_type.toUpperCase() }}
              </span>
            </div>
          </div>
        </div>

        <!-- Conflicts (if any) -->
        <div
          v-if="conflicts.length > 0"
          class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-700/50 dark:bg-yellow-900/30"
        >
          <h5 class="mb-3 font-semibold text-yellow-800 dark:text-yellow-200">
            Konflik Terdeteksi
          </h5>
          <div class="space-y-2">
            <div
              v-for="conflict in conflicts"
              :key="conflict.id"
              class="text-sm text-yellow-700 dark:text-yellow-300"
            >
              <div class="flex items-center justify-between">
                <span>{{ conflict.description }}</span>
                <span
                  :class="[
                    'rounded-full px-2 py-1 text-xs font-medium',
                    conflict.severity === 'critical'
                      ? 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200'
                      : conflict.severity === 'high'
                        ? 'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-200'
                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200',
                  ]"
                >
                  {{ conflict.severity.toUpperCase() }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div
        class="flex items-center justify-between border-t border-gray-200 p-6 dark:border-gray-600"
      >
        <div class="flex space-x-3">
          <button
            v-if="!schedule.is_locked"
            class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
            @click="$emit('edit', schedule)"
          >
            <PencilIcon class="mr-2 h-4 w-4" />
            Edit
          </button>

          <button
            :class="[
              'inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium transition-colors',
              schedule.is_locked
                ? 'bg-green-600 text-white hover:bg-green-700'
                : 'bg-red-600 text-white hover:bg-red-700',
            ]"
            @click="$emit('toggleLock', schedule)"
          >
            <LockOpenIcon v-if="schedule.is_locked" class="mr-2 h-4 w-4" />
            <LockClosedIcon v-else class="mr-2 h-4 w-4" />
            {{ schedule.is_locked ? 'Buka Kunci' : 'Kunci' }}
          </button>
        </div>

        <div class="flex space-x-3">
          <button
            class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700"
            @click="$emit('delete', schedule)"
          >
            <TrashIcon class="mr-2 h-4 w-4" />
            Hapus
          </button>

          <button
            class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
            @click="$emit('close')"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  XMarkIcon,
  PencilIcon,
  TrashIcon,
  LockClosedIcon,
  LockOpenIcon,
  ExclamationTriangleIcon,
  CheckIcon,
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  schedule: {
    type: Object,
    required: true,
  },
})

// Emits
const emit = defineEmits(['close', 'edit', 'delete', 'toggleLock'])

// Reactive state
const changeHistory = ref([])
const conflicts = ref([])
const loading = ref(false)

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
const hasConflicts = computed(() => conflicts.value.length > 0)

// Methods
const formatDay = (day) => {
  return DAYS_OF_WEEK[day] || day
}

const formatDate = (dateString) => {
  if (!dateString) {return '-'}
  return new Date(dateString).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const formatDateTime = (dateString) => {
  if (!dateString) {return '-'}
  return new Date(dateString).toLocaleString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const loadChangeHistory = async () => {
  if (!props.schedule?.id) {return}

  try {
    const response = await fetch(`/api/academic-schedules/${props.schedule.id}/history`)
    const data = await response.json()

    if (data.success) {
      changeHistory.value = data.data
    }
  } catch (error) {
    console.error('Error loading change history:', error)
  }
}

const loadConflicts = async () => {
  if (!props.schedule?.id) {return}

  try {
    const response = await fetch(`/api/academic-schedules/${props.schedule.id}/conflicts`)
    const data = await response.json()

    if (data.success) {
      conflicts.value = data.data
    }
  } catch (error) {
    console.error('Error loading conflicts:', error)
  }
}

// Lifecycle
onMounted(() => {
  loadChangeHistory()
  loadConflicts()
})
</script>

<style scoped>
/* Custom scrollbar for content */
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

/* Change history scrollbar */
.max-h-40::-webkit-scrollbar {
  width: 4px;
}

.max-h-40::-webkit-scrollbar-track {
  background: #f9fafb;
}

.max-h-40::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 2px;
}
</style>
